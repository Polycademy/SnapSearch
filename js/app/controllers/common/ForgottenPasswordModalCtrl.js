'use strict';

/**
 * Forgotten Password Modal Controller
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'Restangular', function ($scope, $modalInstance, $timeout, Restangular) {

    $scope.user = {};

    $scope.forgot = function (user) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        Restangular.all('accounts/forgotten_password/' + user.email).customGET().then(function (response) {
            
            $scope.formSuccess = 'Sent password reset request email. Please check your email and spam filters.'
            $timeout(function () {
                $modalInstance.close();
            }, 1500);

        }, function (response) {

            if (typeof response.data.content == 'string') {
                $scope.formErrors = [response.data.content];
            } else {
                $scope.formErrors = response.data.content;
            }

        });

    };

    $scope.cancel = function () {

        $modalInstance.dismiss();
    
    };

}];