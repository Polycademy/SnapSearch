'use strict';

/**
 * Login Modal Controller
 */
module.exports = ['$scope', '$modalInstance', function ($scope, $modalInstance) {

    //default user object containing the email and password
    $scope.user = {};

    $scope.login = function (user) {

        //user.email
        //user.password
        //
        //then call $modalInstance.close('logged_in');

    };

    $scope.cancel = function () {

        $modalInstance.dismiss('cancel');

    };

}];