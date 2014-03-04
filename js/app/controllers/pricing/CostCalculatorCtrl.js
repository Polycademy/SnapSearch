'use strict';

var settings = require('../../Settings');

/**
 * Cost Calculator Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', 'Calculate', function ($scope, Calculate) {

    var pricingPerUsage = settings.meta.price;
    var freeUsageCap = settings.meta.freeUsageCap;

    //setup the cost object
    $scope.cost = {};

    $scope.$watch(function (scope) {

        return scope.cost.quantity;

    }, function (quantity) {

        if (!quantity) {
            quantity = 0;
        }

        //coerce to integer
        quantity = parseInt(quantity);

        //calculate the price while subtracting from freeUsageCap
        var price = pricingPerUsage * (quantity - freeUsageCap);

        //if the price is negative, reset to zero
        if (price < 0) {
            price = 0;
        }

        //round to 2 decimal points, nearest cent
        price = Calculate.round(price, 2);

        $scope.price = price;

    });

}];