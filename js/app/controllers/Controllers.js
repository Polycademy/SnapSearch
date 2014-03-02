'use strict';

require('angular');

/**
 * Controllers
 */
angular.module('App.Controllers', [])
    //common
    .controller('HeaderCtrl', require('./common/HeaderCtrl'))
    //home
    .controller('HomeCtrl', require('./home/HomeCtrl'))
    .controller('CodeGroupCtrl', require('./home/CodeGroupCtrl'))
    .controller('DemoCtrl', require('./home/DemoCtrl'))
    //home
    .controller('DocumentationCtrl', require('./documentation/DocumentationCtrl'))
    //pricing
    .controller('PricingCtrl', require('./pricing/PricingCtrl'))
    .controller('CostCalculatorCtrl', require('./pricing/CostCalculatorCtrl'));

module.exports = angular.module('App.Controllers');