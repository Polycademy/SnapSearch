'use strict';

/**
 * User System Service Provider.
 * Relies on Restangular.
 */
module.exports = function () {

    var userData = {},
        accountsResource = 'accounts',
        sessionResource = 'sessions';

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

            var accounts = Restangular.all(accountsResource),
                sessions = Restangular.all(sessionResource);

            //these functions will return a promise
            var userApi = {
                getUserState: function () {
                    if (Object.keys(userData).length === 0) {
                        return false;
                    } else {
                        return true;
                    }
                },
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
                    return accounts.one(id).get().then(function (response) {
                        $rootScope.$broadcast('accountProvided.UserSystem', response.content);
                    });
                },
                registerAccount: function (payload) {
                    return accounts.post(payload).then(function (response) {
                        $rootScope.$broadcast('accountRegistered.UserSystem', payload);
                    });
                },
                updateAccount: function (payload) {
                    return accounts.one(userData.id).customPUT(payload).then(function (response) {
                        $rootScope.$broadcast('accountUpdated.UserSystem', payload);
                    });
                },
                patchAccount: function (payload) {
                    return accounts.one(userData.id).patch(payload).then(function (response) {
                        $rootScope.$broadcast('accountPatched.UserSystem', payload);
                    });
                },
                deleteAccount: function () {
                    return accounts.one(userData.id).remove().then(function (response) {
                        $rootScope.$broadcast('accountDestroyed.UserSystem', userData.id);
                    });
                },
                getSession: function () {
                    return sessions.customGET().then(function (response) {
                        $rootScope.$broadcast('sessionProvided.UserSystem', response.content);
                    });
                },
                loginSession: function (payload) {
                    return sessions.post(payload).then(function (response) {
                        $rootScope.$broadcast('sessionLogin.UserSystem', response.content);
                    });
                },
                logoutSession: function () {
                    return sessions.customDELETE().then(function (response) {
                        $rootScope.$broadcast('sessionLogout.UserSystem', userData.id);
                    });
                }
            };

            /**
             * Upon the account being provided, the user data is set to the response content.
             */
            $rootScope.$on('accountProvided.UserSystem', function (event, content) {
                userAPI.setUserData(content);
            });

            /**
             * Upon the account being registered, attempt to login given the registration payload's username, email or password.
             */
            $rootScope.$on('accountRegistered.UserSystem', function (event, payload) {
                userAPI.loginSession({
                    'username': payload.username,
                    'email': payload.email,
                    'password': payload.password
                });
            });

            /**
             * Upon the account being updated, replace the user data with the payload.
             */
            $rootScope.$on('accountUpdated.UserSystem', function (event, payload) {
                userAPI.setUserData(payload);
            });

            /**
             * Upon the account being patched, merge the user data with the payload.
             */
            $rootScope.$on('accountPatched.UserSystem', function (event, payload) {
                userAPI.mergeUserData(payload);
            });

            /**
             * Upon the account being destroyed, attempt to logout.
             */
            $rootScope.$on('accountDestroyed.UserSystem', function (event, id) {
                userAPI.logoutSession();
            });

            /**
             * Upon the session being provided, check if the session is registered. If registered broadcast a sessionLogin event.
             */
            $rootScope.$on('sessionProvided.UserSystem', function (event, id) {
                if (id !== 'anonymous') {
                    $rootScope.$broadcast('sessionLogin.UserSystem', id);
                }
            });

            /**
             * Upon session login, get the account.
             */
            $rootScope.$on('sessionLogin.UserSystem', function (event, id) {
                userApi.getAccount(id);
            });

            /**
             * Upon session logout, clear the userData.
             */
            $rootScope.$on('sessionLogout.UserSystem', function (event, args) {
                userAPI.setUserData({});
            });

            return userApi;

        }
    ];

};