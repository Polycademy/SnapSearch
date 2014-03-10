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

        <!-- Google Analytics -->
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
            ga("create", "UA-48252325-1", "<?= (ENVIRONMENT == 'development') ? 'none' : 'snapsearch.io' ?>");
        </script>

        <!-- Pass in PHP variables to Javascript -->
        <script>
            var serverVars = {
                csrfCookieName: "<?= $this->config->item('cookie_prefix') . $this->config->item('csrf_cookie_name') ?>",
                sessCookieName: "<?= $this->config->item('cookie_prefix') . $this->config->item('sess_cookie_name') ?>"
            };
        </script>

        <!-- Here we go! Weee! -->
        <script src="js/compiled/Common.js"></script>
        <script src="js/compiled/App.js" async></script>

    </head>
    <body class="ng-cloak" ng-cloak ng-controller="AppCtrl">

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
                        <li ng-class="{'active': $state.includes('home')}"><a ui-sref="home">HOME</a></li>
                        <li ng-class="{'active': $state.includes('documentation')}"><a ui-sref="documentation">DOCUMENTATION</a></li>
                        <li ng-class="{'active': $state.includes('pricing')}"><a ui-sref="pricing">PRICING</a></li>
                        <li><a scroll="about">ABOUT</a></li>
                        <li><a ng-href="http://polycademy.com/blog" title="SnapSearch's blog is at Polycademy">BLOG</a></li>
                        <li ng-show="loggedOut"><button class="btn navbar-btn" type="button" ng-click="modal.signUp()">SIGN UP</button></li>
                        <li ng-show="loggedOut"><button class="btn navbar-btn" type="button" ng-click="modal.logIn()">LOG IN</button></li>
                        <li ng-show="loggedIn" ng-class="{'active': $state.includes('controlPanel')}"><a ui-sref="controlPanel">CONTROL PANEL</a></li>
                        <li ng-show="loggedIn"><button class="btn navbar-btn" type="button" ng-click="auth.logOut()">LOG OUT</button></li>
                    </ul>
                </nav>
            </div>
        </header>

        <!-- The side bar will be contained inside the container and ui-view to allow Angularjs to handle it -->
        <div class="main" ui-view autoscroll="!$state.includes('home')"></div>

        <footer class="panel panel_yellow" anchor="about">
            <div class="container tiger-jump">
                <div class="panel-body">
                    <div class="contact-information">
                        <p>Contact us at <a href="http://www.google.com/recaptcha/mailhide/d?k=01KxkEAwiT1nfx-BhMp7WKWg==&amp;c=iaojzr8kgOuD5gSlcb7Tdexe9yVtnztvwDbDcomRY24=" onclick="window.open('http://www.google.com/recaptcha/mailhide/d?k\07501KxkEAwiT1nfx-BhMp7WKWg\75\75\46c\75iaojzr8kgOuD5gSlcb7Tdexe9yVtnztvwDbDcomRY24\075', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;" title="Reveal this e-mail address">enqu...@snapsearch.io</a></p>
                        <p>We’re happy to hear feedback and discuss partnerships!</p>
                        <p class="social-links"><a href="https://twitter.com/snapsearchio/" target="_blank">Twitter</a> | <a href="https://github.com/SnapSearch/" target="_blank">Github</a></p>
                    </div>
                    <div class="founder-information clearfix">
                        <div class="profile-object">
                            <img class="profile-image" src="assets/img/rogerqiu.png" />
                            <p class="profile-name">Roger</p>
                            <p class="profile-title">Co-Founder</p>
                            <p class="profile-social"><a href="https://twitter.com/polycademy" target="_blank">Twitter</a> | <a href="https://github.com/CMCDragonkai" target="_blank">Github</a></p>
                        </div>
                        <div class="profile-object">
                            <img class="profile-image" src="assets/img/mustafasharara.png" />
                            <p class="profile-name">Mustafa</p>
                            <p class="profile-title">Co-Founder</p>
                        </div>
                    </div>
                    <div class="polycademy-block">
                        <p>SnapSearch is a product from <a href="http://polycademy.com/" target="_blank"><img src="assets/img/polycademy_logo.png" /></a></p>
                    </div>
                    <ul class="footer-links">
                        <li><a href="terms">Terms of Service</a></li>
                        <li><a href="privacy">Privacy Policy</a></li>
                    </ul>
                    <div class="attributions">
                        <p><strong>Attributions:</strong> Browser by Fernando Vasconcelos from The Noun Project | Browser by Konstantin Velichko from The Noun Project | Spider Bot by Siwat Vatatiyaporn from The Noun Project | Thought Bubble by Irene Hoffman from The Noun Project | User by Wilson Joseph from The Noun Project | Globe by Gustav Salomonsson from The Noun Project | Settings by Stefan Parnarov from The Noun Project | Cloud Database by Roman Kovbasyuk from The Noun Project | Arrows by Alex Fuller from The Noun Project | Layers by Oriol Carbonell from The Noun Project | Happy by Simple Icons from The Noun Project | Network by Nicholas Menghini from The Noun Project | Statistics by Calvin Ng from The Noun Project | Arrows by Juan Pablo Bravo from The Noun Project | Tiger by Christy Presler from The Noun Project | Tiger by Allison Dominguez from The Noun Project</p>
                    </div>
                </div>
            </div>
        </footer>

        <chat-tab chat-url="{{settings.meta.chatUrl}}" id="chatTab"></chat-tab>

    </body>
</html>