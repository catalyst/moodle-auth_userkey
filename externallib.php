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
 * Webservices for auth_userkey.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/auth/userkey/auth.php");

class auth_userkey_external extends external_api {

    public function request_login_url_parameters() {
        // TODO based on settings set required parameter for one of the following: username, email, idnumber.
        return new external_function_parameters(
            array(
                'user' => new external_single_structure(
                    array(
                        'username' => new external_value(
                            PARAM_USERNAME,
                            'Username policy is defined in Moodle security config.'
                        ),
                        'email' => new external_value(
                            PARAM_EMAIL,
                            'A valid and unique email address'
                        ),
                        'idnumber' => new external_value(
                            PARAM_RAW,
                            'An arbitrary ID code number perhaps from the institution'
                        ),
                        'firstname' => new external_value(
                            PARAM_NOTAGS,
                            'The first name(s) of the user',
                            VALUE_OPTIONAL
                        ),
                        'lastname' => new external_value(
                            PARAM_NOTAGS,
                            'The family name of the user',
                            VALUE_OPTIONAL
                        ),
                        'customfields' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'type'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the custom field'),
                                    'value' => new external_value(PARAM_RAW, 'The value of the custom field')
                                )
                            ), 'User custom fields (also known as user profile fields)', VALUE_OPTIONAL
                        ),
                    )
                )
            )
        );
    }

    public function request_login_url($user) {

        $auth = get_auth_plugin('auth_userkey');
        $loginurl = $auth->get_login_url($user);

        return array(
            'loginurl' => $loginurl,
        );
    }

    public static function request_login_url_returns() {
        return new external_single_structure(
            array(
                'loginurl' => new external_value(PARAM_URL, 'Login URL for a user to log in'),
            )
        );
    }

}
