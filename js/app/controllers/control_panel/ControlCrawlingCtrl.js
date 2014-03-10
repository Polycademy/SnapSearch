'use strict';

var settings = require('../../Settings');

/**
 * Control Crawling Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'UserSystemServ', 'CalculateServ', function ($scope, UserSystemServ, CalculateServ) {

    var pricePerUsage = settings.meta.price;

    $scope.$watch(function () {

        return $scope.userAccount || false;

    }, function (userAccount) {

        if (userAccount) {

            $scope.apiLimitModifier = {};

            //default quantity
            $scope.apiLimitModifier.quantity = userAccount.apiLimit;

            $scope.changeLimit = function (apiLimitModifier) {

                //prevent it going below freeUsageCap
                //equal or higher
                //prevent change if 

                console.log(apiLimitModifier);

            };

            $scope.$watch(function (scope) {

                return scope.apiLimitModifier.quantity;

            }, function (quantity) {

                if (!quantity) {
                    quantity = 0;
                }

                //coerce to integer
                quantity = parseInt(quantity);

                console.log(quantity);

                //calculate the price while subtracting from free usage limit
                var price = pricePerUsage * (quantity - userAccount.apiFreeLimit);

                console.log(price);

                //if the price is negative, reset to zero
                if (price < 0) {
                    price = 0;
                }

                //round to 2 decimal points, nearest cent
                price = CalculateServ.round(price, 2);

                $scope.price = price;

            });

        }

    });



}];