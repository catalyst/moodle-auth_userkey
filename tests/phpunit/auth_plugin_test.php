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
 * Tests for auth_plugin_userkey class.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for auth_plugin_userkey class.
 *
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_userkey_testcase extends advanced_testcase {
    /**
     * An instance of auth_plugin_userkey class.
     * @var auth_plugin_userkey
     */
    protected $auth;

    /**
     * User object.
     * @var
     */
    protected $user;

    /**
     * Initial set up.
     */
    public function setUp() {
        global $CFG;

        require_once($CFG->libdir . "/externallib.php");
        require_once($CFG->dirroot . '/auth/userkey/tests/phpunit/fake_userkey_manager.php');
        require_once($CFG->dirroot . '/auth/userkey/auth.php');
        require_once($CFG->dirroot . '/user/lib.php');

        parent::setUp();

        $this->resetAfterTest();
        $CFG->getremoteaddrconf = GETREMOTEADDR_SKIP_HTTP_X_FORWARDED_FOR;
        $this->auth = new auth_plugin_userkey();
        $this->user = self::getDataGenerator()->create_user();
    }

    /**
     * A helper function to create TestKey.
     *
     * @param array $record Key record.
     */
    protected function create_user_private_key(array $record = []) {
        global $DB;

        $record = (object)$record;

        if (!isset($record->value)) {
            $record->value = 'TestKey';
        }

        if (!isset($record->userid)) {
            $record->userid = $this->user->id;
        }

        if (!isset($record->userid)) {
            $record->instance = $this->user->id;
        }

        if (!isset($record->iprestriction)) {
            $record->iprestriction = null;
        }
        if (!isset($record->validuntil)) {
            $record->validuntil = time() + 300;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }

        $record->script = 'auth/userkey';

        $DB->insert_record('user_private_key', $record);
    }

    /**
     * Test that users can't login using login form.
     */
    public function test_users_can_not_login_using_login_form() {
        $user = new stdClass();
        $user->auth = 'userkey';
        $user->username = 'username';
        $user->password = 'correctpassword';

        self::getDataGenerator()->create_user($user);

        $this->assertFalse($this->auth->user_login('username', 'correctpassword'));
        $this->assertFalse($this->auth->user_login('username', 'incorrectpassword'));
    }

    /**
     * Test that the plugin doesn't allow to store users passwords.
     */
    public function test_auth_plugin_does_not_allow_to_store_passwords() {
        $this->assertTrue($this->auth->prevent_local_passwords());
    }

    /**
     * Test that the plugin is external.
     */
    public function test_auth_plugin_is_external() {
        $this->assertFalse($this->auth->is_internal());
    }

    /**
     * Test that the plugin doesn't allow users to change the passwords.
     */
    public function test_auth_plugin_does_not_allow_to_change_passwords() {
        $this->assertFalse($this->auth->can_change_password());
    }

    /**
     * Test that default mapping field gets returned correctly.
     */
    public function test_get_default_mapping_field() {
        $expected = 'email';
        $actual = $this->auth->get_mapping_field();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that logout page hook sets global redirect variable correctly.
     */
    public function test_logoutpage_hook_sets_global_redirect_correctly() {
        global $redirect, $SESSION;

        $this->auth->logoutpage_hook();
        $this->assertEquals('', $redirect);

        $SESSION->userkey = true;
        $this->auth = new auth_plugin_userkey();
        $this->auth->logoutpage_hook();
        $this->assertEquals('', $redirect);

        unset($SESSION->userkey);
        set_config('redirecturl', 'http://example.com', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();
        $this->auth->logoutpage_hook();
        $this->assertEquals('', $redirect);

        $SESSION->userkey = true;
        set_config('redirecturl', 'http://example.com', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();
        $this->auth->logoutpage_hook();
        $this->assertEquals('http://example.com', $redirect);
    }

    /**
     * Test that configured mapping field gets returned correctly.
     */
    public function test_get_mapping_field() {
        set_config('mappingfield', 'username', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $expected = 'username';
        $actual = $this->auth->get_mapping_field();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that auth plugin throws correct exception if default mapping field is not provided.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Invalid parameter value detected (Required field "email" is not set or empty.)
     */
    public function test_throwing_exception_if_default_mapping_field_is_not_provided() {
        $user = array();
        $actual = $this->auth->get_login_url($user);
    }

    /**
     * Test that auth plugin throws correct exception if username mapping field is not provided, but set in configs.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Invalid parameter value detected (Required field "username" is not set or empty.)
     */
    public function test_throwing_exception_if_mapping_field_username_is_not_provided() {
        $user = array();
        set_config('mappingfield', 'username', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $actual = $this->auth->get_login_url($user);
    }

    /**
     * Test that auth plugin throws correct exception if idnumber mapping field is not provided, but set in configs.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Invalid parameter value detected (Required field "idnumber" is not set or empty.)
     */
    public function test_throwing_exception_if_mapping_field_idnumber_is_not_provided() {
        $user = array();
        set_config('mappingfield', 'idnumber', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $actual = $this->auth->get_login_url($user);
    }

    /**
     * Test that auth plugin throws correct exception if we trying to request not existing user.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Invalid parameter value detected (User is not exist)
     */
    public function test_throwing_exception_if_user_is_not_exist() {
        $user = array();
        $user['email'] = 'notexists@test.com';

        $actual = $this->auth->get_login_url($user);
    }

    /**
     * Test that auth plugin throws correct exception if we trying to request user,
     * but ip field is not set and iprestriction is enabled.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Invalid parameter value detected (Required parameter "ip" is not set.)
     */
    public function test_throwing_exception_if_iprestriction_is_enabled_but_ip_is_missing_in_data() {
        $user = array();
        $user['email'] = 'exists@test.com';
        set_config('iprestriction', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $actual = $this->auth->get_login_url($user);
    }

    /**
     * Test that we can request a user provided user data as an array.
     */
    public function test_return_correct_login_url_if_user_is_array() {
        global $CFG;

        $user = array();
        $user['username'] = 'username';
        $user['email'] = 'exists@test.com';

        self::getDataGenerator()->create_user($user);

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=FaKeKeyFoRtEsTiNg';
        $actual = $this->auth->get_login_url($user);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that we can request a user provided user data as an object.
     */
    public function test_return_correct_login_url_if_user_is_object() {
        global $CFG;

        $user = new stdClass();
        $user->username = 'username';
        $user->email = 'exists@test.com';

        self::getDataGenerator()->create_user($user);

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=FaKeKeyFoRtEsTiNg';
        $actual = $this->auth->get_login_url($user);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that we can request a user provided user data as an object.
     */
    public function test_return_correct_login_url_if_iprestriction_is_enabled_and_data_is_correct() {
        global $CFG;

        $user = new stdClass();
        $user->username = 'username';
        $user->email = 'exists@test.com';
        $user->ip = '192.168.1.1';

        self::getDataGenerator()->create_user($user);

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=FaKeKeyFoRtEsTiNg';
        $actual = $this->auth->get_login_url($user);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that we can request a key for a new user.
     */
    public function test_return_correct_login_url_and_create_new_user() {
        global $CFG, $DB;

        set_config('createuser', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $user = new stdClass();
        $user->username = 'username';
        $user->email = 'username@test.com';
        $user->firstname = 'user';
        $user->lastname = 'name';
        $user->ip = '192.168.1.1';

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=FaKeKeyFoRtEsTiNg';
        $actual = $this->auth->get_login_url($user);

        $this->assertEquals($expected, $actual);

        $userrecord = $DB->get_record('user', ['username' => 'username']);
        $this->assertEquals($user->email, $userrecord->email);
        $this->assertEquals($user->firstname, $userrecord->firstname);
        $this->assertEquals($user->lastname, $userrecord->lastname);
        $this->assertEquals(1, $userrecord->confirmed);
        $this->assertEquals('userkey', $userrecord->auth);
    }

    /**
     * Test that we can request a key for a new user.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Unable to create user, missing value(s): username,firstname,lastname
     */

    public function test_missing_data_to_create_user() {
        global $CFG, $DB;

        set_config('createuser', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $user = new stdClass();
        $user->email = 'username@test.com';
        $user->ip = '192.168.1.1';

        $this->auth->get_login_url($user);
    }

    /**
     * Test that when we attempt to create a new user duplicate usernames are caught.
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Username already exists: username
     */
    public function test_create_refuse_duplicate_username() {
        set_config('createuser', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $originaluser = new stdClass();
        $originaluser->username = 'username';
        $originaluser->email = 'username@test.com';
        $originaluser->firstname = 'user';
        $originaluser->lastname = 'name';
        $originaluser->city = 'brighton';
        $originaluser->ip = '192.168.1.1';

        self::getDataGenerator()->create_user($originaluser);

        $duplicateuser = clone($originaluser);
        $duplicateuser->email = 'duplicateuser@test.com';

        $this->auth->get_login_url($duplicateuser);
    }

    /**
     * Test that when we attempt to create a new user duplicate emails are caught.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Email address already exists: username@test.com
     */
    public function test_create_refuse_duplicate_email() {
        set_config('createuser', true, 'auth_userkey');
        set_config('mappingfield', 'username', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $originaluser = new stdClass();
        $originaluser->username = 'username';
        $originaluser->email = 'username@test.com';
        $originaluser->firstname = 'user';
        $originaluser->lastname = 'name';
        $originaluser->city = 'brighton';
        $originaluser->ip = '192.168.1.1';

        self::getDataGenerator()->create_user($originaluser);

        $duplicateuser = clone($originaluser);
        $duplicateuser->username = 'duplicateuser';

        $this->auth->get_login_url($duplicateuser);
    }

    /**
     * Test that we can request a key for an existing user and update their details.
     */
    public function test_return_correct_login_url_and_update_user() {
        global $CFG, $DB;

        set_config('updateuser', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        $originaluser = new stdClass();
        $originaluser->username = 'username';
        $originaluser->email = 'username@test.com';
        $originaluser->firstname = 'user';
        $originaluser->lastname = 'name';
        $originaluser->city = 'brighton';
        $originaluser->ip = '192.168.1.1';

        self::getDataGenerator()->create_user($originaluser);

        $user = new stdClass();
        $user->username = 'usernamechanged';
        $user->email = 'username@test.com';
        $user->firstname = 'userchanged';
        $user->lastname = 'namechanged';
        $user->ip = '192.168.1.1';

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=FaKeKeyFoRtEsTiNg';
        $actual = $this->auth->get_login_url($user);

        $this->assertEquals($expected, $actual);

        $userrecord = $DB->get_record('user', ['email' => $user->email]);
        $this->assertEquals($user->username, $userrecord->username);
        $this->assertEquals($user->firstname, $userrecord->firstname);
        $this->assertEquals($user->lastname, $userrecord->lastname);
        $this->assertEquals($originaluser->city, $userrecord->city);
        $this->assertEquals('userkey', $userrecord->auth);
    }

    /**
     * Test that when we attempt to update a user duplicate emails are caught.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Email address already exists: trytoduplicate@test.com
     */
    public function test_update_refuse_duplicate_email() {
        set_config('updateuser', true, 'auth_userkey');
        set_config('mappingfield', 'username', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        self::getDataGenerator()->create_user(['email' => 'trytoduplicate@test.com']);
        self::getDataGenerator()->create_user(['username' => 'username']);

        $originaluser = new stdClass();
        $originaluser->username = 'username';
        $originaluser->email = 'trytoduplicate@test.com';
        $originaluser->firstname = 'user';
        $originaluser->lastname = 'name';
        $originaluser->city = 'brighton';
        $originaluser->ip = '192.168.1.1';

        $this->auth->get_login_url($originaluser);
    }

    /**
     * Test that when we attempt to update a user duplicate usernames are caught.
     *
     * @expectedException \invalid_parameter_exception
     * @expectedExceptionMessage Username already exists: trytoduplicate
     */
    public function test_update_refuse_duplicate_username() {
        set_config('updateuser', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $userkeymanager = new \auth_userkey\fake_userkey_manager();
        $this->auth->set_userkey_manager($userkeymanager);

        self::getDataGenerator()->create_user(['username' => 'trytoduplicate']);
        self::getDataGenerator()->create_user(['email' => 'username@test.com']);

        $originaluser = new stdClass();
        $originaluser->username = 'trytoduplicate';
        $originaluser->email = 'username@test.com';
        $originaluser->firstname = 'user';
        $originaluser->lastname = 'name';
        $originaluser->city = 'brighton';
        $originaluser->ip = '192.168.1.1';

        $this->auth->get_login_url($originaluser);
    }

    /**
     * Test that we can get login url if we do not use fake keymanager.
     */
    public function test_return_correct_login_url_if_user_is_object_using_default_keymanager() {
        global $DB, $CFG;

        $user = array();
        $user['username'] = 'username';
        $user['email'] = 'exists@test.com';

        $user = self::getDataGenerator()->create_user($user);

        create_user_key('auth/userkey', $user->id);
        create_user_key('auth/userkey', $user->id);
        create_user_key('auth/userkey', $user->id);
        $keys = $DB->get_records('user_private_key', array('userid' => $user->id));

        $this->assertEquals(3, count($keys));

        $actual = $this->auth->get_login_url($user);

        $keys = $DB->get_records('user_private_key', array('userid' => $user->id));
        $this->assertEquals(1, count($keys));

        $actualkey = $DB->get_record('user_private_key', array('userid' => $user->id));

        $expected = $CFG->wwwroot . '/auth/userkey/login.php?key=' . $actualkey->value;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that we can return correct allowed mapping fields.
     */
    public function test_get_allowed_mapping_fields_list() {
        $expected = array(
            'username' => 'Username',
            'email' => 'Email address',
            'idnumber' => 'ID number',
        );

        $actual = $this->auth->get_allowed_mapping_fields();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that we can get correct request parameters based on the plugin configuration.
     */
    public function test_get_request_login_url_user_parameters_based_on_plugin_config() {
        // Check email as it should be set by default.
        $expected = array(
            'email' => new external_value(
                PARAM_EMAIL,
                'A valid email address'
            ),
        );

        $actual = $this->auth->get_request_login_url_user_parameters();
        $this->assertEquals($expected, $actual);

        // Check username.
        set_config('mappingfield', 'username', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $expected = array(
            'username' => new external_value(
                PARAM_USERNAME,
                'Username'
            ),
        );

        $actual = $this->auth->get_request_login_url_user_parameters();
        $this->assertEquals($expected, $actual);

        // Check idnumber.
        set_config('mappingfield', 'idnumber', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $expected = array(
            'idnumber' => new external_value(
                PARAM_RAW,
                'An arbitrary ID code number perhaps from the institution'
            ),
        );

        $actual = $this->auth->get_request_login_url_user_parameters();
        $this->assertEquals($expected, $actual);

        // Check some junk field name.
        set_config('mappingfield', 'junkfield', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $expected = array();

        $actual = $this->auth->get_request_login_url_user_parameters();
        $this->assertEquals($expected, $actual);

        // Check IP if iprestriction disabled.
        set_config('iprestriction', false, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();
        $expected = array();
        $actual = $this->auth->get_request_login_url_user_parameters();
        $this->assertEquals($expected, $actual);

        // Check IP if iprestriction enabled.
        set_config('iprestriction', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();
        $expected = array(
            'ip' => new external_value(
                PARAM_HOST,
                'User IP address'
            ),
        );
        $actual = $this->auth->get_request_login_url_user_parameters();
        $this->assertEquals($expected, $actual);

        // Check IP if createuser enabled.
        set_config('createuser', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();
        $expected = array(
            'ip' => new external_value(PARAM_HOST, 'User IP address'),
            'firstname' => new external_value(PARAM_NOTAGS, 'The first name(s) of the user', VALUE_OPTIONAL),
            'lastname'  => new external_value(PARAM_NOTAGS, 'The family name of the user', VALUE_OPTIONAL),
            'email'     => new external_value(PARAM_RAW_TRIMMED, 'A valid and unique email address', VALUE_OPTIONAL),
            'username'  => new external_value(PARAM_USERNAME, 'A valid and unique username', VALUE_OPTIONAL),
        );
        $actual = $this->auth->get_request_login_url_user_parameters();
        $this->assertEquals($expected, $actual);
        set_config('createuser', false, 'auth_userkey');

        // Check IP if updateuser enabled.
        set_config('updateuser', true, 'auth_userkey');
        $this->auth = new auth_plugin_userkey();
        $expected = array(
            'ip' => new external_value(PARAM_HOST, 'User IP address'),
            'firstname' => new external_value(PARAM_NOTAGS, 'The first name(s) of the user', VALUE_OPTIONAL),
            'lastname'  => new external_value(PARAM_NOTAGS, 'The family name of the user', VALUE_OPTIONAL),
            'email'     => new external_value(PARAM_RAW_TRIMMED, 'A valid and unique email address', VALUE_OPTIONAL),
            'username'  => new external_value(PARAM_USERNAME, 'A valid and unique username', VALUE_OPTIONAL),
        );
        $actual = $this->auth->get_request_login_url_user_parameters();
        $this->assertEquals($expected, $actual);
        set_config('updateuser', false, 'auth_userkey');
    }

    /**
     * Data provider for testing URL validation functions.
     *
     * @return array First element URL, the second URL is error message. Empty error massage means no errors.
     */
    public function url_data_provider() {
        return array(
            array('', ''),
            array('http://google.com/', ''),
            array('https://google.com', ''),
            array('http://some.very.long.and.silly.domain/with/a/path/', ''),
            array('http://0.255.1.1/numericip.php', ''),
            array('http://0.255.1.1/numericip.php?test=1&id=2', ''),
            array('/just/a/path', 'You should provide valid URL'),
            array('random string', 'You should provide valid URL'),
            array(123456, 'You should provide valid URL'),
            array('php://google.com', 'You should provide valid URL'),
        );
    }

    /**
     * Test required parameter exception gets thrown id try to login, but key is not set.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage A required parameter (key) was missing
     */
    public function test_required_parameter_exception_thrown_if_key_not_set() {
        $this->auth->user_login_userkey();
    }

    /**
     * Test that incorrect key exception gets thrown if a key is incorrect.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Incorrect key
     */
    public function test_invalid_key_exception_thrown_if_invalid_key() {
        $_POST['key'] = 'InvalidKey';
        $this->auth->user_login_userkey();
    }

    /**
     * Test that expired key exception gets thrown if a key is expired.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Expired key
     */
    public function test_expired_key_exception_thrown_if_expired_key() {
        $this->create_user_private_key(['validuntil' => time() - 3000]);

        $_POST['key'] = 'TestKey';
        $this->auth->user_login_userkey();
    }

    /**
     * Test that IP address mismatch exception gets thrown if incorrect IP.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Client IP address mismatch
     */
    public function test_ipmismatch_exception_thrown_if_ip_is_incorrect() {
        $this->create_user_private_key(['iprestriction' => '192.168.1.1']);

        $_POST['key'] = 'TestKey';
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
        $this->auth->user_login_userkey();
    }

    /**
     * Test that IP address mismatch exception gets thrown if incorrect IP and outside whitelist.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Client IP address mismatch
     */
    public function test_ipmismatch_exception_thrown_if_ip_is_outside_whitelist() {
        set_config('ipwhitelist', '10.0.0.0/8;172.16.0.0/12;192.168.0.0/16', 'auth_userkey');
        $this->create_user_private_key(['iprestriction' => '192.161.1.1']);

        $_POST['key'] = 'TestKey';
        $_SERVER['HTTP_CLIENT_IP'] = '192.161.1.2';
        $this->auth->user_login_userkey();
    }

    /**
     * Test that IP address mismatch exception gets thrown if user id is incorrect.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessageRegExp /Invalid user id/i
     */
    public function test_invalid_user_exception_thrown_if_user_is_invalid() {
        $this->create_user_private_key([
            'userid' => 777,
            'instance' => 777,
            'iprestriction' => '192.168.1.1',
        ]);

        $_POST['key'] = 'TestKey';
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $this->auth->user_login_userkey();
    }

    /**
     * Test that key gets removed after a user logged in.
     */
    public function test_that_key_gets_removed_after_user_logged_in() {
        global $DB;

        $this->create_user_private_key([
            'value' => 'RemoveKey',
            'iprestriction' => '192.168.1.1',
        ]);

        $_POST['key'] = 'RemoveKey';
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';

        try {
            // Using @ is the only way to test this. Thanks moodle!
            @$this->auth->user_login_userkey();
        } catch (moodle_exception $e) {
            $keyexists = $DB->record_exists('user_private_key', array('value' => 'RemoveKey'));
            $this->assertFalse($keyexists);
        }
    }

    /**
     * Test that a user logs in and gets redirected correctly.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Unsupported redirect to http://www.example.com/moodle detected, execution terminated.
     */
    public function test_that_user_logged_in_and_redirected() {
        global $CFG;

        $this->create_user_private_key();
        $CFG->wwwroot = 'http://www.example.com/moodle';
        $_POST['key'] = 'TestKey';
        @$this->auth->user_login_userkey();
    }

    /**
     * Test that a user logs in correctly.
     */
    public function test_that_user_logged_in_correctly() {
        global $USER, $SESSION;

        $this->create_user_private_key();

        $_POST['key'] = 'TestKey';

        try {
            // Using @ is the only way to test this. Thanks moodle!
            @$this->auth->user_login_userkey();
        } catch (moodle_exception $e) {
            $this->assertEquals($this->user->id, $USER->id);
            $this->assertSame(sesskey(), $USER->sesskey);
            $this->assertObjectHasAttribute('userkey', $SESSION);
        }
    }

    /**
     * Test that a user gets redirected to internal wantsurl URL successful log in.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Unsupported redirect to /course/index.php?id=12&key=134 detected, execution terminated.
     */
    public function test_that_user_gets_redirected_to_internal_wantsurl() {
        $this->create_user_private_key();
        $_POST['key'] = 'TestKey';
        $_POST['wantsurl'] = '/course/index.php?id=12&key=134';

        // Using @ is the only way to test this. Thanks moodle!
        @$this->auth->user_login_userkey();
    }

    /**
     * Test that a user gets redirected to external wantsurl URL successful log in.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Unsupported redirect to http://test.com/course/index.php?id=12&key=134 detected,
     * execution terminated.
     */
    public function test_that_user_gets_redirected_to_external_wantsurl() {
        $this->create_user_private_key();

        $_POST['key'] = 'TestKey';
        $_POST['wantsurl'] = 'http://test.com/course/index.php?id=12&key=134';

        // Using @ is the only way to test this. Thanks moodle!
        @$this->auth->user_login_userkey();
    }

    /**
     * Test that login hook redirects a user if skipsso not set and ssourl is set.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Unsupported redirect to http://google.com detected, execution terminated.
     */
    public function test_loginpage_hook_redirects_if_skipsso_not_set_and_ssourl_set() {
        global $SESSION;

        $SESSION->enrolkey_skipsso = 0;
        set_config('ssourl', 'http://google.com', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $this->auth->loginpage_hook();
    }

    /**
     * Test that login hook does not redirect a user if skipsso not set and ssourl is not set.
     */
    public function test_loginpage_hook_does_not_redirect_if_skipsso_not_set_and_ssourl_not_set() {
        global $SESSION;

        $SESSION->enrolkey_skipsso = 0;
        set_config('ssourl', '', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $this->assertTrue($this->auth->loginpage_hook());
    }

    /**
     * Test that login hook does not redirect a user if skipsso is set and ssourl is not set.
     */
    public function test_loginpage_hook_does_not_redirect_if_skipsso_set_and_ssourl_not_set() {
        global $SESSION;

        $SESSION->enrolkey_skipsso = 1;
        set_config('ssourl', '', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $this->assertTrue($this->auth->loginpage_hook());
    }

    /**
     * Test that pre login hook redirects a user if skipsso not set and ssourl is set.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Unsupported redirect to http://google.com detected, execution terminated.
     */
    public function test_pre_loginpage_hook_redirects_if_skipsso_not_set_and_ssourl_set() {
        global $SESSION;

        $SESSION->enrolkey_skipsso = 0;
        set_config('ssourl', 'http://google.com', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $this->auth->pre_loginpage_hook();
    }

    /**
     * Test that pre login hook does not redirect a user if skipsso is not set and ssourl is not set.
     */
    public function test_pre_loginpage_hook_does_not_redirect_if_skipsso_not_set_and_ssourl_not_set() {
        global $SESSION;

        $SESSION->enrolkey_skipsso = 0;
        set_config('ssourl', '', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $this->assertTrue($this->auth->pre_loginpage_hook());
    }

    /**
     * Test that login page hook does not redirect a user if skipsso is set and ssourl is not set.
     */
    public function test_pre_loginpage_hook_does_not_redirect_if_skipsso_set_and_ssourl_not_set() {
        global $SESSION;

        $SESSION->enrolkey_skipsso = 1;
        set_config('ssourl', '', 'auth_userkey');
        $this->auth = new auth_plugin_userkey();

        $this->assertTrue($this->auth->pre_loginpage_hook());
    }

    /**
     * Test that if one user logged, he will be logged out before a new one is authorised.
     */
    public function test_that_different_authorised_user_is_logged_out_and_new_one_logged_in() {
        global $USER, $SESSION;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertEquals($USER->id, $user->id);

        $this->create_user_private_key();

        $_POST['key'] = 'TestKey';

        try {
            // Using @ is the only way to test this. Thanks moodle!
            @$this->auth->user_login_userkey();
        } catch (moodle_exception $e) {
            $this->assertEquals($this->user->id, $USER->id);
            $this->assertSame(sesskey(), $USER->sesskey);
            $this->assertObjectHasAttribute('userkey', $SESSION);
        }
    }

    /**
     * Test that authorised user gets logged out when trying to logged in with invalid key.
     */
    public function test_if_invalid_key_authorised_user_gets_logged_out() {
        global $USER, $SESSION;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertEquals($USER->id, $user->id);

        $this->create_user_private_key();

        $_POST['key'] = 'Incorrect Key';

        try {
            // Using @ is the only way to test this. Thanks moodle!
            @$this->auth->user_login_userkey();
        } catch (moodle_exception $e) {
            $this->assertEquals('Incorrect key', $e->getMessage());
            $this->assertEmpty($USER->id);
            $this->assertEquals(new stdClass(), $SESSION);
        }
    }

    /**
     * Test if a user is logged in and tries to log in again it stays logged in.
     */
    public function test_that_already_logged_in_user_stays_logged_in() {
        global $DB, $USER, $SESSION;

        $this->setUser($this->user);
        $this->assertEquals($USER->id, $this->user->id);

        $this->create_user_private_key();

        $_POST['key'] = 'TestKey';

        try {
            // Using @ is the only way to test this. Thanks moodle!
            @$this->auth->user_login_userkey();
        } catch (moodle_exception $e) {
            $this->assertEquals($this->user->id, $USER->id);
            $this->assertSame(sesskey(), $USER->sesskey);
            $this->assertObjectNotHasAttribute('userkey', $SESSION);
            $keyexists = $DB->record_exists('user_private_key', array('value' => 'TestKey'));
            $this->assertFalse($keyexists);
        }
    }

    /**
     * Test when try to logout, but required return is not set.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage A required parameter (return) was missing
     */
    public function test_user_logout_userkey_when_required_return_not_set() {
        $this->auth->user_logout_userkey();
    }

    /**
     * Test when try to logout, but user is not logged in.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Unsupported redirect to http://google.com detected, execution terminated.
     */
    public function test_user_logout_userkey_when_user_is_not_logged_in() {
        $_POST['return'] = 'http://google.com';

        $this->auth->user_logout_userkey();
    }

    /**
     * Test when try to logout, but user logged in with different auth type.
     */
    public function test_user_logout_userkey_when_user_logged_in_with_different_auth() {
        global $USER;

        $_POST['return'] = 'http://google.com';

        $this->setUser($this->user);
        try {
            $this->auth->user_logout_userkey();
        } catch (moodle_exception $e) {
            $this->assertTrue(isloggedin());
            $this->assertEquals($USER->id, $this->user->id);
            $this->assertEquals(
                'Incorrect logout request',
                $e->getMessage()
            );
        }
    }

    /**
     * Test when try to logout, but user logged in with different auth type.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage A required parameter (return) was missing
     */
    public function test_user_logout_userkey_when_user_logged_in_but_return_not_set() {
        $this->setUser($this->user);
        $this->auth->user_logout_userkey();
    }

    /**
     * Test successful logout.
     */
    public function test_user_logout_userkey_logging_out() {
        global $USER;

        $this->setUser($this->user);
        $USER->auth = 'userkey';
        $_POST['return'] = 'http://google.com';

        try {
            $this->auth->user_logout_userkey();
        } catch (moodle_exception $e) {
            $this->assertFalse(isloggedin());
            $this->assertEquals('Unsupported redirect to http://google.com detected, execution terminated.', $e->getMessage());
        }
    }

}
