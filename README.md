Log in to Moodle using one time user key.
=========================================

Auth plugin for organising simple one way SSO(single sign on) between moodle and your external web
application. The main idea is to make a web call to moodle and provide one of the possible matching
fields to find required user and generate one time login URL. The user can be redirected to this
URL to automatically log in to Moodle without typing username and password.


Using
-----
1. Install the plugin as usual.
2. Enable and configure just installed plugin. Set required Mapping field, User key life time,
User key life time and Redirect after logout.
3. Configure Moodle web services as described here http://docs.moodle.org/en/Web_services
4. Add function "auth_userkey_request_login_url" to your enabled web service.
5. Configure your external application to make a web call to get login URL.
6. Redirect your users to this URL to be logged in to Moodle.


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

***Web service will return follwiong structure or error message.***
    Array
        (
        [loginurl] => string
        )

Please navigate to API documentation to get full description for "auth_userkey_request_login_url" function.
e.g. http://yourmoodle.com/admin/webservice/documentation.php

You can amend this URL by wantsurl

**User key life time**

This setting describes for how long a user key will be valid. If you try to use expired key then you will
get an error.

**IP restriction**

If this setting is set to yes, then user have to use the same remote IP address to generate a user key (make
a web call) as well as then log in using this key. If IP address is different a user will get an error.

**Redirect after logout**

You can set URL to redirect user to after they logged out from Moodle. For example you can redirect them
to logout script of your web application to log users out from there as well. This setting is optional.

