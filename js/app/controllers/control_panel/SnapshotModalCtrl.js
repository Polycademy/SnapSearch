'use strict';

/**
 * Snapshot Modal Controller
 */
module.exports = ['$scope', '$modalInstance', 'snapshotId', 'Restangular', function ($scope, $modalInstance, snapshotId, Restangular) {

    $scope.snapshotId = snapshotId;

    Restangular.one('cache', snapshotId).get().then(function (response) {

        //pretty print JSON!
        $scope.snapshot = JSON.stringify(response.content, undefined, 2);
    
    }, function (response) {

        $scope.snapshot = '//cannot find snapshot';

    });

    $scope.cancel = function () {

        $modalInstance.dismiss();

    };

}];