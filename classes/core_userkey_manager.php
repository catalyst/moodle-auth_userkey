<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Key manager class.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_userkey;

defined('MOODLE_INTERNAL') || die;

class core_userkey_manager implements userkey_manager_interface {

    /**
     * This script script required by core create_user_key().
     */
    const CORE_USER_KEY_MANAGER_SCRIPT = 'auth/userkey';

    /**
     * Default life time of the user key in seconds.
     */
    const DEFAULT_KEY_LIFE_TIME_IN_SECONDS = 60;

    /**
     * Config object.
     *
     * @var \stdClass
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param \stdClass $config
     */
    public function __construct(\stdClass $config) {
        $this->config = $config;
    }

    /**
     * Create a user key.
     *
     * @param int $userid User ID.
     * @param null|array $allowedips A list of allowed ips for this key.
     *
     * @return string Generated key.
     */
    public function create_key($userid, $allowedips = null) {
        $this->delete_keys($userid);

        if (isset($this->config->keylifetime) && (int)$this->config->keylifetime > 0) {
            $validuntil = time() + $this->config->keylifetime;
        } else {
            $validuntil = time() + self::DEFAULT_KEY_LIFE_TIME_IN_SECONDS;
        }

        $iprestriction = null;

        if (isset($this->config->iprestriction) && !empty($this->config->iprestriction)) {
            if ($allowedips) {
                $iprestriction = $allowedips;
            } else {
                $iprestriction = getremoteaddr(null);
            }
        }

        return create_user_key(
            self::CORE_USER_KEY_MANAGER_SCRIPT,
            $userid,
            $userid,
            $iprestriction,
            $validuntil
        );
    }

    /**
     * Delete all keys for a specific user.
     *
     * @param int $userid User ID.
     */
    public function delete_keys($userid) {
         delete_user_key(self::CORE_USER_KEY_MANAGER_SCRIPT, $userid);
    }

    /**
     * Validates key and returns key data object if valid.
     *
     * @param string $keyvalue User key value.
     *
     * @return object Key object including userid property.
     *
     * @throws \moodle_exception If provided key is not valid.
     */
    public function validate_key($keyvalue) {
        global $DB;

        $options = array(
            'script' => self::CORE_USER_KEY_MANAGER_SCRIPT,
            'value' => $keyvalue
        );

        if (!$key = $DB->get_record('user_private_key', $options)) {
            print_error('invalidkey');
        }

        if (!empty($key->validuntil) and $key->validuntil < time()) {
            print_error('expiredkey');
        }

        $this->validate_ip_address($key);

        if (!$user = $DB->get_record('user', array('id' => $key->userid))) {
            print_error('invaliduserid');
        }
        return $key;
    }

    /**
     * Validates key IP address and returns true if valid.
     *
     * @param object $key Key object including userid property.
     *
     * @throws \moodle_exception If provided key is not valid.
     */
    protected function validate_ip_address($key) {
        if (!$key->iprestriction) {
            return true;
        }

        $remoteaddr = getremoteaddr(null);

        if (empty($remoteaddr)) {
            print_error('noip', 'auth_userkey');
        }

        if (address_in_subnet($remoteaddr, $key->iprestriction)) {
            return true;
        }

        if (isset($this->config->ipwhitelist)) {
            $ips = explode(';', $this->config->ipwhitelist);
            foreach ($ips as $ip) {
                if (address_in_subnet($remoteaddr, $ip)) {
                    return true;
                }
            }
        }

        print_error('ipmismatch', 'error', '', null, "Remote address: $remoteaddr\nKey IP: $key->iprestriction");
    }
}
