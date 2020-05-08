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
 * Tests for core_userkey_manager class.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use auth_userkey\core_userkey_manager;

/**
 * Tests for core_userkey_manager class.
 *
 * Key validation is fully covered in auth_plugin_test.php file.
 * TODO: write tests for validate_key() function.
 *
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_userkey_manager_testcase extends advanced_testcase {
    /**
     * Test user object.
     * @var
     */
    protected $user;

    /**
     * Test config object.
     * @var
     */
    protected $config;

    /**
     * Initial set up.
     */
    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest();
        $CFG->getremoteaddrconf = GETREMOTEADDR_SKIP_HTTP_X_FORWARDED_FOR;
        $this->user = self::getDataGenerator()->create_user();
        $this->config = new stdClass();
    }

    /**
     * Test that core_userkey_manager implements userkey_manager_interface interface.
     */
    public function test_implements_userkey_manager_interface() {
        $manager = new core_userkey_manager($this->config);

        $expected = 'auth_userkey\userkey_manager_interface';
        $this->assertInstanceOf($expected, $manager);
    }

    /**
     * Test that key gets created correctly if config option iprestriction is not set.
     */
    public function test_create_correct_key_if_iprestriction_is_not_set() {
        global $DB;

        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals(null, $actualkey->iprestriction);
        $this->assertEquals(time() + 60, $actualkey->validuntil);
    }

    /**
     * Test that key gets created correctly if config option iprestriction is set to true.
     */
    public function test_create_correct_key_if_iprestriction_is_true() {
        global $DB;

        $this->config->iprestriction = true;
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals('192.168.1.1', $actualkey->iprestriction);
        $this->assertEquals(time() + 60, $actualkey->validuntil);
    }

    /**
     * Test that key gets created correctly if config option iprestriction is set to true and we set allowedips.
     */
    public function test_create_correct_key_if_iprestriction_is_true_and_we_set_allowedips() {
        global $DB;

        $this->config->iprestriction = true;
        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id, '192.168.1.3');

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals('192.168.1.3', $actualkey->iprestriction);
        $this->assertEquals(time() + 60, $actualkey->validuntil);
    }

    /**
     * Test that key gets created correctly if config option iprestriction is set to false.
     */
    public function test_create_correct_key_if_iprestriction_is_false() {
        global $DB;

        $this->config->iprestriction = false;
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals(null, $actualkey->iprestriction);
        $this->assertEquals(time() + 60, $actualkey->validuntil);
    }

    /**
     * Test that IP address mismatch exception gets thrown if incorrect IP and outside whitelist.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Client IP address mismatch
     */
    public function test_exception_if_ip_is_outside_whitelist() {
        global $DB;

        $this->config->iprestriction = true;
        $this->config->ipwhitelist = '10.0.0.0/8;172.16.0.0/12;192.168.0.0/16';

        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id, '193.168.1.1');

        $_SERVER['HTTP_CLIENT_IP'] = '193.168.1.2';

        $manager->validate_key($value);
    }

    /**
     * Test that IP address mismatch exception gets thrown if incorrect IP and outside whitelist.
     */
    public function test_create_correct_key_if_ip_correct_not_whitelisted_and_whitelist_set() {
        global $DB;

        $this->config->iprestriction = true;

        $this->config->ipwhitelist = '10.0.0.0/8;172.16.0.0/12;192.168.0.0/16';

        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id, '193.168.1.1');

        $_SERVER['HTTP_CLIENT_IP'] = '193.168.1.1';

        $key = $manager->validate_key($value);
        $this->assertEquals($this->user->id, $key->userid);
    }

    /**
     * Test that key is accepted if incorrect IP and within whitelist.
     */
    public function test_create_correct_key_if_ip_is_whitelisted() {
        global $DB;

        $this->config->iprestriction = true;

        $this->config->ipwhitelist = '10.0.0.0/8;172.16.0.0/12;192.168.0.0/16';

        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id, '192.168.1.1');

        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';

        $key = $manager->validate_key($value);
        $this->assertEquals($this->user->id, $key->userid);
    }

    /**
     * Test that key gets created correctly if config option iprestriction is set to false and we set allowedips.
     */
    public function test_create_correct_key_if_iprestriction_is_falseand_we_set_allowedips() {
        global $DB;

        $this->config->iprestriction = false;
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id, '192.168.1.1');

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals(null, $actualkey->iprestriction);
        $this->assertEquals(time() + 60, $actualkey->validuntil);
    }

    /**
     * Test that key gets created correctly if config option iprestriction is set to a string.
     */
    public function test_create_correct_key_if_iprestriction_is_string() {
        global $DB;

        $this->config->iprestriction = 'string';
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals('192.168.1.1', $actualkey->iprestriction);
        $this->assertEquals(time() + 60, $actualkey->validuntil);
    }

    /**
     * Test that key gets created correctly if config option keylifetime is not set.
     */
    public function test_create_correct_key_if_keylifetime_is_not_set() {
        global $DB;

        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals(null, $actualkey->iprestriction);
        $this->assertEquals(time() + 60, $actualkey->validuntil);
    }

    /**
     * Test that key gets created correctly if config option keylifetime is set to integer.
     */
    public function test_create_correct_key_if_keylifetime_is_set_to_integer() {
        global $DB;

        $this->config->keylifetime = 3000;

        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals(null, $actualkey->iprestriction);
        $this->assertEquals(time() + 3000, $actualkey->validuntil);

    }

    /**
     * Test that key gets created correctly if config option keylifetime is set to a string.
     */
    public function test_create_correct_key_if_keylifetime_is_set_to_string() {
        global $DB;

        $this->config->keylifetime = '3000';

        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));

        $this->assertEquals($value, $actualkey->value);
        $this->assertEquals($this->user->id, $actualkey->userid);
        $this->assertEquals('auth/userkey', $actualkey->script);
        $this->assertEquals($this->user->id, $actualkey->instance);
        $this->assertEquals(null, $actualkey->iprestriction);
        $this->assertEquals(time() + 3000, $actualkey->validuntil);

    }

    /**
     * Test that we can delete created key.
     */
    public function test_can_delete_created_key() {
        global $DB;

        $manager = new core_userkey_manager($this->config);
        $value = $manager->create_key($this->user->id);

        $keys = $DB->get_records('user_private_key', array('userid' => $this->user->id));
        $this->assertEquals(1, count($keys));

        $manager->delete_keys($this->user->id);

        $keys = $DB->get_records('user_private_key', array('userid' => $this->user->id));
        $this->assertEquals(0, count($keys));
    }

    /**
     * Test that we can delete all existing keys.
     */
    public function test_can_delete_all_existing_keys() {
        global $DB;

        $manager = new core_userkey_manager($this->config);

        create_user_key('auth/userkey', $this->user->id);
        create_user_key('auth/userkey', $this->user->id);
        create_user_key('auth/userkey', $this->user->id);

        $keys = $DB->get_records('user_private_key', array('userid' => $this->user->id));
        $this->assertEquals(3, count($keys));

        $manager->delete_keys($this->user->id);

        $keys = $DB->get_records('user_private_key', array('userid' => $this->user->id));
        $this->assertEquals(0, count($keys));
    }

    /**
     * Test that we create only one key.
     */
    public function test_create_only_one_key() {
        global $DB;

        $manager = new core_userkey_manager($this->config);

        create_user_key('auth/userkey', $this->user->id);
        create_user_key('auth/userkey', $this->user->id);
        create_user_key('auth/userkey', $this->user->id);

        $keys = $DB->get_records('user_private_key', array('userid' => $this->user->id));
        $this->assertEquals(3, count($keys));

        $manager->create_key($this->user->id);
        $keys = $DB->get_records('user_private_key', array('userid' => $this->user->id));
        $this->assertEquals(1, count($keys));
    }
}
