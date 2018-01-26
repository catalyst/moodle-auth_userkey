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
 * Settings for the plugin.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 *             2017 Juan Carrera (carreraj@unizar.es)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$yesno = array(new lang_string('no'), new lang_string('yes'));
$fields = get_auth_plugin('userkey')->get_allowed_mapping_fields();

if ($ADMIN->fulltree) {
    // Some array options.
    $yesno = [
        new lang_string('no'),
        new lang_string('yes'),
    ];
    $fields = get_auth_plugin('userkey')->get_allowed_mapping_fields();

    // Introductory explanation.
    $settings->add(new admin_setting_heading(
        'auth_userkey/pluginname',
        '',
        new lang_string('auth_userkeydescription', 'auth_userkey')
        )
    );

    // Field used for mapping moodle user.
    $settings->add(new admin_setting_configselect(
            'auth_userkey/mappingfield',
            new lang_string('mappingfield', 'auth_userkey'),
            new lang_string('mappingfield_desc', 'auth_userkey'),
            0,
            $fields
        )
    );

    // Keylifetime: defaults to 60 seconds.
    $settings->add(
        new admin_setting_configtext(
            'auth_userkey/keylifetime',
            new lang_string('keylifetime', 'auth_userkey'),
            new lang_string('keylifetime_desc', 'auth_userkey'),
            '60'
        )
    );

    // Activate ip restriction?
    $settings->add(new admin_setting_configselect(
            'auth_userkey/iprestriction',
            new lang_string('iprestriction', 'auth_userkey'),
            new lang_string('iprestriction_desc', 'auth_userkey'),
            0,
            $yesno
        )
    );

    // IPs white list.
    $settings->add(
        new admin_setting_configtext(
            'auth_userkey/ipwhitelist',
            new lang_string('ipwhitelist', 'auth_userkey'),
            new lang_string('ipwhitelist_desc', 'auth_userkey'),
            ''
        )
    );

    // Redirect Url on logout.
    $settings->add(
        new admin_setting_configtext(
            'auth_userkey/redirecturl',
            new lang_string('redirecturl', 'auth_userkey'),
            new lang_string('redirecturl_desc', 'auth_userkey'),
            ''
        )
    );

    // SSO Url.
    $settings->add(
        new admin_setting_configtext(
            'auth_userkey/ssourl',
            new lang_string('ssourl', 'auth_userkey'),
            new lang_string('ssourl_desc', 'auth_userkey'),
            ''
        )
    );

    // Create user?
    $settings->add(new admin_setting_configselect(
            'auth_userkey/createuser',
            new lang_string('createuser', 'auth_userkey'),
            new lang_string('createuser_desc', 'auth_userkey'),
            0,
            $yesno
        )
    );

    // Update user?
    $settings->add(new admin_setting_configselect(
            'auth_userkey/updateuser',
            new lang_string('updateuser', 'auth_userkey'),
            new lang_string('updateuser_desc', 'auth_userkey'),
            0,
            $yesno
        )
    );

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('userkey');
    display_auth_lock_options(
        $settings,
        $authplugin->authtype,
        $authplugin->userfields,
        new lang_string('auth_fieldlocks_help', 'auth'),
        false,
        false
    );
}