<?php

/*
	PIGEON ROUTING
 */
Pigeon::map(function($r){
	
	//RESOURCES ROUTING
	$r->route('api', false, function($r){

		//ROBOT
		$r->get('v1/robot', 'v1/robot/query');
		$r->post('v1/robot', 'v1/robot/post');

		//ACCOUNTS
		$r->resources('accounts');
		$r->patch('accounts/([a-zA-Z0-9\-_]+)', 'accounts#update');
		$r->get('accounts/forgotten_password/(:any)', 'accounts/forgotten_password/$1');
		$r->post('accounts/confirm_forgotten_password', 'accounts/confirm_forgotten_password');
		$r->post('accounts/regenerate_api_key/(:any)', 'accounts/regenerate_api_key/$1');

		//SESSIONS
		$r->resource('session');

		//BILLING
		$r->resources('billing');
		$r->patch('billing/([a-zA-Z0-9\-_]+)', 'billing#update');

		//PAYMENTS HISTORY
		$r->resources('payments');

		//USAGE HISTORY
		$r->resources('usage');

		//LOG HISTORY
		$r->resources('log');

		//CACHE HISTORY
		$r->resources('cache');

		//DEMO
		$r->resource('demo');

		//INVOICES
		$r->resources('invoices');

	});

	//CLI ROUTING
	$r->route('cli', false , function($r){
		
		//Migration
		//php index.php cli migrate restart
		$r->route('migrate', 'migrate/index');
		$r->route('migrate/latest', 'migrate/latest');
		$r->route('migrate/current', 'migrate/current');
		$r->route('migrate/version/(:num)', 'migrate/version/$1');
		$r->route('migrate/restart',  'migrate/restart');
		$r->route('migrate/restart/(:num)',  'migrate/restart/$1');

		//Cache purging
		//php index.php cli cron purge_cache P30D
		$r->route('cron/purge_cache', 'cron#purge_cache');
		$r->route('cron/purge_cache/(.*)', 'cron#purge_cache');
		$r->route('cron/purge_cache/(.*)/(.*)', 'cron#purge_cache');

		//Monthly Billing
		//php index.php cli cron monthly_tracking
		$r->route('cron/monthly_tracking', 'cron#monthly_tracking');

		$r->route('cron/test', 'cron#test');
		
	});
	
	//CLIENT SIDE ROUTING
	$r->route('(.*)', 'home#index');
	
});

$route = Pigeon::draw();

$route['default_controller'] = 'home';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
	CSRF EXCLUSION, use api paths
 */
$config =& load_class('Config', 'core');
$config->set_item('csrf_exclude_uris', array(
	'api/v1/robot'
));