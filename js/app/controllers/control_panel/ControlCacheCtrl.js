'use strict';

/**
 * Control Cache Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'UserSystemServ', 'Restangular', function ($scope, UserSystemServ, Restangular) {

    var handleCacheForm = function (userAccount) {

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

    };

    var getCacheCount = function (userAccount) {

        Restangular.all('cache').customGET('', {
            user: userAccount.id,
            transform: 'count'
        }).then(function (response) {

            $scope.snapshotCount = response.content;

        }, function (response) {

            $scope.snapshotCount = 0;

        });

    };

    var getCacheList = function (userAccount) {

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

            //open a modal with the snapshot data!
            /*
                Restangular.all('cache').get(id).then(function (response) {
    
                });
             */

        };

        $scope.deleteSnapshot = function (id) {

            //then update list
            Restangular.one('cache', id).remove().then(function (response) {
                getCache();
            });

        };

        getCache();

    };

    var initialise = function (userAccount) {

        handleCacheForm(userAccount);
        getCacheCount(userAccount);
        getCacheList(userAccount);

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