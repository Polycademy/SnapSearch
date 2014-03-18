'use strict';

var settings = require('./Settings');

/**
 * Angular Initialisation & Front Controller
 *
 * @param {Object}   $rootScope
 * @param {Object}   $cookies
 * @param {Object}   $http
 * @param {Object}   $state 
 * @param {Object}   $stateParams
 */
module.exports = [
    '$rootScope',
    '$cookies',
    '$http',
    '$state',
    '$stateParams',
    'BaseUrlConst',
    function($rootScope, $cookies, $http, $state, $stateParams, BaseUrlConst){
        
        //PROVIDING STATE ON ROOTSCOPE
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;

        //CONFIGURATION
        $rootScope.settings = settings;

        //PROVIDING BASE URL
        $rootScope.baseUrl = BaseUrlConst;

    }
];