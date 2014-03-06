'use strict';

/**
 * User State Run Block
 */
module.exports = ['UserStateServ', function (UserStateServ) {

    //attempt to get the user's session upon startup, there are three outcomes:
    //1. continues with the current session with a valid session cookie
    //2. triggers autologin with an autologin cookie and returns a valid session cookie
    //3. remains as an anonymous user
    //in cases where there is valid session cookie authenticationProvided will be broadcasted
    UserStateServ.getSession();

}];