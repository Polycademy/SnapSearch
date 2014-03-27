'use strict';

/**
 * Control Account Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'UserSystemServ', 'Restangular', function ($scope, UserSystemServ, Restangular) {

    var userAccount;

    $scope.regenerateApiKey = function () {

        Restangular.all('accounts/regenerate_api_key/' + userAccount.id).customPOST().then(function (response) {
            UserSystemServ.getAccount(userAccount.id);
        });

    };

    $scope.updateAccount = function (account) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        UserSystemServ.patchAccount(account).then(function (response) {

            $scope.formSuccess = 'Updated Account';

        }, function (response) {

            if (response.status === 400) {
                $scope.formErrors = response.data.content;
            } else {
                $scope.formErrors = ['System error, try again or contact us.'];
            }

        });

    };

    var initialise = function (userData) {

        userAccount = userData;
        $scope.account = userData;

    };

    //run every time the controller is reinstantiated
    if (UserSystemServ.getUserState() && Object.keys(UserSystemServ.getUserData()).length > 0) {
        
        initialise(UserSystemServ.getUserData());
    
    } else {

        $scope.$watch(UserSystemServ.getUserData, function (newUserAccount, oldUserAccount) {

            //only if they are different, do we poll for new crawling data
            if (!angular.equals(newUserAccount, oldUserAccount)) {
                if (Object.keys(newUserAccount).length > 0) {
                    initialise(newUserAccount);
                }
            }

        });

    }

}];