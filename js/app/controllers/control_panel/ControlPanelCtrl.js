'use strict';

/**
 * Control Panel Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'UserSystemServ', 'MomentServ', 'CalculateServ', function ($scope, UserSystemServ, MomentServ, CalculateServ) {

    $scope.$watch(UserSystemServ.getUserData, function (value) {

        console.log(Object.keys(value).length);

        if (Object.keys(value).length > 0) {

            var userAccount = angular.copy(value);

            console.log(userAccount);

            $scope.userAccount = userAccount;
            $scope.userAccount.apiUsagePercentage = CalculateServ.round((value.apiUsage / value.apiLimit) * 100, '2');

            //chargeCycle will wrap the dates as moment objects
            $scope.chargeCycle = {
                beginning: MomentServ(value.chargeDate, 'YYYY-MM-DD HH:mm:ss').subtract(MomentServ.duration.fromIsoduration(value.chargeInterval)),
                ending: MomentServ(value.chargeDate, 'YYYY-MM-DD HH:mm:ss')
            };

        }

    }, true);

}];