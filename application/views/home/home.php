<script type="text/ng-template" id="home.html">
    <div class="panel panel_lego">
        <div class="container">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="page-header">
                            <h1 class="page-header">SnapSearch is Search Engine Optimisation for Javascript, HTML 5 and Single Page Applications</h1>
                            <h3>Make your sites crawlable with SnapSearch!</h3>
                            <button class="btn btn-primary" type="button">
                                <h4>Get Started for Free<br /><small>No Credit Card Required</small></h4>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="code-group" ng-controller="CodeGroupCtrl">
                            <ul class="nav nav-tabs">
                                <li ng-class="{'active': activeCode == 'php'}"><button ng-click="changeCode('php')">PHP</button></li>
                                <li ng-class="{'active': activeCode == 'ruby'}"><button ng-click="changeCode('ruby')">Ruby</button></li>
                                <li ng-class="{'active': activeCode == 'node.js'}"><button ng-click="changeCode('node.js')">Node.js</button></li>
                                <li ng-class="{'active': activeCode == 'python'}"><button ng-click="changeCode('python')">Python</button></li>
                            </ul>
                            <div class="tab-content" ng-switch="activeCode">
                                <div class="tab-panel" ng-switch-when="php">
                                    <p>Installation:</p>
                                    <pre><code>composer require snapsearch/snapsearch-client-php:1.0.0</code></pre>
                                    <p>Usage:</p>
                                    <pre>
                                        <code>//add content here, it needs to be encoded</code>
                                    </pre>
                                    <button class="btn btn-primary btn-fork pull-right">Fork me on Github</button>
                                </div>
                                <div class="tab-panel" ng-switch-when="ruby">
                                    <p>Installation:</p>
                                    <pre><code>composer require snapsearch/snapsearch-client-php:1.0.0</code></pre>
                                    <p>Usage:</p>
                                    <pre>
                                        <code>//add content here, it needs to be encoded</code>
                                    </pre>
                                    <button class="btn btn-primary btn-fork pull-right">Fork me on Github</button>
                                </div>
                                <div class="tab-panel" ng-switch-when="node.js">
                                    <p>Installation:</p>
                                    <pre><code>composer require snapsearch/snapsearch-client-php:1.0.0</code></pre>
                                    <p>Usage:</p>
                                    <pre>
                                        <code>//add content here, it needs to be encoded</code>
                                    </pre>
                                    <button class="btn btn-primary btn-fork pull-right">Fork me on Github</button>
                                </div>
                                <div class="tab-panel" ng-switch-when="python">
                                    <p>Installation:</p>
                                    <pre><code>composer require snapsearch/snapsearch-client-php:1.0.0</code></pre>
                                    <p>Usage:</p>
                                    <pre>
                                        <code>//add content here, it needs to be encoded</code>
                                    </pre>
                                    <button class="btn btn-primary btn-fork pull-right">Fork me on Github</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel panel_white">
        <div class="container">
            <div class="panel-heading">
                <h2 class="panel-title">SnapSearch works with...</h2>
            </div>
            <div class="panel-body">
                <!-- Add in the contents -->
            </div>
            <div class="panel-footer">
                <h2 class="panel-title">We’re 100% framework agnostic!</h2>
            </div>
        </div>
    </div>
    <div class="panel panel_lego">
        <div class="container">
            <div class="panel-heading">
                <h2 class="panel-title">Why use SnapSearch?</h2>
            </div>
            <div class="panel-body">
                <!-- Add in the contents -->
            </div>
        </div>
    </div>
    <div class="panel panel_yellow">
        <div class="container">
            <div class="panel-heading">
                <h2 class="panel-title">Features</h2>
            </div>
            <div class="panel-body">
                <!-- Add in the contents -->
            </div>
        </div>
    </div>
    <div class="panel panel_white">
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