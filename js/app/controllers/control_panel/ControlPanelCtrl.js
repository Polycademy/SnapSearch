'use strict';

/**
 * Control Panel Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'UserSystemServ', function ($scope, UserSystemServ) {

    /*
    Ok in order to correctly assess whether someone is logged in or not.
    it needs to be resolved via the routes. Or in the resolve function.
    This will call whether you're logged in or not, and therefore deliberate what happens afterwards.
    However it must not run on each route, only on the first request.

    Something like this:

    getSession, if valid session, then to the page, if invalid session, redirect or just go to the page, but only do this if this is the first time it's been loaded, because otherwise the data would be cached!
    So the decision on what to do if invalid session should be in the resolve parameter of the routing.

    Furthermore this means the App.js run should not be doing this. But instead the service exposes the getSession.

    You could also do it all in each controller...

    Controllers could be inherited. We can try prototypical inheritance for the resolves too. This could allow overwriting the parent when necessary. So normally just call getSession(), otherwise call getSession().then..etc
     */

    $scope.$watch(function () {

        return UserSystemServ.getUserData();

    }, function (userData) {

        if (Object.keys(userData).length > 0) {

            var id = userData.id;

            console.log('logged in, begin processing data!');

            //use the id to load the data

        }


    }, true);


}];