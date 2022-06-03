![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/catalyst/moodle-auth_userkey/ci/MOODLE_33PLUS)


Log in to Moodle using one time user key.
=========================================

Auth plugin for organising simple one way SSO(single sign on) between moodle and your external web
application. The main idea is to make a web call to moodle and provide one of the possible matching
fields to find required user and generate one time login URL. A user can be redirected to this
URL to be log in to Moodle without typing username and password.


Using
-----
1. Install the plugin as usual.
2. Enable and configure just installed plugin. Set required Mapping field, User key life time, IP restriction and Logout redirect URL.
3. Enable web service advance feature (Admin > Advanced features), more info http://docs.moodle.org/en/Web_services
4. Enable one of the supported protocols (Admin > Plugins > Web services > Manage protocols)
5. Create a token for a specific user and for the service 'User key authentication web service' (Admin > Plugins > Web services > Manage tokens)
6. Make sure that the "web service" user has 'auth/userkey:generatekey' capability.
7. Authorise the "web service" user: Admin > Plugins > Web services > External services, select 'Authorised users' for the web service, and add the user.
8. Configure your external application to make a web call to get login URL.
9. Redirect your users to this URL to be logged in to Moodle.

Configuration
-------------

**Mapping field**

Required data structure for web call is related to mapping field you configured.

For example XML-RPC (PHP structure) description for different mapping field settings:

***User name***

    [user] =>
        Array
            (
            [username] => string
            )

***Email Address***

    [user] =>
        Array
            (
            [email] => string
            )

***ID number***

    [user] =>
        Array
            (
            [idnumber] => string
            )

***Web service will return following structure or standard Moodle webservice error message.***

    Array
        (
        [loginurl] => string
        )

Please navigate to API documentation to get full description for "auth_userkey_request_login_url" function.
e.g. http://yourmoodle.com/admin/webservice/documentation.php

You can amend login URL by "wantsurl" parameter to redirect user after they logged in to Moodle.

E.g. http://yourmoodle.com/auth/userkey/login.php?key=uniquekey&wantsurl=http://yourmoodle.com/course/view.php?id=3

Wantsurl maybe internal and external.


**User key life time**

This setting describes for how long a user key will be valid. If you try to use expired key then you will
get an error.

**IP restriction**

If this setting is set to yes, then your web application has to provie user's ip address to generate a user key. Then
the user should have provided ip when using this key. If ip address is different a user will get an error.

**Redirect after logout from Moodle**

You can set URL to redirect users after they logged out from Moodle. For example you can redirect them
to logout script of your web application to log users out from it as well. This setting is optional.

**URL of SSO host**

You can set URL to redirect users before they see Moodle login page. For example you can redirect them
to your web application to login page. You can use "enrolkey_skipsso" URL parameter to bypass this option.
E.g. http://yourmoodle.com/login/index.php?enrolkey_skipsso=1

**Logout URL**

If you need to logout users after they logged out from the external application, you can redirect them 
to logout script with required parameter "return". 

E.g. http://yourmoodle.com/auth/userkey/logout.php?return=www.google.com 


Users will be logged out from Moodle and then redirected to the provided URL. 
In case when a user session is already expired, the user will be still redirected.  
  

**Example client**

**Note:** the code below is not for production use. It's just a quick and dirty way to test the functionality.

The code below defines a function that can be used to obtain a login url. 
You will need to add/remove parameters depending on whether you have update/create user enabled and which mapping field you are using.

The required library curl can be obtained from https://github.com/moodlehq/sample-ws-clients
```php
/**
 * @param   string $useremail Email address of user to create token for.
 * @param   string $firstname First name of user (used to update/create user).
 * @param   string $lastname Last name of user (used to update/create user).
 * @param   string $username Username of user (used to update/create user).
 * @param   string $ipaddress IP address of end user that login request will come from (probably $_SERVER['REMOTE_ADDR']).
 * @param int      $courseid Course id to send logged in users to, defaults to site home.
 * @param int      $modname Name of course module to send users to, defaults to none.
 * @param int      $activityid cmid to send logged in users to, defaults to site home.
 * @return bool|string
 */
function getloginurl($useremail, $firstname, $lastname, $username, $courseid = null, $modname = null, $activityid = null) {
    require_once('curl.php');
        
    $token        = 'YOUR_TOKEN';
    $domainname   = 'http://MOODLE_WWW_ROOT';
    $functionname = 'auth_userkey_request_login_url';

    $param = [
        'user' => [
            'firstname' => $firstname, // You will not need this parameter, if you are not creating/updating users
            'lastname'  => $lastname, // You will not need this parameter, if you are not creating/updating users
            'username'  => $username, 
            'email'     => $useremail,
        ]
    ];

    $serverurl = $domainname . '/webservice/rest/server.php' . '?wstoken=' . $token . '&wsfunction=' . $functionname . '&moodlewsrestformat=json';
    $curl = new curl; // The required library curl can be obtained from https://github.com/moodlehq/sample-ws-clients 

    try {
        $resp     = $curl->post($serverurl, $param);
        $resp     = json_decode($resp);
        if ($resp && !empty($resp->loginurl)) {
            $loginurl = $resp->loginurl;        
        }
    } catch (Exception $ex) {
        return false;
    }

    if (!isset($loginurl)) {
        return false;
    }

    $path = '';
    if (isset($courseid)) {
        $path = '&wantsurl=' . urlencode("$domainname/course/view.php?id=$courseid");
    }
    if (isset($modname) && isset($activityid)) {
        $path = '&wantsurl=' . urlencode("$domainname/mod/$modname/view.php?id=$activityid");
    }

    return $loginurl . $path;
}

echo getloginurl('barrywhite@googlemail.com', 'barry', 'white', 'barrywhite', 2, 'certificate', 8);
```


# Crafted by Catalyst IT

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)

# Contributing and Support

Issues, and pull requests using github are welcome and encouraged! 

https://github.com/catalyst/moodle-auth_userkey/issues

If you would like commercial support or would like to sponsor additional improvements
to this plugin please contact us:

https://www.catalyst-au.net/contact-us
