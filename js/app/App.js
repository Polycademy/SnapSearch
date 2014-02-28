'use strict';

/**
 * Shims and Polyfills
 */
require('es5-shim');
require('es6-shim');
require('json3');

/**
 * Globals (to be eventually converted and shimmed and compiled into common.js)
 * We should stop using jQuery and Bootstrap javascript. They are not required.
 * Also Polymer Elements & Brick! (Web UI Components) <- Will be integrated into AngularJS 2.0
 * Elements can be asynchronous or completely compiled. It depends on templateUrl or compiled version!
 * Elements can also use React components
 * http://facebook.github.io/react/ and http://stackoverflow.com/a/21244706/582917 (and using requestAnimationFrame http://stackoverflow.com/a/21395442/582917) http://www.youtube.com/watch?v=x7cQ3mrcKaY#t=749
 * React is faster!
 */
global.jQuery = require('jquery');
require('bootstrap');
require('angular');
require('angular-cookies');
require('angular-resource');
require('angular-sanitize');
require('angular-animate');
require('angular-ui-router');
require('angular-bootstrap');
require('angular-ui-utils');
require('angulartics');
require('../../components/angulartics/src/angulartics-ga.js');

/**
 * Modules
 */
var config = require('./Config');
var fs = require('fs');

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
    'ui.utils',
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

        //precompiled templates, these routes should be used with ui-sref and ui-sref-active
        $stateProvider
            .state(
                'home',
                {
                    url: '/',
                    template: fs.readFileSync(__dirname + '/../templates/home.html', 'utf8'),
                    controller: 'HomeCtrl'
                }
            )
            .state(
                'documentation',
                {
                    url: '/documentation',
                    template: fs.readFileSync(__dirname + '/../templates/documentation.html', 'utf8'),
                    controller: 'DocumentationCtrl'
                }
            )
            .state(
                'pricing',
                {
                    url: '/pricing',
                    template: fs.readFileSync(__dirname + '/../templates/pricing.html', 'utf8'),
                    controller: 'PricingCtrl'
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