<?php

/*
	PIGEON ROUTING
 */
Pigeon::map(function($r){
	
	//RESOURCES ROUTING
	$r->route('api', false, function($r){

		//versioned api
		$r->resource('v1/robot');
		
		
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