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
 * Upgrade script.
 *
 * @package    auth_userkey
 * @copyright  2018 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_auth_userkey_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2018050200) {
        // Confirm all previously created users.
        $DB->execute("UPDATE {user} SET confirmed=? WHERE auth=?", array(1, 'userkey'));
        upgrade_plugin_savepoint(true, 2018050200, 'auth', 'userkey');
    }

    return true;
}
