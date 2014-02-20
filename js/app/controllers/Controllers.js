'use strict';

require('angular');

/**
 * Controllers
 */
angular.module('App.Controllers', [])
    //home
    .controller('HomeCtrl', require('./home/HomeCtrl'))
    .controller('CodeGroupCtrl', require('./home/CodeGroupCtrl'));

module.exports = angular.module('App.Controllers');