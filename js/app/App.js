'use strict';

/**
 * Shims and Polyfills
 */
require('es5-shim');
require('es6-shim');
require('json3');

/**
 * Globals (to be eventually converted and shimmed and compiled into common.js)
 */
global.jQuery = require('../../components/jquery/dist/jquery'); //see: https://github.com/jquery/jquery/pull/1521
require('bootstrap');
require('angular');
require('angular-cookies');
require('angular-resource');
require('angular-sanitize');
require('angular-animate');
require('angular-ui-router');
require('angular-bootstrap');
require('angulartics');
require('../../components/angulartics/src/angulartics-ga.js');

/**
 * Modules
 */
var config = require('./Config');

/**
 * Bootstrapping Angular Modules
 */
var app = angular.module('App', [
    require('./controllers/Controllers').name,
    require('./directives/Directives').name,
    require('./elements/Elements').name,
    require('./filters/Filters').name,
    require('./services/Services').name,
    'ngCookies',
    'ngResource',
    'ngSanitize',
    'ngAnimate',
    'ui.router',
    'ui.bootstrap',
    'angulartics',
    'angulartics.google.analytics'
]);

/**
 * Configuration & Routing
 */
app.config([
    '$locationProvider',
    '$stateProvider',
    '$urlRouterProvider',
    function($locationProvider, $stateProvider, $urlRouterProvider){

        //HTML5 Mode URLs
        $locationProvider.html5Mode(true).hashPrefix('!');

        //We should eventually move to precompiled templates. No need to extract it from the DOM. Nor should we need to download them asynchronously since they are very small.
        //I guess the best solution would be asynchronous download, but with a resolve ability!
        //But precompiled templates is faster than loading from DOM. Easier to implement. Cleaner than the current solution.
        $stateProvider
            .state(
                'home',
                {
                    url: '/',
                    templateUrl: 'home.html',
                    controller: 'HomeCtrl'
                }
            );

        $urlRouterProvider.otherwise('/');

    }
]);

/**
 * Initialisation
 */
app.run([
    '$rootScope',
    '$cookies',
    '$http',
    '$state',
    '$stateParams',
    function($rootScope, $cookies, $http, $state, $stateParams){

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
        $rootScope.config = config;

        //PROVIDING BASE URL
        $rootScope.baseUrl = angular.element('base').attr('href');

    }
]);

/**
 * Execute!
 */
angular.element(document).ready(function(){

    angular.bootstrap(document, ['App']);

});