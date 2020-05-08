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
 * Tests for externallib.php.
 *
 * @package    auth_userkey
 * @copyright  2016 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class auth_userkey_externallib_testcase extends advanced_testcase {
    /**
     * User object.
     *
     * @var
     */
    protected $user = array();

    /**
     * Initial set up.
     */
    public function setUp() {
        global $CFG;

        require_once($CFG->libdir . "/externallib.php");
        require_once($CFG->dirroot . '/auth/userkey/externallib.php');

        $this->resetAfterTest();

        $user = array();
        $user['username'] = 'username';
        $user['email'] = 'exists@test.com';
        $user['idnumber'] = 'idnumber';
        $this->user = self::getDataGenerator()->create_user($user);
    }

    /**
     * Test call with incorrect required parameter.
     *
     * @expectedException webservice_access_exception
     * @expectedExceptionMessage Access control exception (The userkey authentication plugin is disabled.)
     */
    public function test_throwing_plugin_disabled_exception() {
        $this->setAdminUser();

        $params = array(
            'bla' => 'exists@test.com',
        );
        // Simulate the web service server.
        $result = auth_userkey_external::request_login_url($params);
        $result = external_api::clean_returnvalue(auth_userkey_external::request_login_url_returns(), $result);
    }

    /**
     * Test successful web service calls.
     */
    public function test_successful_webservice_calls() {
        global $DB, $CFG;

        $CFG->auth = "userkey";
        $this->setAdminUser();

        // Email.
        $params = array(
            'email' => 'exists@test.com',
        );

        // Simulate the web service server.
        $result = auth_userkey_external::request_login_url($params);
        $result = external_api::clean_returnvalue(auth_userkey_external::request_login_url_returns(), $result);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));
        $expectedurl = $CFG->wwwroot . '/auth/userkey/login.php?key=' . $actualkey->value;

        $this->assertTrue(is_array($result));
        $this->assertTrue(key_exists('loginurl', $result));
        $this->assertEquals($expectedurl, $result['loginurl']);

        // Username.
        set_config('mappingfield', 'username', 'auth_userkey');
        $params = array(
            'username' => 'username',
        );

        // Simulate the web service server.
        $result = auth_userkey_external::request_login_url($params);
        $result = external_api::clean_returnvalue(auth_userkey_external::request_login_url_returns(), $result);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));
        $expectedurl = $CFG->wwwroot . '/auth/userkey/login.php?key=' . $actualkey->value;

        $this->assertTrue(is_array($result));
        $this->assertTrue(key_exists('loginurl', $result));
        $this->assertEquals($expectedurl, $result['loginurl']);

        // Idnumber.
        set_config('mappingfield', 'idnumber', 'auth_userkey');
        $params = array(
            'idnumber' => 'idnumber',
        );

        // Simulate the web service server.
        $result = auth_userkey_external::request_login_url($params);
        $result = external_api::clean_returnvalue(auth_userkey_external::request_login_url_returns(), $result);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));
        $expectedurl = $CFG->wwwroot . '/auth/userkey/login.php?key=' . $actualkey->value;

        $this->assertTrue(is_array($result));
        $this->assertTrue(key_exists('loginurl', $result));
        $this->assertEquals($expectedurl, $result['loginurl']);

        // IP restriction.
        set_config('iprestriction', true, 'auth_userkey');
        set_config('mappingfield', 'idnumber', 'auth_userkey');
        $params = array(
            'idnumber' => 'idnumber',
            'ip' => '192.168.1.1',
        );

        // Simulate the web service server.
        $result = auth_userkey_external::request_login_url($params);
        $result = external_api::clean_returnvalue(auth_userkey_external::request_login_url_returns(), $result);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));
        $expectedurl = $CFG->wwwroot . '/auth/userkey/login.php?key=' . $actualkey->value;

        $this->assertTrue(is_array($result));
        $this->assertTrue(key_exists('loginurl', $result));
        $this->assertEquals($expectedurl, $result['loginurl']);
    }

    /**
     * Test call with missing email required parameter.
     *
     * @expectedException invalid_parameter_exception
     * @expectedExceptionMessage Invalid parameter value detected (Required field "email" is not set or empty.)
     */
    public function test_exception_thrown_if_required_parameter_email_is_not_set() {
        global $CFG;

        $this->setAdminUser();
        $CFG->auth = "userkey";

        $params = array(
            'bla' => 'exists@test.com',
        );

        auth_userkey_external::request_login_url($params);
    }

    /**
     * Test call with missing ip required parameter.
     *
     * @expectedException invalid_parameter_exception
     * @expectedExceptionMessage Invalid parameter value detected (Required parameter "ip" is not set.)
     */
    public function test_exception_thrown_if_required_parameter_op_is_not_set() {
        global $CFG;

        $this->setAdminUser();
        $CFG->auth = "userkey";

        set_config('iprestriction', true, 'auth_userkey');

        $params = array(
            'email' => 'exists@test.com',
        );

        auth_userkey_external::request_login_url($params);
    }

    /**
     * Test request for a user who is not exist.
     *
     * @expectedException invalid_parameter_exception
     * @expectedExceptionMessage Invalid parameter value detected (User is not exist)
     */
    public function test_request_not_existing_user() {
        global $CFG;

        $this->setAdminUser();
        $CFG->auth = "userkey";

        $params = array(
            'email' => 'notexists@test.com',
        );

        // Simulate the web service server.
        $result = auth_userkey_external::request_login_url($params);
        $result = external_api::clean_returnvalue(auth_userkey_external::request_login_url_returns(), $result);
    }

    /**
     * Test that permission exception gets thrown if user doesn't have required permissions.
     *
     * @expectedException required_capability_exception
     * @expectedExceptionMessage Sorry, but you do not currently have permissions to do that (Generate login user key)
     */
    public function test_throwing_of_permission_exception() {
        global $CFG;

        $this->setUser($this->user);
        $CFG->auth = "userkey";

        $params = array(
            'email' => 'notexists@test.com',
        );

        // Simulate the web service server.
        $result = auth_userkey_external::request_login_url($params);
        $result = external_api::clean_returnvalue(auth_userkey_external::request_login_url_returns(), $result);
    }

    /**
     * Test request gets executed correctly if use has required permissions.
     */
    public function test_request_gets_executed_if_user_has_permission() {
        global $CFG, $DB;

        $this->setUser($this->user);
        $CFG->auth = "userkey";

        $context = context_system::instance();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        assign_capability('auth/userkey:generatekey', CAP_ALLOW, $studentrole->id, $context->id);
        role_assign($studentrole->id, $this->user->id, $context->id);

        $params = array(
            'email' => 'exists@test.com',
        );

        // Simulate the web service server.
        $result = auth_userkey_external::request_login_url($params);
        $result = external_api::clean_returnvalue(auth_userkey_external::request_login_url_returns(), $result);

        $actualkey = $DB->get_record('user_private_key', array('userid' => $this->user->id));
        $expectedurl = $CFG->wwwroot . '/auth/userkey/login.php?key=' . $actualkey->value;

        $this->assertTrue(is_array($result));
        $this->assertTrue(key_exists('loginurl', $result));
        $this->assertEquals($expectedurl, $result['loginurl']);
    }
}
