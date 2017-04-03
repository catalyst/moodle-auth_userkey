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
 * Key manager interface.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_userkey;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface userkey_manager_interface describes key manager behaviour.
 *
 * @package auth_userkey
 */
interface userkey_manager_interface {
    /**
     * Create a user key.
     *
     * @param int $userid User ID.
     * @param null|array $allowedips A list of allowed ips for this key.
     *
     * @return string Generated key.
     */
    public function create_key($userid, $allowedips = null);

    /**
     * Delete all keys for a specific user.
     *
     * @param int $userid User ID.
     */
    public function delete_keys($userid);

    /**
     * Validates key and returns key data object if valid.
     *
     * @param string $keyvalue Key value.
     *
     * @return object Key object including userid property.
     *
     * @throws \moodle_exception If provided key is not valid.
     */
    public function validate_key($keyvalue);

}