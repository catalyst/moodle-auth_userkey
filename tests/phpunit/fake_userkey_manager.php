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

namespace auth_userkey;

/**
 * Fake userkey manager for testing.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fake_userkey_manager implements userkey_manager_interface {

    /**
     * Create key.
     *
     * @param int $userid user ID.
     * @param null $allowedips Allowed IPs.
     *
     * @return string
     */
    public function create_key($userid, $allowedips = null) {
        return 'FaKeKeyFoRtEsTiNg';
    }

    /**
     * Delete keys for a user.
     *
     * @param int $userid User ID to delete keys for.
     */
    public function delete_keys($userid) {
    }

    /**
     * Validate provided key.
     *
     * @param string $keyvalue Key to validate.
     *
     * @return object|void
     */
    public function validate_key($keyvalue) {
    }
}
