'use strict';

var fs = require('fs');

/**
 * Angular Configuration & Routing
 */
module.exports = [
    '$locationProvider', 
    '$stateProvider', 
    '$urlRouterProvider', 
    function ($locationProvider, $stateProvider, $urlRouterProvider) {

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
            )
            .state(
                'controlPanel',
                {
                    url: '/control_panel',
                    template: fs.readFileSync(__dirname + '/../templates/control_panel.html', 'utf8'),
                    controller: 'ControlPanelCtrl'
                }
            );

        $urlRouterProvider.otherwise('/');

    }
];