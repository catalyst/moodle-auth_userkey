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
 * User key auth method.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use auth_userkey\core_userkey_manager;
use auth_userkey\userkey_manager_interface;

require_once($CFG->libdir.'/authlib.php');

/**
 * Shibboleth authentication plugin.
 */
class auth_plugin_userkey extends auth_plugin_base {

    const DEFAULT_MAPPING_FIELD = 'email';
    /**
     * User key manager.
     *
     * @var userkey_manager_interface
     */
    private $userkeymanager;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'userkey';
        $this->config = get_config('auth_userkey');

        //$this->config->mappingfield string
        //$this->config->iprestriction bool
        //$this->config->keylifetime string
        //$this->config->createuser bool
    }

    /**
     * Don't allow login using login form.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     *
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        return false;
    }

    /**
     * Don't store local passwords.
     *
     * @return bool True.
     */
    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is external.
     *
     * @return bool False.
     */
    public function is_internal() {
        return false;
    }

    /**
     * The plugin can't change the user's password.
     *
     * @return bool False.
     */
    public function can_change_password() {
        return false;
    }

    /**
     * @param \auth_userkey\userkey_manager_interface $keymanager
     */
    public function set_userkey_manager(userkey_manager_interface $keymanager) {
        $this->userkeymanager = $keymanager;
    }


    /**
     * @return string
     */
    public function get_mapping_field() {
        if (isset($this->config->mappingfield) && !empty($this->config->mappingfield)) {
            return $this->config->mappingfield;
        }

        return self::DEFAULT_MAPPING_FIELD;
    }

    public function get_login_url($user) {
        global $CFG, $DB;

        $mappingfield = $this->get_mapping_field();

        if (!isset($user[$mappingfield]) || empty($user[$mappingfield])) {
            throw new invalid_parameter_exception('User field "' . $mappingfield . '" is not set or empty.');
        }

//        $params = array($mappingfield => $user[$mappingfield], 'mnethostid' => $CFG->mnet_localhost_id);
//
//        if (!$user = $DB->get_record('user', $params) ) {
//            throw new invalid_parameter_exception('User is not exist');
//        }
//
//        if (!isset($this->userkeymanager)) {
//            $keymanager = new auth_userkey\core_userkey_manager($user->id, $this->config);
//            $this->set_userkey_manager($keymanager);
//        }
//
//        $userkey = $this->userkeymanager->create_key();
//
//        return $CFG->wwwroot . '/auth/userkey/login.php?key=' . $userkey;
    }

}
