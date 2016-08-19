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
        require_once($CFG->dirroot . '/auth/userkey/tests/fake_userkey_manager.php');
        require_once($CFG->dirroot . '/auth/userkey/auth.php');

        $this->auth = new auth_plugin_userkey();
        $this->user = self::getDataGenerator()->create_user();

        $this->resetAfterTest();
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
    }

    /**
     * Test that we can validate keylifetime for config form correctly.
     */
    public function test_validate_keylifetime_for_config_form() {
        $form = new stdClass();

        $form->redirecturl = '';

        $form->keylifetime = '';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertEquals('User key life time should be a number', $err['keylifetime']);

        $form->keylifetime = '0';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertEquals('User key life time should be a number', $err['keylifetime']);

        $form->keylifetime = '1';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertFalse(array_key_exists('keylifetime', $err));

        $form->keylifetime = 0;
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertEquals('User key life time should be a number', $err['keylifetime']);

        $form->keylifetime = 1;
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertFalse(array_key_exists('keylifetime', $err));

        $form->keylifetime = 'rkjflj';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertEquals('User key life time should be a number', $err['keylifetime']);
    }

    /**
     * Test that we can validate redirecturl for config form correctly.
     */
    public function test_validate_redirecturl_for_config_form() {
        $form = new stdClass();

        $form->keylifetime = 10;

        $form->redirecturl = '';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertFalse(array_key_exists('redirecturl', $err));

        $form->redirecturl = 'http://google.com/';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertFalse(array_key_exists('redirecturl', $err));

        $form->redirecturl = 'https://google.com';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertFalse(array_key_exists('redirecturl', $err));

        $form->redirecturl = 'http://some.very.long.and.silly.domain/with/a/path/';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertFalse(array_key_exists('redirecturl', $err));

        $form->redirecturl = 'http://0.255.1.1/numericip.php';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertFalse(array_key_exists('redirecturl', $err));

        $form->redirecturl = '/just/a/path';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertEquals('You should provide valid URL', $err['redirecturl']);

        $form->redirecturl = 'random string';
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertEquals('You should provide valid URL', $err['redirecturl']);

        $form->redirecturl = 123456;
        $err = array();
        $this->auth->validate_form($form, $err);
        $this->assertEquals('You should provide valid URL', $err['redirecturl']);
    }

    /**
     * Test that we can process config form.
     */
    public function test_process_config_form() {
        $config = get_config('auth_userkey');

        $this->assertObjectNotHasAttribute('mappingfield', $config);
        $this->assertObjectNotHasAttribute('keylifetime', $config);
        $this->assertObjectNotHasAttribute('iprestriction', $config);

        $formconfig = new stdClass();
        $formconfig->mappingfield = 'email';
        $formconfig->keylifetime = 100;
        $formconfig->iprestriction = 0;
        $formconfig->redirecturl = 'http://google.com/';

        $this->auth->process_config($formconfig);

        $config = get_config('auth_userkey');
        $this->assertObjectHasAttribute('mappingfield', $config);
        $this->assertObjectHasAttribute('keylifetime', $config);
        $this->assertObjectHasAttribute('iprestriction', $config);

        $this->assertEquals('email', $config->mappingfield);
        $this->assertEquals(100, $config->keylifetime);
        $this->assertEquals(0, $config->iprestriction);
        $this->assertEquals('http://google.com/', $config->redirecturl);
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
        global $DB;

        $key = new stdClass();
        $key->value = 'ExpiredKey';
        $key->script = 'auth/userkey';
        $key->userid = $this->user->id;
        $key->instance = $this->user->id;
        $key->iprestriction = null;
        $key->validuntil    = time() - 3000;
        $key->timecreated   = time();
        $DB->insert_record('user_private_key', $key);

        $_POST['key'] = 'ExpiredKey';
        $this->auth->user_login_userkey();
    }

    /**
     * Test that IP address mismatch exception gets thrown if incorrect IP.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Client IP address mismatch
     */
    public function test_ipmismatch_exception_thrown_if_ip_is_incorrect() {
        global $DB;

        $key = new stdClass();
        $key->value = 'IpmismatchKey';
        $key->script = 'auth/userkey';
        $key->userid = $this->user->id;
        $key->instance = $this->user->id;
        $key->iprestriction = '192.168.1.1';
        $key->validuntil    = time() + 300;
        $key->timecreated   = time();
        $DB->insert_record('user_private_key', $key);

        $_POST['key'] = 'IpmismatchKey';
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
        $this->auth->user_login_userkey();
    }

    /**
     * Test that IP address mismatch exception gets thrown if incorrect IP.
     *
     * @expectedException moodle_exception
     * @expectedExceptionMessage Invalid user id
     */
    public function test_invalid_user_exception_thrown_if_ip_is_incorrect() {
        global $DB;

        $key = new stdClass();
        $key->value = 'InvalidUser';
        $key->script = 'auth/userkey';
        $key->userid = 777;
        $key->instance = 777;
        $key->iprestriction = '192.168.1.1';
        $key->validuntil    = time() + 300;
        $key->timecreated   = time();
        $DB->insert_record('user_private_key', $key);

        $_POST['key'] = 'InvalidUser';
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $this->auth->user_login_userkey();
    }

    /**
     * Test that key gets removed after a user logged in.
     */
    public function test_that_key_gets_removed_after_user_logged_in() {
        global $DB;

        $key = new stdClass();
        $key->value = 'RemoveKey';
        $key->script = 'auth/userkey';
        $key->userid = $this->user->id;
        $key->instance = $this->user->id;
        $key->iprestriction = '192.168.1.1';
        $key->validuntil    = time() + 300;
        $key->timecreated   = time();
        $DB->insert_record('user_private_key', $key);

        $_POST['key'] = 'RemoveKey';
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';

        // Using @ is the only way to test this. Thanks moodle!
        @$this->auth->user_login_userkey();

        $keyexists = $DB->record_exists('user_private_key', array('value' => 'RemoveKey'));
        $this->assertFalse($keyexists);
    }

    /**
     * Test that a user loggs in correctly.
     */
    public function test_that_user_logged_in() {
        global $DB, $USER, $SESSION;

        $key = new stdClass();
        $key->value = 'UserLogin';
        $key->script = 'auth/userkey';
        $key->userid = $this->user->id;
        $key->instance = $this->user->id;
        $key->iprestriction = null;
        $key->validuntil    = time() + 300;
        $key->timecreated   = time();
        $DB->insert_record('user_private_key', $key);

        $_POST['key'] = 'UserLogin';

        // Using @ is the only way to test this. Thanks moodle!
        $redirect = @$this->auth->user_login_userkey();

        $this->assertEquals('/', $redirect);
        $this->assertEquals($this->user->id, $USER->id);
        $this->assertSame(sesskey(), $USER->sesskey);
        $this->assertObjectHasAttribute('userkey', $SESSION);
    }

    /**
     * Test that wantsurl URL gets returned after user logged in if wantsurl's set.
     */
    public function test_that_return_wantsurl() {
        global $DB;

        $key = new stdClass();
        $key->value = 'WantsUrl';
        $key->script = 'auth/userkey';
        $key->userid = $this->user->id;
        $key->instance = $this->user->id;
        $key->iprestriction = null;
        $key->validuntil    = time() + 300;
        $key->timecreated   = time();
        $DB->insert_record('user_private_key', $key);

        $_POST['key'] = 'WantsUrl';
        $_POST['wantsurl'] = '/course/index.php?id=12&key=134';

        // Using @ is the only way to test this. Thanks moodle!
        $redirect = @$this->auth->user_login_userkey();

        $this->assertEquals('/course/index.php?id=12&key=134', $redirect);
    }

    /**
     * Test that wantsurl URL gets returned after user logged in if wantsurl's set to external URL.
     */
    public function test_that_return_wantsurl_if_it_is_external_url() {
        global $DB;

        $key = new stdClass();
        $key->value = 'WantsUrlExternal';
        $key->script = 'auth/userkey';
        $key->userid = $this->user->id;
        $key->instance = $this->user->id;
        $key->iprestriction = null;
        $key->validuntil    = time() + 300;
        $key->timecreated   = time();
        $DB->insert_record('user_private_key', $key);

        $_POST['key'] = 'WantsUrlExternal';
        $_POST['wantsurl'] = 'http://test.com/course/index.php?id=12&key=134';

        // Using @ is the only way to test this. Thanks moodle!
        $redirect = @$this->auth->user_login_userkey();

        $this->assertEquals('http://test.com/course/index.php?id=12&key=134', $redirect);
    }

}
