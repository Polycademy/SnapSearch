'use strict';

var fs = require('fs');

/**
 * Control Cache Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', '$modal', 'UserSystemServ', 'Restangular', function ($scope, $modal, UserSystemServ, Restangular) {

    var userAccount;

    var getCacheCount = function () {

        Restangular.all('cache').customGET('', {
            user: userAccount.id,
            transform: 'count'
        }).then(function (response) {

            $scope.snapshotCount = response.content;

        }, function (response) {

            $scope.snapshotCount = 0;

        });

    };

    var offset = 0;
    var limit = 40;

    var getCache = function () {

        Restangular.all('cache').customGET('', {
            user: userAccount.id,
            offset: offset,
            limit: limit
        }).then(function (response) {

            $scope.snapshots = response.content;

        }, function (response) {

            $scope.snapshots = false;

        });

    };

    $scope.forwardCache = function () {

        offset = offset - limit;
        getCache();

    };

    $scope.backwardCache = function () {

        offset = offset + limit;
        getCache();

    };

    $scope.viewSnapshot = function (id) {

        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/control_panel/snapshot_modal.html', 'utf8'), 
            controller: require('./SnapshotModalCtrl'),
            windowClass: 'snapshot-modal form-modal', 
            resolve: {
                snapshotId: function () {
                    return id;
                }
            }
        });

    };

    $scope.deleteSnapshot = function (id, index) {

        //then update list
        Restangular.one('cache', id).remove().then(function (response) {
            //client side updates
            $scope.snapshotCount = $scope.snapshotCount - 1;
            $scope.snapshots.splice(index, 1);
            //verify agains the server side
            getCache();
            getCacheCount();
        }, function (response) {
            //refresh the cache either way, if say the user deleted from a different page
            getCache();
            getCacheCount();
        });

    };

    $scope.primeCache = function (cache) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        var parameters = {};
        if (!_.isEmpty(cache.parameters)) {
            parameters = JSON.parse(cache.parameters);
        }
        //if parameters is not an object or that it is an array, we discard and use an empty object
        if (!angular.isObject(parameters) || angular.isArray(parameters)) {
            parameters = {};
        }
        parameters.url = cache.url;

        Restangular.all('v1/robot').post(parameters).then(function (response) {

            //we don't do client side updates because the new record may update an old record
            getCache();
            getCacheCount();
            $scope.formSuccess = 'Done!';

        }, function (response) {

            if (response.status === 400) {
                $scope.formErrors = response.data.content;
            } else if (response.status === 401 || response.status === 429) {
                $scope.formErrors = [response.data.content];
            } else {
                $scope.formErrors = ['System error, try again or contact us.'];
            }

        });

    };

    var initialise = function (userData) {

        userAccount = userData;
        getCacheCount();
        getCache();

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