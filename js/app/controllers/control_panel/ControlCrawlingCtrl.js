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
         * Formats the X axis on the date graph
         */
        $scope.xAxisDateFormatFunction = function(){
            //xValue is a Moment.js wrapped date objects
            //it's evaluated in milliseconds, but d3 needs it in a JS date object
            return function(xValue){
                return d3.time.format('%Y-%m-%d')(new Date(xValue));
            }
        };

        /**
         * Extracts the key value for the pie graph
         */
        $scope.xPieFunction = function(){
            return function(d) {
                return d.key;
            };
        };
        
        /**
         * Extract the quantity value for the pie graph
         */
        $scope.yPieFunction = function () {
            return function(d){
                return d.quantity;
            };
        };


        var totalDomainDistinctionRequestsQuantity;
        var totalDomainDistinctionUsagesQuantity;

        /**
         * Creates the tool tip content structure for domain distinction requests graph
         */
        $scope.domainDistinctionRequestsToolTip = function () {
            return function (key, quantity, node, chart) {
                return "<h3>" + key +"</h3>" + "<p>" + quantity + " Requests - " + 
                    Math.round(
                        (quantity / totalDomainDistinctionRequestsQuantity) * 100
                    ) + 
                "%</p>";
            };
        };

        /**
         * Creates the tool tip content structure for domain distinction usages graph
         */
        $scope.domainDistinctionUsagesToolTip = function () {
            return function (key, quantity, node, chart) {
                return "<h3>" + key +"</h3>" + "<p>" + quantity + " Usages - " + 
                    Math.round(
                        (quantity / totalDomainDistinctionUsagesQuantity) * 100
                    ) + 
                "%</p>";
            };
        };

        /**
         * Get Request & Usage History Stats
         */
        var getGraphStats = function (userAccount) {

            //currently the ending will always the current date, and the graph will simple contain more data as we go backwards in time
            $scope.logGraphDate = {
                beginning: MomentServ().subtract(MomentServ.duration(userAccount.chargeInterval)),
                ending: MomentServ()
            };

            var getGraph = function () {

                var cutOffDate = $scope.logGraphDate.beginning.format('YYYY-MM-DD HH:mm:ss');
                var dates = [];
                var requests = [];
                var usages = [];

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
                        usages.push([date, value.quantity]);
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
                            key: "Requests",
                            values: requests
                        },
                        {
                            key: "Usages",
                            values: usages
                        }
                    ];

                }, function (response) {

                    $scope.usageHistoryData = [];

                });

            };

            $scope.forwardGraph = function () {

                $scope.logGraphDate.beginning = $scope.logGraphDate.beginning.add(
                    MomentServ.duration(userAccount.chargeInterval)
                );
                getGraph();

            };

            $scope.backwardGraph = function () {

                $scope.logGraphDate.beginning = $scope.logGraphDate.beginning.subtract(
                    MomentServ.duration(userAccount.chargeInterval)
                );
                getGraph();

            };

            getGraph();

        };

        var getHistoryStats = function (userAccount) {

            var domainDistinctionDuration = 'P1Y';

            //right now we're only utilising the beginning
            $scope.domainDistinctionDate = {
                beginning: MomentServ().subtract(MomentServ.duration(domainDistinctionDuration)),
                ending: MomentServ()
            };

            var getDomainDistinction = function () {

                var cutOffDate = $scope.domainDistinctionDate.beginning.format('YYYY-MM-DD HH:mm:ss');

                $scope.domainDistinctionDataRequests = [];
                Restangular.all('log').customGET('', {
                    user: userAccount.id,
                    date: cutOffDate,
                    transform: 'by_domain'
                }).then(function (response) {

                    totalDomainDistinctionRequestsQuantity = 0;

                    //iterate through the domain: quantity
                    var data = [];
                    angular.forEach(response.content, function (value, key) {
                        totalDomainDistinctionRequestsQuantity = totalDomainDistinctionRequestsQuantity + value;
                        data.push({
                            key: key,
                            quantity: value
                        });
                    });

                    $scope.domainDistinctionDataRequests = data;

                });

                $scope.domainDistinctionDataUsages = [];
                Restangular.all('log').customGET('', {
                    user: userAccount.id,
                    date: cutOffDate,
                    type: 'uncached',
                    transform: 'by_domain'
                }).then(function (response) {

                    totalDomainDistinctionUsagesQuantity = 0;

                    //iterate through the domain: quantity
                    var data = [];
                    angular.forEach(response.content, function (value, key) {
                        totalDomainDistinctionUsagesQuantity = totalDomainDistinctionUsagesQuantity + value;
                        data.push({
                            key: key,
                            quantity: value
                        });
                    });

                    $scope.domainDistinctionDataUsages = data;

                });

            };

            $scope.forwardDomains = function () {

                $scope.domainDistinctionDate.beginning = $scope.domainDistinctionDate.beginning.add(
                    MomentServ.duration(domainDistinctionDuration)
                );
                getDomainDistinction();

            };

            $scope.backwardDomains = function () {

                $scope.domainDistinctionDate.beginning = $scope.domainDistinctionDate.beginning.subtract(
                    MomentServ.duration(domainDistinctionDuration)
                );
                getDomainDistinction();

            };

            getDomainDistinction();

            //finally for the log table we'll need to extract the entire data

        };

        var initialise = function (userAccount) {

            handleApiLimitModifierForm(userAccount);
            getGraphStats(userAccount);
            getHistoryStats(userAccount);

        };

        //run every time the controller is reinstantiated
        if (UserSystemServ.getUserState()) {
            
            initialise(UserSystemServ.getUserData());
        
        } else {

            $scope.$watch(UserSystemServ.getUserData, function (newUserAccount, oldUserAccount) {

                //only if they are different, do we poll for new crawling data
                if (!angular.equals(newUserAccount, oldUserAccount)) {
                    if (Object.keys(newUserAccount).length > 0) {
                        initialise(newUserAccount);
                    }
                }

            });

        }

}];