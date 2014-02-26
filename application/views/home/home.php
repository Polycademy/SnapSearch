<script type="text/ng-template" id="home.html">
    <div class="introduction panel panel_lego panel_transition_white_dark">
        <div class="container">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="page-header">
                            <h1>SnapSearch is Search Engine Optimisation for Javascript, HTML 5 and Single Page Applications</h1>
                            <h3>Make your sites crawlable with SnapSearch!</h3>
                            <button class="call-to-action btn btn-primary" type="button">
                                <h4 class="call-to-action-text">Get Started for Free<br /><small>No Credit Card Required</small></h4>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="code-group clearfix" ng-controller="CodeGroupCtrl">
                            <ul class="nav nav-tabs">
                                <li class="tab" ng-class="{'active': activeCode == 'php'}">
                                    <button class="btn" ng-click="changeCode('php')">PHP</button>
                                </li>
                                <li class="tab" ng-class="{'active': activeCode == 'ruby'}">
                                    <button class="btn" ng-click="changeCode('ruby')">Ruby</button>
                                </li>
                                <li class="tab" ng-class="{'active': activeCode == 'node.js'}">
                                    <button class="btn" ng-click="changeCode('node.js')">Node.js</button>
                                </li>
                                <li class="tab" ng-class="{'active': activeCode == 'python'}">
                                    <button class="btn" ng-click="changeCode('python')">Python</button>
                                </li>
                            </ul>
                            <div class="tab-content clearfix" ng-switch="activeCode">
                                <div class="tab-panel" ng-switch-when="php">
                                    <p>Installation:</p>
                                    <syntax syntax-language="bash">composer require snapsearch/snapsearch-client-php:1.0.0</syntax>
                                    <p>Usage:</p>
                                    <syntax class="code-usage" syntax-language="php">//add content here, it needs to be encoded</syntax>
                                    <a class="btn btn-primary btn-fork pull-right" href="https://github.com/SnapSearch/SnapSearch-Client-PHP" target="_blank">
                                        <img src="assets/img/github_mark.png" />
                                        Fork me on Github
                                    </a>                                </div>
                                <div class="tab-panel" ng-switch-when="ruby">
                                    <p>Installation:</p>
                                    <syntax syntax-language="bash">gem install snapsearch-client-ruby</syntax>
                                    <p>Usage:</p>
                                    <syntax class="code-usage" syntax-language="ruby">#add content here, it needs to be encoded</syntax>
                                    <a class="btn btn-primary btn-fork pull-right" href="https://github.com/SnapSearch/SnapSearch-Client-Ruby" target="_blank">
                                        <img src="assets/img/github_mark.png" />
                                        Fork me on Github
                                    </a>
                                </div>
                                <div class="tab-panel" ng-switch-when="node.js">
                                    <p>Installation:</p>
                                    <syntax syntax-language="bash">npm install snapsearch-client-node:1.0.0</syntax>
                                    <p>Usage:</p>
                                    <syntax class="code-usage" syntax-language="javascript">//add content here, it needs to be encoded</syntax>
                                    <a class="btn btn-primary btn-fork pull-right" href="https://github.com/SnapSearch/SnapSearch-Client-Node" target="_blank">
                                        <img src="assets/img/github_mark.png" />
                                        Fork me on Github
                                    </a>
                                </div>
                                <div class="tab-panel" ng-switch-when="python">
                                    <p>Installation:</p>
                                    <syntax syntax-language="bash">pip install snapsearch-client-python</syntax>
                                    <p>Usage:</p>
                                    <syntax class="code-usage" syntax-language="python">#comment</syntax>
                                    <a class="btn btn-primary btn-fork pull-right" href="https://github.com/SnapSearch/SnapSearch-Client-Python" target="_blank">
                                        <img src="assets/img/github_mark.png" />
                                        Fork me on Github
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="framework-support panel panel_white panel_transition_white_dark">
        <div class="container">
            <div class="panel-heading">
                <h2 class="panel-title">SnapSearch works with...</h2>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <img class="framework-logo" src="assets/img/sails_logo.png" />
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <img class="framework-logo" src="assets/img/angular_logo.png" />
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <img class="framework-logo" src="assets/img/js_logo.png" />
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <img class="framework-logo" src="assets/img/jquery_logo.png" />
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <img class="framework-logo" src="assets/img/backbone_logo.png" />
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <img class="framework-logo" src="assets/img/ember_logo.png" />
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <img class="framework-logo" src="assets/img/knockout_logo.png" />
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <img class="framework-logo" src="assets/img/meteor_logo.png" />
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <h2 class="panel-title">We’re 100% framework agnostic!</h2>
            </div>
        </div>
    </div>
    <div class="problem-solution panel panel_lego panel_transition_yellow_dark">
        <div class="container">
            <div class="panel-heading">
                <h2 class="panel-title">Why use SnapSearch?</h2>
            </div>
            <div class="panel-body">
                <h3 class="problem-title">The Problem</h3>
                <div class="problem row">
                    <div class="col-md-6">
                        <img src="assets/img/user_coding.png" />
                        <div class="problem-explanation">
                            <p>You’ve coded up a javascript enhanced or single page application using the latest HTML5 technologies. Using a modern browser, you can see all the asynchronous or animated content appear.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <img src="assets/img/spider_reading.png" />
                        <div class="problem-explanation">
                            <p>Search engines however see nothing. This is because search engine robots are simple HTTP clients that cannot execute advanced javascript. They do not execute AJAX, and thus cannot load asynchronous resources, nor can they activate javascript events that make your application dynamic and user friendly.</p>
                        </div>
                    </div>
                </div>
                <h3 class="solution-title">The Solution</h3>
                <div class="solution row">
                    <div class="col-md-3"></div>
                    <div class="col-md-3"></div>
                    <div class="col-md-3"></div>
                    <div class="col-md-3"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel panel_yellow panel_transition_white_yellow">
        <div class="container">
            <div class="panel-heading">
                <h2 class="panel-title">Features</h2>
            </div>
            <div class="panel-body">
                <!-- Add in the contents -->
            </div>
        </div>
    </div>
    <div class="panel panel_white panel_transition_white_yellow">
        <div class="container">
            <div class="panel-heading">
                <h2 class="panel-title">Try our demo here</h2>
            </div>
            <div class="panel-body">
                <!-- Add in the contents -->
            </div>
        </div>
    </div>
</script>