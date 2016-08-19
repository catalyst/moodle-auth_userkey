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
 * Strings for auth_userkey.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'User key authentication';
$string['auth_userkeydescription'] = ''; // TODO: create descripton.
$string['mappingfield'] = 'Mapping field';
$string['mappingfield_desc'] = 'This user field will be used to find relevant user in the LMS.';
$string['iprestriction'] = 'IP restriction';
$string['iprestriction_desc'] = 'If enabled, a user should use the same remote ip to get login URL and logging to to LMS.';
$string['keylifetime'] = 'User key life time';
$string['keylifetime_desc'] = 'Life time in seconds of the each user login key.';
$string['incorrectkeylifetime'] = 'User key life time should be a number';
$string['createuser'] = 'Crete user?';
$string['createuser_desc'] = 'If enabled, a new user will be created if fail to find one in LMS.';
$string['redirecturl'] = 'Logout redirect URL';
$string['redirecturl_desc'] = 'Optionally you can redirect users to this URL after they logged out from LMS.';
$string['incorrectredirecturl'] = 'You should provide valid URL';
$string['userkey:generatekey'] = 'Generate login user key';
$string['pluginisdisabled'] = 'The userkey authentication plugin is disabled.';