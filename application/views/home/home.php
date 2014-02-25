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
                                    <pre><code>composer require snapsearch/snapsearch-client-php:1.0.0</code></pre>
                                    <p>Usage:</p>
                                    <pre class="code-usage"><code>//add content here, it needs to be encoded</code></pre>
                                    <button class="btn btn-primary btn-fork pull-right">
                                        <img src="assets/img/github_mark.png" />
                                        Fork me on Github
                                    </button>                                </div>
                                <div class="tab-panel" ng-switch-when="ruby">
                                    <p>Installation:</p>
                                    <pre><code>gem install snapsearch-client-ruby</code></pre>
                                    <p>Usage:</p>
                                    <pre class="code-usage"><code>//add content here, it needs to be encoded</code></pre>
                                    <button class="btn btn-primary btn-fork pull-right">
                                        <img src="assets/img/github_mark.png" />
                                        Fork me on Github
                                    </button>
                                </div>
                                <div class="tab-panel" ng-switch-when="node.js">
                                    <p>Installation:</p>
                                    <pre><code>npm install snapsearch-client-node:1.0.0</code></pre>
                                    <p>Usage:</p>
                                    <pre class="code-usage"><code>//add content here, it needs to be encoded</code></pre>
                                    <button class="btn btn-primary btn-fork pull-right">
                                        <img src="assets/img/github_mark.png" />
                                        Fork me on Github
                                    </button>
                                </div>
                                <div class="tab-panel" ng-switch-when="python">
                                    <p>Installation:</p>
                                    <pre><code>pip install snapsearch-client-python</code></pre>
                                    <p>Usage:</p>
                                    <pre class="code-usage"><code>//add content here, it needs to be encoded fgd gfgh fgh fgh fh gfh fh gfs hfg h gf hsfhg gh fg hfg hsh  sgh gfs fgh</code></pre>
                                    <button class="btn btn-primary btn-fork pull-right">
                                        <img src="assets/img/github_mark.png" />
                                        Fork me on Github
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel panel_white panel_transition_white_dark">
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
    <div class="panel panel_lego panel_transition_yellow_dark">
        <div class="container">
            <div class="panel-heading">
                <h2 class="panel-title">Why use SnapSearch?</h2>
            </div>
            <div class="panel-body">
                <!-- Add in the contents -->
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