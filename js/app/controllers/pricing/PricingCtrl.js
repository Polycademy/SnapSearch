'use strict';

var settings = require('../../Settings');

/**
 * Pricing Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

    $scope.pricePerUsage = settings.meta.price;
    $scope.freeUsageCap = settings.meta.freeUsageCap;

}];