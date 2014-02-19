'use strict';

var config = require('./Config');
    domready = require('domready'),
    es5Shim = require('es5-shim'),
    es6Shim = require('es6-shim'),
    json3 = require('json3'),
    jQuery = $ = require('jquery'), //these need to be put into globals for angular and bootstrap to take advantage
    bootstrap = require('bootstrap'), //THIS MIGHT HAVE A PROBLEM SINCE THERE ARE MULTIPLE "main" files!
    angular = require('angular'),
    angularCookies = require('angular-cookies'),
    angularResource = require('angular-resource'),
    angularSanitize = require('angular-sanitize'),
    angularAnimate = require('angular-animate'),
    uiRouter = require('angular-ui-router'),
    uiBootstrap = require('angular-ui-bootstrap'),
    angulartics = require('angulartics');

//app is an module that is dependent on several top level modules
var app = angular.module('App', [
    'Controllers',
    'Directives',
    'Elements',
    'Filters',
    'Services',
    'ngCookies',
    'ngResource',
    'ngSanitize',
    'ngAnimate',
    'ui.router',
    'ui.bootstrap',
    'angulartics',
    'angulartics-google-analytics'
]);

//parent core modules for the registry, they have the [] allowing functions to be appended to them
angular.module('Controllers', []);
angular.module('Directives', []);
angular.module('Elements', []);
angular.module('Filters', []);
angular.module('Services', []);

/*
    HERE we require all the controllers..etc.
 */

app.config([
    '$locationProvider',
    '$stateProvider',
    '$urlRouterProvider',
    function($locationProvider, $stateProvider, $urlRouterProvider){

        //HTML5 Mode URLs
        $locationProvider.html5Mode(true).hashPrefix('!');

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
        $rootScope.baseUrl = jQuery('base').attr('href');

    }
]);

domready(function(){

    angular.bootstrap(document, ['App']);

});