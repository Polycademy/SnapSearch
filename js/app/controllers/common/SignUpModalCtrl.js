'use strict';

/**
 * Sign Up Modal Controller
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'UserSystemServ', function ($scope, $modalInstance, $timeout, UserSystemServ) {

    $scope.user = {};

    $scope.signup = function (user) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        UserSystemServ.registerAccount(user).then(function (response) {

            $scope.formSuccess = 'Successfully registered. Automatically logging in.';
            $timeout(function () {
                $modalInstance.close();
            }, 1500);

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