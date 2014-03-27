'use strict';

/**
 * Card Create Modal
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'Restangular', function ($scope, $modalInstance, $timeout, Restangular) {

    $scope.card = {};

    $scope.createCard = function (card) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        Restangular.all('billing').post(card).then(function (response) {

            $scope.formSuccess = 'Created Card';
            var newCard = Restangular.one('billing', response.content).get();
            $timeout(function () {
                $modalInstance.close(newCard);
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