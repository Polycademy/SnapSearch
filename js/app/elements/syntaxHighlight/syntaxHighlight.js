'use strict';

var fs = require('fs');
var insertCss = require('insert-css');
var hljs = require('./lib/hljs/index');
var css = fs.readFileSync(__dirname + '/lib/hljs/styles/default.css', 'utf8');
var codeBlockTemplate = fs.readFileSync(__dirname + '/templates/code_block.html', 'utf8');

insertCss(css);

/**
 * Syntax Highlight Element
 *
 * Assuming the directive is named "syntax":
 * 
 * Element Name Usage
 *     <syntax syntax-language="language">{{code}}</syntax>
 *     =>
 *     <pre syntax-language="language"><code>{{highlightedCode}}</code></pre>
 * Attribute Usage
 *     <e syntax syntax-language="language">{{code}}</syntax>
 *     =>
 *     <pre syntax syntax-language="language"><code>{{highlightedCode}}</code></pre>
 *
 * @param {string} syntaxLanguage Determines the language to highlight
 */
module.exports = ['$sce', function($sce){

    return {
        scope: {
            'syntaxLanguage': '@'
        }, 
        restrict: 'AE',
        template: codeBlockTemplate, 
        transclude: true, 
        replace: true, 
        link: function (scope, element, attributes, controller, transclude) {

            //transclude's clone is the child elements of the directive element, it will wrap any unwrapped text nodes with the span tag
            transclude(scope, function (clone) {

                //get the directive element's content as text, this will be the {{code}}
                var code = angular.element(clone).text();

                //convert the code string into a highlighted code string
                var highlightedCode = hljs.highlight(scope.syntaxLanguage, code, true);

                //bind to the scope as trusted HTML
                scope.highlightedCode = $sce.trustAsHtml(highlightedCode.value.replace(/\n/g,'<br />'));

            });

        }
    };

}];