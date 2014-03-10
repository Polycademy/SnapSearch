'use strict';

var settings = require('../../Settings');

/**
 * Control Crawling Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'UserSystemServ', 'CalculateServ', 'Restangular', function ($scope, UserSystemServ, CalculateServ, Restangular) {

    var pricePerUsage = settings.meta.price;

    $scope.$watch(function () {

        return $scope.userAccount || false;

    }, function (userAccount) {

        if (userAccount) {

            //setting up apiLimitModifier object
            $scope.apiLimitModifier = {};

            //default quantity is the current api limit
            $scope.apiLimitModifier.quantity = userAccount.apiLimit;

            //check if the user has billing details
            Restangular.all('billing').customGET('', {
                user: userAccount.id, 
                active: true, 
                valid: true
            }).then(function () {
                $scope.hasBillingDetails = true;
            }, function () {
                $scope.hasBillingDetails = false;
            });

            //calculate the price
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

            //change the limit
            $scope.changeLimit = function (apiLimitModifier) {

                $scope.formErrors = false;
                $scope.formSuccess = false;

                UserSystemServ.patchAccount({
                    apiLimit: apiLimitModifier.quantity
                }).then(function (response) {

                    $scope.formSuccess = 'Successfully updated API Usage Cap!';

                }, function (response) {

                    if (typeof response.data.content == 'string') {
                        $scope.formErrors = [response.data.content];
                    } else {
                        $scope.formErrors = response.data.content;
                    }

                });

            };

        }

    });



}];