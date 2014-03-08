'use strict';

/**
 * Restangular XSRF Integation.
 * This cannot work with $cookies or anything until you're able to access the set-cookies response header.
 * So right now no CSRF protection :( https://github.com/mgonto/restangular/issues/617
 * Set CSRF to true in the config when resolved.
 */
module.exports = ['$cookies', '$timeout', 'Restangular', function ($cookies, $timeout, Restangular) {

    /*
        //XSRF INTEGRATION
        $rootScope.$watch(
            function(){
                return $cookies[serverVars.csrfCookieName];
            },
            function(){
                $http.defaults.headers.common['X-XSRF-TOKEN'] = $cookies[serverVars.csrfCookieName];
            }
        );
     */

     /*
    Restangular.addResponseInterceptor(function (data, operation, what, url, response, deferred) {

        $timeout(function () {

            console.log($cookies);

        }, 0);


        Restangular.setDefaultHeaders({
            'X-XSRF-TOKEN': $cookies[serverVars.csrfCookieName]
        });

        console.log(response.headers());

        // $http.defaults.headers.common['X-XSRF-TOKEN'] = $cookies[serverVars.csrfCookieName];
        //we need to add this to our thing

        return data;
    });
    */

}];