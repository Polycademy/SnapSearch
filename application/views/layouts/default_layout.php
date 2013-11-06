<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie10 lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie10 lt-ie9 lt-ie8 ie7"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie10 lt-ie9 ie8"> <![endif]-->
<!--[if IE 9]>         <html class="no-js lt-ie10 ie9"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-js"> <!--<![endif]-->
	<head>
		<base href="<?= base_url() ?>" />
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title><?= $title ?> - <?= $desc ?></title>
		<meta name="description" content="<?= $meta_desc ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="fragment" content="!" />
		<link rel="shortcut icon" href="favicon.ico">
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="img/apple-touch-icon-144x144-precomposed.png">
		<link rel="apple-touch-icon-precomposed" sizes="120x120" href="img/apple-touch-icon-120x120-precomposed.png">
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="img/apple-touch-icon-114x114-precomposed.png">
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="img/apple-touch-icon-72x72-precomposed.png">
		<link rel="apple-touch-icon-precomposed" href="img/apple-touch-icon-57x57-precomposed.png">
		<link rel="stylesheet" href="css/main.css">
		<script src="js/lib/modernizr-2.6.2-respond-1.1.0.min.js"></script>
		<script src="js/config.js"></script>
		<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
			<? if(ENVIRONMENT == 'development'){ ?>
				ga('create', '<?= $google_analytics_key ?>', {'cookieDomain': 'none'});
			<? }elseif(ENVIRONMENT == 'production'){ ?>
				ga('create', '<?= $google_analytics_key ?>', '<?= $domain ?>');
			<? } ?>
		</script>
		<script data-main="js/bootstrap.js" src="js/lib/require.min.js"></script>
	</head>
	<body class="ng-cloak" ng-cloak>

		<!--[if lt IE 7]>
			<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
		<![endif]-->

        <header class="navbar navbar-static-top" ng-controller="HeaderCtrl">
			<div class="container">
				<div class="navbar-inner">
					<a class="logo" href="<?php echo site_url() ?>" title="Home">
						<img src="img/logo.png" />
					</a>
					<ul class="nav">
						<li ng-class="{'active_link': $state.includes('about')}"><a href="about" title="Learn more about Snapsearch">About</a></li>
						<li ng-class="{'active_link': $state.includes('blog')}"><a href="blog" title="Keep up with Snapsearch">Blog</a></li>
					</ul>
				</div>
			</div>
        </header>

		<!-- The side bar will be contained inside the container and ui-view to allow Angularjs to handle it -->
		<div class="main" ui-view></div>

		<!-- Client Side Templates -->
		<?
			Template::asset('application/views', 'php', array(
				'application/views/index.html', //CI stuff
				'application/views/layouts/**',  //for server side
				'application/views/errors/**', //this is for CI
			));
		?>

		<!-- Pass in PHP variables to Javascript -->
		<script>
			var serverVars = {
				baseUrl: "<?= base_url() ?>",
				csrfCookieName: "<?= $this->config->item('cookie_prefix') . $this->config->item('csrf_cookie_name') ?>",
				sessCookieName: "<?= $this->config->item('cookie_prefix') . $this->config->item('sess_cookie_name') ?>"
			};
		</script>

	</body>
</html>