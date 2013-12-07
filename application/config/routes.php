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
		$r->get('accounts/forgotten_password/(:any)', 'accounts/forgotten_password/$1');
		$r->post('accounts/confirm_forgotten_password', 'accounts/confirm_forgotten_password');

		//BILLING
		$r->get('billing/(:any)', 'billing/show/$1');
		$r->post('billing/(:any)', 'billing/create/$1');
		$r->put('billing/(:any)', 'billing/update/$1');
		$r->delete('billing/(:any)', 'billing/delete/$1');

		//PAYMENTS HISTORY
		$r->get('payments/(:any)', 'payments/show/$1');

		//USAGE HISTORY
		$r->get('usage/(:any)', 'payments/show/$1');

		//SESSIONS
		$r->resource('sessions');

		//TEST
		$r->get('test/(:any)', 'test/$1');


	});

	//CLI ROUTING
	$r->route('cli', false , function($r){
		
		//php index.php cli migrate restart
		$r->route('migrate', 'migrate/index');
		$r->route('migrate/latest', 'migrate/latest');
		$r->route('migrate/current', 'migrate/current');
		$r->route('migrate/version/(:num)', 'migrate/version/$1');
		$r->route('migrate/restart',  'migrate/restart');
		$r->route('migrate/restart/(:num)',  'migrate/restart/$1');

		$r->route('cron/purge_cache', 'cron#purge_cache');
		$r->route('cron/purge_cache/(.*)', 'cron#purge_cache');
		$r->route('cron/purge_cache/(.*)/(.*)', 'cron#purge_cache');
		
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