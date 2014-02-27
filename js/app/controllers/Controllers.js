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
    .controller('DemoCtrl', require('./home/DemoCtrl'));

module.exports = angular.module('App.Controllers');