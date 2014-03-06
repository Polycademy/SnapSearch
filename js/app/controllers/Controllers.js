'use strict';

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
    .controller('CostCalculatorCtrl', require('./pricing/CostCalculatorCtrl'))
    //control panel
    .controller('ControlPanelCtrl', require('./control_panel/ControlPanelCtrl'))
    //terms
    .controller('TermsCtrl', require('./terms/TermsCtrl'))
    //privacy
    .controller('PrivacyCtrl', require('./privacy/PrivacyCtrl'));

module.exports = angular.module('App.Controllers');