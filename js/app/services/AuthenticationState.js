'use strict';

/**
 * Authentication State Run Block
 */
module.exports = ['$rootScope', function ($rootScope) {

    //initial parameters will be changed 
    $rootScope.loggedIn = false;
    $rootScope.loggedOut = true;

    $rootScope.$on('authenticationProvided', function (event, args) {

        $rootScope.loggedIn = true;
        $rootScope.loggedOut = false;

    });

    $rootScope.$on('authenticationLogout', function (event, args) {

        $rootScope.loggedIn = false;
        $rootScope.loggedOut = true;

    });

}];