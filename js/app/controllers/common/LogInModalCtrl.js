'use strict';

/**
 * Login Modal Controller
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'UserSystemServ', function ($scope, $modalInstance, $timeout, UserSystemServ) {

    $scope.user = {};

    $scope.login = function (user) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        UserSystemServ.loginSession(user).then(function () {

            $scope.formSuccess = 'Successfully logged in.';
            $timeout(function () {
                $modalInstance.close();
            }, 1500);

        }, function (response) {

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