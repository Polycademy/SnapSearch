'use strict';

/**
 * Login Modal Controller
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'UserSystemServ', function ($scope, $modalInstance, $timeout, UserSystemServ) {

    $scope.user = {};

    $scope.formErrors = false;
    $scope.formSuccess = false;

    $scope.login = function (user) {

        UserSystemServ.loginSession(user).then(function () {

            $scope.formErrors = false;
            $scope.formSuccess = 'Successfully logged in.';
            $timeout(function () {
                $modalInstance.close();
            }, 1500);

        }, function (response) {

            $scope.formSuccess = false;
            if (response.status === 400 || response.status === 401) {
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