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
     * Generated user key.
     *
     * @var string
     */
    protected $userkey;

    /**
     * User id.
     *
     * @var int
     */
    protected $userid;

    /**
     * Shows if we need restrict user key by IP.
     *
     * @var null | bool
     */
    protected $iprestriction = null;

    /**
     * Time when user key will be expired in unix stamp format.
     *
     * @var null | string
     */
    protected $validuntil = null;

    /**
     * Config object.
     *
     * @var \stdClass
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param $userid
     * @param \stdClass $config
     *
     * @throws \Exception If there is no user with provided userid.
     */
    public function __construct($userid, \stdClass $config) {
        global $DB;

        if (!$DB->record_exists('user', array('id' => $userid))) {
            throw new \Exception('Invalid user id');
        }

        $this->userid = $userid;
        $this->config = $config;

        if (isset($config->iprestriction) && !empty($config->iprestriction)) {
            $this->iprestriction = getremoteaddr(null);
        }

        if (isset($config->keylifetime) && (int)$config->keylifetime > 0) {
            $this->validuntil = time() + $config->keylifetime;
        } else {
            $this->validuntil = time() + self::DEFAULT_KEY_LIFE_TIME_IN_SECONDS;
        }
    }

    /**
     * Create a user key.
     *
     * @return string Generated key.
     */
    public function create_key() {
        $this->delete_key();
        $this->userkey = create_user_key(
            self::CORE_USER_KEY_MANAGER_SCRIPT,
            $this->userid,
            $this->userid,
            $this->iprestriction,
            $this->validuntil
        );

        return $this->userkey;
    }

    /**
     * Delete all user keys.
     */
    public function delete_key() {
         delete_user_key(self::CORE_USER_KEY_MANAGER_SCRIPT, $this->userid);
    }

}
