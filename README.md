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
5. Create a token for a specific user and for the service 'Request login URL Service' (Admin > Plugins > Web services > Manage tokens)
6. Configure your external application to make a web call to get login URL.
7. Redirect your users to this URL to be logged in to Moodle.

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

You can amend login URL by "wantsurl" parameter to redirect user after theu logged in to Moodle.

E.g. http://yourmoodle.com/auth/userkey/login.php?key=uniquekey&wantsurl=http://yourmoodle.com/course/view.php?id=3

Wantsurl maybe internal and external.


**User key life time**

This setting describes for how long a user key will be valid. If you try to use expired key then you will
get an error.

**IP restriction**

If this setting is set to yes, then your web application has to provie user's ip address to generate a user key. Then
the user should have provided ip when using this key. If ip address is different a user will get an error.

**Logout redirect URL**

You can set URL to redirect users after they logged out from Moodle. For example you can redirect them
to logout script of your web application to log users out from it as well. This setting is optional.


TODO:
-----
1. Add users provisioning.
2. Implement logout webservice to be able to call it from external application.
3. Add a test client code to README.
