'use strict';

/**
 * Filters
 */
angular.module('App.Filters', []);

module.exports = angular.module('App.Filters')
    .filter('booleanStyle', require('./booleanStyle'));