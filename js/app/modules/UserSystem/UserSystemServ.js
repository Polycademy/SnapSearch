'use strict';

/**
 * User System Service Provider.
 * Relies on Restangular.
 */
module.exports = function () {

    var userData = {},
        authGateway = '/',
        accountsResource = 'accounts',
        sessionResource = 'sessions';

    this.setAuthGateway = function (path) {
        authGateway = path;
    };

    this.setAccountsResource = function (resource) {
        accountsResource = resource;
    };

    this.setSessionResource = function (resource) {
        sessionResource = resource;
    };

    this.$get = [
        '$rootScope',
        '$location',
        'Restangular',
        function ($rootScope, $location, Restangular) {

            //these functions will return a promise
            var userApi = {
                getUserData: function () {
                    return userData;
                },
                setUserData: function (data) {
                    userData = data;
                },
                mergeUserData: function (data) {
                    angular.extend(userData, data);
                },
                getAccount: function (id) {
                    return Restangular.one(accountsResource, id).get().then(function (response) {
                        userData = response.content;
                        $rootScope.$broadcast('accountProvided', userData);
                    });
                },
                registerAccount: function (payload) {
                    return Restangular.all(accountsResource).post(payload).then(function (response) {
                        $rootScope.$broadcast('accountRegistered', payload);
                    });
                },
                updateAccount: function (payload) {
                    //we have to do the one of accounts
                    return Restangular.all(accountsResource).one(userData.id).customPut(payload).then(function (response) {
                        this.setUserData(payload);
                        $rootScope.$broadcast('accountUpdated', payload);
                    });
                },
                patchAccount: function (payload) {
                    return Restangular.all(accountsResource).one(userData.id).patch(payload).then(function (response) {
                        this.mergeUserData(payload);
                        $rootScope.$broadcast('accountPatched', payload);
                    })
                },
                deleteAccount: function () {
                    return Restangular.all(accountsResource).one(userData.id).remove().then(function (response) {
                        $rootScope.$broadcast('accountDestroyed', userData.id);
                        userData = {};
                    });
                },
                getSession: function () {
                    return Restangular.one(sessionResource).get().then(function (response) {
                        if (response.content !== 'anonymous') {
                            $rootScope.$broadcast('sessionLogin', response.content);
                        }
                    });
                },
                loginSession: function (payload) {
                    return Restangular.one(sessionResource).post(payload).then(function (response) {
                        $rootScope.$broadcast('sessionLogin', response.content);
                    });
                },
                logoutSession: function () {
                    return Restangular.one(sessionResource).remove().then(function (response) {
                        $rootScope.$broadcast('sessionLogout', userData.id);
                        userData = {};
                    });
                }
            };

            $rootScope.$on('sessionLogin', function (event, args) {
                userApi.getAccount(args);
            });

            $rootScope.$on('sessionLogout', function (event, args) {
                
            });

            return userApi;

        }
    ];


};