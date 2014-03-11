'use strict';

var settings = require('../../Settings');

/**
 * Control Crawling Controller
 *
 * @param {Object} $scope
 */
module.exports = [
    '$scope', 
    'UserSystemServ', 
    'CalculateServ', 
    'Restangular', 
    'MomentServ', 
    function ($scope, UserSystemServ, CalculateServ, Restangular, MomentServ) {

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

                //calculate the price while subtracting from free usage limit
                var price = pricePerUsage * (quantity - userAccount.apiFreeLimit);

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

            var usageHistoryOffset = 0;
            var usageHistoryLimit = 6;

            var getHistory = function () {

                Restangular.all('usage').customGET('', {
                    user: userAccount.id,
                    offset: usageHistoryOffset,
                    limit: usageHistoryLimit
                }).then(function (response) {

                    var dates = [];
                    var usage = [];
                    var requests = [];
                    response.content.forEach(function (value, index) {
                        var date = MomentServ(value.date, 'YYYY-MM-DD HH:mm:ss');
                        dates.push(date);
                        usage.push([date, value.usage]);
                        requests.push([date, value.requests]);
                    });
                    var oldestDate = dates.reduce(function (prevDate, curDate) {
                        return curDate.unix() < prevDate.unix() ? curDate : prevDate;
                    });
                    var latestDate = dates.reduce(function (prevDate, curDate) {
                        return curDate.unix() > prevDate.unix() ? curDate : prevDate;
                    });

                    $scope.usageHistoryData = [
                        {
                            key: "Usage Cap",
                            values: [
                                [oldestDate, userAccount.apiLimit],
                                [latestDate, userAccount.apiLimit]
                            ]
                        },
                        {
                            key: "Usages",
                            values: usage
                        },
                        {
                            key: "Requests",
                            values: requests
                        }
                    ];

                }, function () {

                    $scope.usageHistoryData = [];

                });

            };

            $scope.xAxisDateFormatFunction = function(){
                //xValue is milliseconds, as it seems that Moment.js automatically turns itself into milliseconds
                return function(xValue){
                    return d3.time.format('%Y-%m-%d')(new Date(xValue));
                }
            };

            getHistory();

            $scope.forwardUsageHistory = function () {

                usageHistoryOffset = usageHistoryOffset - usageHistoryLimit;
                if (usageHistoryOffset < 0) {
                    usageHistoryOffset = 0;
                }
                getHistory();

            };

            $scope.backwardUsageHistory = function () {

                usageHistoryOffset = usageHistoryOffset + usageHistoryLimit;
                getHistory();

            };

        }

    });



}];