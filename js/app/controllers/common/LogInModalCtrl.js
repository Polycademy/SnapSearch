'use strict';

var fs = require('fs');

/**
 * Login Modal Controller
 */
module.exports = ['$scope', '$modalInstance', '$timeout', '$modal', 'UserSystemServ', function ($scope, $modalInstance, $timeout, $modal, UserSystemServ) {

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

    $scope.forgottenPassword = function () {

        //dimiss because close results in a transition to control panel
        $modalInstance.dismiss();
        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/forgotten_password_modal.html', 'utf8'),
            controller: require('./ForgottenPasswordModalCtrl'),
            windowClass: 'forgotten-password-modal form-modal'
        });

    };

}];