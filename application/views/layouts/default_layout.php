<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie10 lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie10 lt-ie9 lt-ie8 ie7"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie10 lt-ie9 ie8"> <![endif]-->
<!--[if IE 9]>         <html class="no-js lt-ie10 ie9"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-js"> <!--<![endif]-->
    <head>

        <base href="<?= base_url() ?>" />

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <title>SnapSearch - Search Engine Optimisation for Javascript, HTML 5 and Single Page Applications</title>
        <meta name="description" content="SnapSearch is Search Engine Optimisation for Javascript, HTML 5 and Single Page Applications. Make your sites crawlable with SnapSearch. AngularJS SEO, BackboneJS SEO, Ember SEO, jQuery SEO Knockout SEO, Meteor SEO and Sails SEO.">

        <meta name="fragment" content="!" />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="shortcut icon" href="assets/img/favicon.ico">
        <link rel="apple-touch-icon-precomposed" href="assets/img/apple-touch-icon-precomposed.png">
        
        <link rel="stylesheet" href="assets/css/Main.css">

        <!-- TODO: Modernizr should be built in production -->
        <script src="components/modernizr/modernizr.js"></script>
        <script src="components/respond/dest/respond.min.js"></script>

        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
            ga("create", "UA-48252325-1", "<?= (ENVIRONMENT == 'development') ? 'none' : 'snapsearch.io' ?>");
        </script>

    </head>
    <body class="ng-cloak" ng-cloak>

        <header class="navbar navbar-default navbar-static-top panel_white panel_transition_white_dark" ng-controller="HeaderCtrl">
            <div class="container">
                <div class="navbar-header">
                    <a class="logo" href="/" title="Home">
                        <img src="assets/img/snapsearch_logo.png" />
                    </a>
                    <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#header-navbar">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>
                <nav class="collapse navbar-collapse" id="header-navbar">
                    <ul class="nav navbar-nav">
                        <li ng-class="{'active': $state.includes('home')}"><a ng-href="home">HOME</a></li>
                        <li ng-class="{'active': $state.includes('documentation')}"><a ng-href="documentation">DOCUMENTATION</a></li>
                        <li ng-class="{'active': $state.includes('pricing')}"><a ng-href="pricing">PRICING</a></li>
                        <li ng-class="{'active': $state.includes('about')}"><a ng-href="about">ABOUT</a></li>
                        <li><a ng-href="http://polycademy.com/blog" title="SnapSearch's blog is at Polycademy">BLOG</a></li>
                        <li><button class="btn navbar-btn" type="button" ng-click="">SIGN UP</button></li>
                        <li><button class="btn navbar-btn" type="button" ng-click="">LOG IN</button></li>
                    </ul>
                </nav>
            </div>
        </header>

        <!-- The side bar will be contained inside the container and ui-view to allow Angularjs to handle it -->
        <div class="main" ui-view autoscroll="false"></div>

        <footer class="panel panel_yellow">
            <div class="container">
                <div class="panel-body">
                    
                </div>
            </div>
        </footer>

        <!-- Client Side Templates -->
        <?
            Template::asset('application/views', 'php', array(
                'application/views/index.html', //CI stuff
                'application/views/layouts/**',  //for server side
                'application/views/errors/**', //this is for CI
                'application/views/invoices/**', //these are for pdf invoices, not HTML
                'application/views/email/**'
            ));
        ?>

        <!-- Pass in PHP variables to Javascript -->
        <script>
            var serverVars = {
                csrfCookieName: "<?= $this->config->item('cookie_prefix') . $this->config->item('csrf_cookie_name') ?>",
                sessCookieName: "<?= $this->config->item('cookie_prefix') . $this->config->item('sess_cookie_name') ?>"
            };
        </script>

        <!-- Here we go! Weee! -->
        <script src="js/compiled/App.js"></script>
        <script></script>

    </body>
</html>