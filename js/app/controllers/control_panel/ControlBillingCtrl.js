'use strict';

var fs = require('fs');

/**
 * Control Billing Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', '$modal', 'UserSystemServ', 'Restangular', function ($scope, $modal, UserSystemServ, Restangular) {

    var userAccount;

    var getBillingRecords = function () {

        $scope.billingRecords = [];
        Restangular.all('billing').customGET('', {
            user: userAccount.id
        }).then(function (response) {

            response.content = response.content.map(function (card) {

                //convert to integer
                var invalid = parseInt(card.cardInvalid, 10);

                if (invalid) {
                    card.validation = 'Invalid: ' + card.invalidReason;
                } else {
                    card.validation = 'Valid';
                }

                return card;

            });

            $scope.billingRecords = response.content;

        });

    };

    $scope.modal.cardCreate = function () {

        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/control_panel/card_create_modal.html', 'utf8'),
            controller: require('./CardCreateModalCtrl'),
            windowClass: 'card-create-modal form-modal',
            resolve: {
                userId: function () {
                    return userAccount.id
                }
            }
        }).result.then(function () {

            getBillingRecords();

        });

    };

    $scope.deleteCard = function (id, index) {

        Restangular.one('billing', id).remove().then(function (response) {

            $scope.billingRecords.splice(index, 1);
            getBillingRecords();

        }, function (response) {
            
            //verify it doesn't exist on the server side
            getBillingRecords();

        });

    };

    var initialise = function (userData) {

        userAccount = userData;
        getBillingRecords();

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