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
        <meta name="description" content="SnapSearch is Search Engine Optimisation for Javascript, HTML 5 and Single Page Applications. Make your sites crawlable with SnapSearch.">

        <meta name="fragment" content="!" />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="shortcut icon" href="assets/img/favicon.ico">
        <link rel="apple-touch-icon-precomposed" href="assets/img/apple-touch-icon-precomposed.png">
        
        <link rel="stylesheet" href="css/main.css">

        <!-- TODO: Modernizr should be built in production -->
        <script src="components/modernizr/modernizr.js"></script>
        <script src="components/respond/dest/respond.min.js"></script>

        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
            <? if(ENVIRONMENT == 'development'){ ?>
                ga('create', 'GOOGLE ANALYTICS KEY', {'cookieDomain': 'none'});
            <? }elseif(ENVIRONMENT == 'production'){ ?>
                ga('create', 'GOOGLE ANALYTICS KEY', 'auto');
            <? } ?>
        </script>

    </head>
    <body class="ng-cloak" ng-cloak>

        <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

        <!-- TODO: Convert to Bootstrap 3 Compatible Template! -->
        <header class="navbar navbar-static-top">
            <div class="container">
                <div class="navbar-inner">
                    <a class="logo" href="<?php echo site_url() ?>" title="Home">
                        <img src="img/logo.png" />
                    </a>
                    <ul class="nav">
                        <li ng-class="{'active_link': $state.includes('home')}"><a href="home">Home</a></li>
                        <li ng-class="{'active_link': $state.includes('documentation')}"><a href="documentation">Documentation</a></li>
                        <li ng-class="{'active_link': $state.includes('pricing')}"><a href="pricing">Pricing</a></li>
                        <li ng-class="{'active_link': $state.includes('about')}"><a href="about">About</a></li>
                        <li><a href="http://polycademy.com/blog" title="SnapSearch's blog is at Polycademy">Blog</a></li>
                        <li><a>Sign Up</a></li>
                        <li><a>Log In</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- The side bar will be contained inside the container and ui-view to allow Angularjs to handle it -->
        <div class="main" ui-view></div>

        <!-- TODO: Footer! Also these should be moved out into their own templates. And the ui-viewed templates can load them or not. -->
        <footer>
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
        <script src="js/compiled/app.js"></script>

    </body>
</html>