'use strict';

var fs = require('fs');

/**
 * Control Billing Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', '$modal', function ($scope, $modal) {

    //once cards get created or updated, we need to either add to the card model client side, or requery the server...
    //client side updating is quicker and more "semantic"
    //server side requerying is easier to do

    $scope.modal.cardCreate = function () {

        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/control_panel/card_create_modal.html', 'utf8'),
            controller: require('./CardCreateModalCtrl'),
            windowClass: 'card-create-modal form-modal'
        }).result.then(function () {

            //requery the server and update the cards model

        });

    };

    //card update modal is only available from the Billing controller
    $scope.modal.cardUpdate = function () {

        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/control_panel/card_update_modal.html', 'utf8'), 
            controller: require('./CardUpdateModalCtrl'),
            windowClass: 'card-update-modal form-modal'
        }).result.then(function () {

            //request the server and update the cards model

        });

    };

}];