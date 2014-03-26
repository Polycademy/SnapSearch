'use strict';

var settings = require('../../Settings');

/**
 * Control Payments Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'UserSystemServ', 'Restangular', 'CalculateServ', function ($scope, UserSystemServ, Restangular, CalculateServ) {

    var userAccount;

    var getCurrentBill = function () {

        //convert to cents
        var chargePerRequest = settings.meta.price * 100;

        //we don't use apiLeftOverUsage to calculate charges
        var currentCharge = chargePerRequest * (userAccount.apiUsage - userAccount.apiFreeLimit);
        currentCharge = currentCharge + userAccount.apiLeftOverCharge;

        if (currentCharge < 0) {
            currentCharge = 0;
        }

        //convert back to dollars
        currentCharge = currentCharge / 100;

        $scope.billThisMonth = CalculateServ.round(currentCharge, 2);

    };

    var offset = 0;
    var limit = 50;

    var getPaymentRecords = function () {

        Restangular.all('payments').customGET('', {
            user: userAccount.id,
            offset: offset,
            limit: limit
        }).then(function (response) {

            $scope.paymentRecords = response.content;

        });

    };

    $scope.forwardPayments = function () {

        offset = offset - limit;
        getPaymentRecords();

    };

    $scope.backwardPayments = function () {

        offset = offset + limit;
        getPaymentRecords();

    };

    var initialise = function (userData) {

        userAccount = userData;
        getCurrentBill();
        getPaymentRecords();

    };

    //run every time the controller is reinstantiated
    if (UserSystemServ.getUserState() && Object.keys(UserSystemServ.getUserData()).length > 0) {
        
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