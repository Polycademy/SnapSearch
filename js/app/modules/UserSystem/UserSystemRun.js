'use strict';

/**
 * User System Run Block
 */
module.exports = ['$rootScope', 'UserSystemServ', function ($rootScope, UserSystemServ) {

    //attempt to get the user's session upon startup, there are three outcomes:
    //1. continues with the current session with a valid session cookie
    //2. triggers autologin with an autologin cookie and returns a valid session cookie
    //3. remains as an anonymous user
    //if the session retrieved was not anonymous it will broadcast that the sesion is logged in
    UserSystemServ.getSession().then(function (response) {
        if (response.content !== 'anonymous') {
            $rootScope.$broadcast('sessionLogin.UserSystem', response.content);
        }
    });

}];