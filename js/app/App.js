'use strict';

/**
 * Bootstrapping Angular Modules
 */
var app = angular.module('App', [
    'ngCookies',
    'ngResource',
    'ngSanitize',
    'ngAnimate',
    'restangular',
    'ui.router',
    'ui.bootstrap',
    'ui.utils',
    'angulartics',
    'angulartics.google.analytics',
    'nvd3ChartDirectives',
    require('./modules/Modules').name,
    require('./services/Services').name,
    require('./filters/Filters').name,
    require('./directives/Directives').name,
    require('./elements/Elements').name,
    require('./controllers/Controllers').name
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