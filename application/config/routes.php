<?php

/*
	PIGEON ROUTING
 */
Pigeon::map(function($r){
	
	//RESOURCES ROUTING
	$r->route('api', false, function($r){

		$r->get('v1/robot', 'v1/robot/query');
		$r->post('v1/robot', 'v1/robot/post');

		$r->resources('accounts');
		$r->get('accounts/activate/(:any)', 'accounts/activate/$1');
		$r->get('accounts/forgot_password/(:any)', 'accounts/forgot_password/$1');
		$r->get('accounts/confirm_forgot_password/(:any)', 'accounts/forgot_password/$1');
		$r->resource('sessions');
		$r->resources('blog');

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
$config->set_item('csrf_exclude_uris', array());