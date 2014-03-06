'use strict';

/**
 * User System Run Block
 */
module.exports = ['UserSystemServ', function (UserSystemServ) {

    //attempt to get the user's session upon startup, there are three outcomes:
    //1. continues with the current session with a valid session cookie
    //2. triggers autologin with an autologin cookie and returns a valid session cookie
    //3. remains as an anonymous user
    //in cases where there is valid session cookie authenticationProvided will be broadcasted
    UserSystemServ.getSession();

}];