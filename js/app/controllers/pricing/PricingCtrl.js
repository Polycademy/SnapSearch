'use strict';

var config = require('../../Config.js');

/**
 * Pricing Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

    $scope.pricePerUsage = config.meta.price;
    $scope.freeUsageCap = config.meta.freeUsageCap;

}];