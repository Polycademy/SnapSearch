'use strict';

var fs = require('fs');

/**
 * Angular Router
 */
module.exports = [
    '$locationProvider', 
    '$stateProvider', 
    '$urlRouterProvider', 
    function ($locationProvider, $stateProvider, $urlRouterProvider) {

        //HTML5 Mode URLs
        $locationProvider.html5Mode(true).hashPrefix('!');

        //redirections, this must be above the stateProvider
        $urlRouterProvider
            .when('/control_panel', '/control_panel/crawling') //setting default child state
            .otherwise('/');

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
            )
            .state(
                'controlPanel.crawling', //default controlPanel childstate
                {
                    url: '/crawling',
                    template: fs.readFileSync(__dirname + '/../templates/control_panel/crawling.html', 'utf8'),
                    controller: 'ControlCrawlingCtrl'
                }
            )
            .state(
                'controlPanel.cache',
                {
                    url: '/cache',
                    template: fs.readFileSync(__dirname + '/../templates/control_panel/cache.html', 'utf8'),
                    controller: 'ControlCacheCtrl'
                }
            )
            .state(
                'controlPanel.payments',
                {
                    url: '/payments',
                    template: fs.readFileSync(__dirname + '/../templates/control_panel/payments.html', 'utf8'),
                    controller: 'ControlPaymentsCtrl'
                }
            )
            .state(
                'controlPanel.billing',
                {
                    url: '/billing',
                    template: fs.readFileSync(__dirname + '/../templates/control_panel/billing.html', 'utf8'),
                    controller: 'ControlBillingCtrl'
                }
            )
            .state(
                'controlPanel.account',
                {
                    url: '/account',
                    template: fs.readFileSync(__dirname + '/../templates/control_panel/account.html', 'utf8'),
                    controller: 'ControlAccountCtrl'
                }
            )
            .state(
                'terms',
                {
                    url: '/terms',
                    template: fs.readFileSync(__dirname + '/../templates/terms.html', 'utf8'),
                    controller: 'TermsCtrl'
                }
            )
            .state(
                'privacy',
                {
                    url: '/privacy',
                    template: fs.readFileSync(__dirname + '/../templates/privacy.html', 'utf8'),
                    controller: 'PrivacyCtrl'
                }
            );

    }
];