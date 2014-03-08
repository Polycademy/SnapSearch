'use strict';

/**
 * Authentication State Run Block
 */
module.exports = ['$rootScope', 'UserSystemServ', function ($rootScope, UserSystemServ) {

    $rootScope.loggedIn = false;
    $rootScope.loggedOut = true;

    $rootScope.$watch(function () {

        return UserSystemServ.getUserState();
    
    }, function (state) {

        $rootScope.loggedIn = state;
        $rootScope.loggedOut = !state;

    });

}];