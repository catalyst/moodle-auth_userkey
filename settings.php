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
 * Admin settings and defaults
 *
 * @package auth_userkey
 * @copyright  2017 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $yesno = array(get_string('no'), get_string('yes'));
    $fields = get_auth_plugin('userkey')->get_allowed_mapping_fields();

    $settings->add(new admin_setting_configselect('auth_userkey/mappingfield',
        new lang_string('mappingfield', 'auth_userkey'),
        new lang_string('mappingfield_desc', 'auth_userkey'), 0, $fields));

    $settings->add(new admin_setting_configtext('auth_userkey/keylifetime', get_string('keylifetime', 'auth_userkey'),
            get_string('keylifetime_desc', 'auth_userkey', 'auth'),
            '60', PARAM_INT));

    $settings->add(new admin_setting_configselect('auth_userkey/iprestriction',
            new lang_string('iprestriction', 'auth_userkey'),
            new lang_string('iprestriction_desc', 'auth_userkey'), 0, $yesno));

    $settings->add(new admin_setting_configtext('auth_userkey/ipwhitelist', get_string('ipwhitelist', 'auth_userkey'),
            get_string('ipwhitelist_desc', 'auth_userkey', 'auth'),
            '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('auth_userkey/redirecturl', get_string('redirecturl', 'auth_userkey'),
            get_string('redirecturl_desc', 'auth_userkey', 'auth'),
            '', PARAM_URL));

    $settings->add(new admin_setting_configtext('auth_userkey/ssourl', get_string('ssourl', 'auth_userkey'),
            get_string('ssourl_desc', 'auth_userkey', 'auth'),
            '', PARAM_URL));

    $settings->add(new admin_setting_configselect('auth_userkey/createuser',
            new lang_string('createuser', 'auth_userkey'),
            new lang_string('createuser_desc', 'auth_userkey'), 0, $yesno));

    $settings->add(new admin_setting_configselect('auth_userkey/updateuser',
            new lang_string('updateuser', 'auth_userkey'),
            new lang_string('updateuser_desc', 'auth_userkey'), 0, $yesno));
}
