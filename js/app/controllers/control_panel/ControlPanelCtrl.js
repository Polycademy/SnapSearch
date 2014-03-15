'use strict';

/**
 * Control Panel Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', '$interval', 'UserSystemServ', 'MomentServ', 'CalculateServ', function ($scope, $interval, UserSystemServ, MomentServ, CalculateServ) {

    var refreshingUserAccount = $interval(function () {
        var userData = UserSystemServ.getUserData();
        if (Object.keys(userData).length > 0){
            UserSystemServ.getAccount(userData.id);
        }
    }, 10000);

    $scope.$on('$destroy', function () {
        $interval.cancel(refreshingUserAccount);
    });

    $scope.$watch(UserSystemServ.getUserData, function (value) {

        if (Object.keys(value).length > 0) {

            var userAccount = angular.copy(value);

            // console.log(userAccount);

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