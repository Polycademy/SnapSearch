'use strict';

var fs = require('fs');
var insertCss = require('insert-css');
var css = fs.readFileSync(__dirname + '/chat.css', 'utf8');
var chatTemplate = fs.readFileSync(__dirname + '/chat.html', 'utf8');

insertCss(css);

/**
 * Chat Tab
 *
 * Relies on Angular jQuery
 */
module.exports = [function () {

    return {
        scope: {
            'chatUrl': '@' //this gets passed in directly
        }, 
        restrict: 'AE',
        template: chatTemplate, 
        replace: true, 
        link: function (scope, element, attributes) {

            scope.openCloseChatTab = function () {

                element.children('.chatTab-content').toggleClass('crushed');

            };

        }
    };

}];