'use strict';

/**
 * Control Panel Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'BusyLoopServ', 'UserSystemServ', 'MomentServ', 'CalculateServ', function ($scope, BusyLoopServ, UserSystemServ, MomentServ, CalculateServ) {

    var cancelBusyLoop = BusyLoopServ(function () {
        if (UserSystemServ.getUserState()){
            var userData = UserSystemServ.getUserData();
            UserSystemServ.getAccount(userData.id);
        }
    }, 60000);

    $scope.$on('$destroy', function () {
        cancelBusyLoop();
    });

    $scope.$watch(UserSystemServ.getUserData, function (value) {

        if (Object.keys(value).length > 0) {

            var userAccount = angular.copy(value);

            $scope.userAccount = userAccount;
            $scope.userAccount.apiUsagePercentage = CalculateServ.round((value.apiUsage / value.apiLimit) * 100, '2');

            //chargeCycle will wrap the dates as moment objects
            $scope.chargeCycle = {
                beginning: MomentServ(value.chargeDate, 'YYYY-MM-DD HH:mm:ss').subtract(MomentServ.duration(value.chargeInterval)),
                ending: MomentServ(value.chargeDate, 'YYYY-MM-DD HH:mm:ss')
            };

        }

    }, true);

}];