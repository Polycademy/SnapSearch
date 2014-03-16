'use strict';

var settings = require('../../Settings');

/**
 * Control Crawling Controller
 *
 * @param {Object} $scope
 */
module.exports = [
    '$scope', 
    '$q',
    'UserSystemServ', 
    'CalculateServ', 
    'Restangular', 
    'MomentServ', 
    function ($scope, $q, UserSystemServ, CalculateServ, Restangular, MomentServ) {

        var checkUserAccount = function () {

            return $scope.userAccount || false;
        
        };

        /**
         * Handle API Limit Modifier Form
         */
        var handleApiLimitModifierForm = function (userAccount) {

            $scope.apiLimitModifier = {};

            //default api limit quantity is the current api limit
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
                var price = settings.meta.price * (quantity - userAccount.apiFreeLimit);

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

        };

        /**
         * Get Request & Usage History Stats
         */
        var getGraphStats = function (userAccount) {

            var logGraphDate = MomentServ().subtract(MomentServ.duration.fromIsoduration(userAccount.chargeInterval));

            var getGraph = function () {

                var cutOffDate = logGraphDate.format('YYYY-MM-DD HH:mm:ss');
                var dates = [];
                var requests = [];
                var usage = [];

                var cachedLog = Restangular.all('log').customGET('', {
                    user: userAccount.id,
                    date: cutOffDate,
                    type: 'cached',
                    transform: 'by_date'
                });

                var uncachedLog = Restangular.all('log').customGET('', {
                    user: userAccount.id,
                    date: cutOffDate,
                    type: 'uncached',
                    transform: 'by_date'
                });

                $q.all([
                    cachedLog,
                    uncachedLog
                ]).then(function (responses) {

                    responses[0].content.forEach(function (value, index) {
                        var date = MomentServ(value.date, 'YYYY-MM-DD HH:mm:ss');
                        dates.push(date);
                        requests.push([date, value.quantity]);
                    });

                    responses[1].content.forEach(function (value, index) {
                        var date = MomentServ(value.date, 'YYYY-MM-DD HH:mm:ss');
                        dates.push(date);
                        usage.push([date, value.quantity]);
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

                }, function (response) {

                    $scope.usageHistoryData = [];

                });

            };

            //sets up the date formatting on the x axis
            $scope.xAxisDateFormatFunction = function(){
                //xValue is a Moment.js wrapped date objects
                //it's evaluated in milliseconds, but d3 needs it in a JS date object
                return function(xValue){
                    return d3.time.format('%Y-%m-%d')(new Date(xValue));
                }
            };

            getGraph();

            $scope.forwardGraph = function () {

                logGraphDate = logGraphDate.add(MomentServ.duration.fromIsoduration(userAccount.chargeInterval));
                getGraph();

            };

            $scope.backwardGraph = function () {

                logGraphDate = logGraphDate.subtract(MomentServ.duration.fromIsoduration(userAccount.chargeInterval));
                getGraph();

            };

        };

        var getHistoryStats = function (userAccount) {

            //get the full log up to a date...?
            //log_model will need to support date offsets instead of offset/limit
            //domain distinction actually needs to grab it off the server?
            //probably better to execute it from the server, there could be a lot of data

            //finally for the log table we'll need to extract the entire data

        };

        var initialise = function (userAccount) {

            handleApiLimitModifierForm(userAccount);
            getGraphStats(userAccount);
            getHistoryStats();

        };

        //run every time the controller is reinstantiated
        if (checkUserAccount()) {
            
            initialise(checkUserAccount());
        
        } else {

            $scope.$watch(checkUserAccount, function (userAccount) {
                initialise(userAccount);
            });

        }

}];