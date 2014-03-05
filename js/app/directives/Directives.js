'use strict';

require('angular');

/**
 * Directives
 */
angular.module('App.Directives', []);

module.exports = angular.module('App.Directives')
    .directive('equaliseHeights', require('./equaliseHeights'))
    .directive('anchor', require('./anchor'))
    .directive('passwordMatch', require('./passwordMatch'));