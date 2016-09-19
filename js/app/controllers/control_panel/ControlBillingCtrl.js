'use strict';

var fs = require('fs');
var settings = require('../../Settings');

/**
 * Control Billing Controller.
 * Although billing system supports having multiple cards per customer.
 * We are going to assume a 1-1 correspondence between cards and customer to simplify things.
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'UserSystemServ', 'ExternalScriptLoaderServ', 'Restangular', function ($scope, UserSystemServ, ExternalScriptLoaderServ, Restangular) {

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

    var stripeScriptCallback = function (action, billingId) {

        return function (stripeData) {

            // although errors are being reported directly from stripe
            // we can have billing errors from our backend trying to confirm the stripe application
            $scope.stripeBillingBackendErrors = false;
            $scope.stripeBillingBackendSuccess = false;

            if (action === 'create') {

                // create the billing record for the user 
                Restangular.all('billing').post({
                    'stripeToken': stripeData.id,
                    'stripeEmail': stripeData.email,
                    'userId': userAccount.id
                }).then(function (response) {

                    $scope.stripeBillingBackendSuccess = 'Created Customer';
                    // re-acquire the billing records after successful billing creation
                    getBillingRecords();

                }, function (response) {

                    // we need to display errors even if 400 or 500
                    $scope.stripeBillingBackendErrors = response.data.content;

                });

            } else if (action === 'update') {

                // update the first (and only) billing record for the current user 
                Restangular.one('billing', billingId).customPUT({
                    'stripeToken': stripeData.id,
                    'stripeEmail': stripeData.email
                }).then(function (response) {

                    $scope.stripeBillingBackendSuccess = 'Updated Customer';
                    // re-acquire the billing records after successful billing creation
                    getBillingRecords();

                }, function (response) {

                    // we need to display errors even if 400 or 500
                    $scope.stripeBillingBackendErrors = response.data.content;

                });

            }

        };

    };

    var setupStripeForm = function () {

        ExternalScriptLoaderServ.importScript(
            'https://checkout.stripe.com/checkout.js', 
            'stripeScript', 
            function () {

                var stripeHandler = StripeCheckout.configure({
                    key: settings.apiKeys.stripePublicKey,
                    locale: 'auto',
                    currency: 'aud',
                    panelLabel: 'Subscribe',
                    label: 'Add a Card via Stripe',
                    allowRememberMe: false, 
                    email: userAccount.email
                });

                $scope.cardCreate = function () {

                    stripeHandler.open({
                        token: stripeScriptCallback('create', null)
                    });

                };

                $scope.cardUpdate = function (id) {

                    stripeHandler.open({
                        token: stripeScriptCallback('update', id)
                    });

                };

            }
        );

    };

    var initialise = function (userData) {

        // set this up first, as all subsequent procedures rely on this variable
        userAccount = userData;
        getBillingRecords();
        setupStripeForm();

    };

    $scope.cardDelete = function (id) {

        Restangular.one('billing', id).remove().then(function (response) {

            $scope.stripeBillingBackendSuccess = 'Deleted Customer';
            getBillingRecords();

        }, function (response) {
            
            //verify it doesn't exist on the server side
            $scope.stripeBillingBackendSuccess = 'Already Deleted Customer';
            getBillingRecords();

        });

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