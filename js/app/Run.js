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
 * @param {Function} $anchorScroll
 * @param {Object}   $location
 */
module.exports = [
    '$rootScope',
    '$cookies',
    '$http',
    '$state',
    '$stateParams',
    '$anchorScroll',
    '$location',
    function($rootScope, $cookies, $http, $state, $stateParams, $anchorScroll, $location){

        //XSRF INTEGRATION
        $rootScope.$watch(
            function(){
                return $cookies[serverVars.csrfCookieName];
            },
            function(){
                $http.defaults.headers.common['X-XSRF-TOKEN'] = $cookies[serverVars.csrfCookieName];
            }
        );

        //PROVIDING STATE ON ROOTSCOPE
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;

        //CONFIGURATION
        $rootScope.settings = settings;

        //PROVIDING BASE URL
        $rootScope.baseUrl = angular.element('base').attr('href');

        //hash scroll function, this can be replaced by a directive
        $rootScope.scroll = function (hash) {
            $location.hash(hash);
            $anchorScroll();
        };

    }
];