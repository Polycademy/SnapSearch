'use strict';

/**
 * Sign Up Modal Controller
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'UserSystemServ', function ($scope, $modalInstance, $timeout, UserSystemServ) {

    $scope.user = {};

    $scope.formErrors = false;
    $scope.formSuccess = false;

    $scope.signup = function (user) {

        UserSystemServ.registerAccount(user).then(function (response) {

            $scope.formErrors = false;
            $scope.formSuccess = 'Successfully registered. Automatically logging in.';
            $timeout(function () {
                $modalInstance.close();
            }, 1500);

        }, function (response) {

            $scope.formSuccess = false;
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