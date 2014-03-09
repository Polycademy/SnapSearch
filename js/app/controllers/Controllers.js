'use strict';

/**
 * Controllers
 */
angular.module('App.Controllers', [])
    //common
    .controller('AppCtrl', require('./common/AppCtrl'))
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
    .controller('ControlCrawlingCtrl', require('./control_panel/ControlCrawlingCtrl'))
    .controller('ControlCacheCtrl', require('./control_panel/ControlCacheCtrl'))
    .controller('ControlPaymentsCtrl', require('./control_panel/ControlPaymentsCtrl'))
    .controller('ControlBillingCtrl', require('./control_panel/ControlBillingCtrl'))
    .controller('ControlAccountCtrl', require('./control_panel/ControlAccountCtrl'))
    //terms
    .controller('TermsCtrl', require('./terms/TermsCtrl'))
    //privacy
    .controller('PrivacyCtrl', require('./privacy/PrivacyCtrl'));

module.exports = angular.module('App.Controllers');