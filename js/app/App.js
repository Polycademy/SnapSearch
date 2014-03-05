'use strict';

/**
 * Shims and Polyfills and Utilities
 */
require('es5-shim');
require('es6-shim');
require('json3');
global._ = require('lodash');

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
require('restangular');
require('angular-ui-router');
require('angular-bootstrap');
require('angular-ui-utils');
require('angulartics');
require('../../components/angulartics/src/angulartics-ga.js');

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
    'restangular',
    'ui.router',
    'ui.bootstrap',
    'ui.utils',
    'angulartics',
    'angulartics.google.analytics'
]);

/**
 * Configuration & Routing
 */
app.config(require('./Router'));

/**
 * Initialisation
 */
app.run(require('./Run'));

/**
 * Execute!
 */
angular.element(document).ready(function(){

    angular.bootstrap(document, ['App']);

});