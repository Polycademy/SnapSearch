'use strict';

require('angular');

/**
 * Controllers
 */
angular.module('Controllers', [])
    //home
    .controller('HomeCtrl', require('./home/HomeCtrl'))
    .controller('CodeGroupCtrl', require('./home/CodeGroupCtrl'));

module.exports = angular.module('Controllers');