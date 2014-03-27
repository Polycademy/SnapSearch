'use strict';

/**
 * Card Create Modal
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'userId', 'Restangular', function ($scope, $modalInstance, $timeout, userId, Restangular) {

    $scope.card = {};

    $scope.createCard = function (card) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        //we're creating a billing record for a particular user id
        $scope.card['userId'] = userId;

        Restangular.all('billing').post(card).then(function (response) {

            $scope.formSuccess = 'Created Card';
            $timeout(function () {
                $modalInstance.close();
            }, 1000);

        }, function (response) {

            if (response.status === 400) {
                $scope.formErrors = response.data.content;
            } else {
                $scope.formErrors = ['System error, try again or contact us.'];
            }

        });

    };

    $scope.cancel = function () {

        $modalInstance.dismiss();

    };

}];