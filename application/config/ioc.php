<?php

/**
 * Pimple uses anonymous functions (lambdas) so it can "lazy load" the classes.
 * The functions will not be processed when the PHP interpreter goes through this file.
 * They will be kept inside the function waiting to be called as part of the container array.
 * Once you call the functions, then the objects will be created! Thus "lazy loading", not "eager loading". Saves memory too!
 * The functions can also be "shared", so they are not executed everytime it is called, even more lazier loading!
 * Note Pimple is an object that acts like an array, see the actual Pimple code to see how this works.
 * This usage assumes that you have autoloading working, so that the references to the classes will be autoloaded!
 * "$this->config" corresponds to the config files. It can be accessed inside the closures in 5.4.
 */

$ioc = new Pimple;

/**
 * Setups a PDO Database Handle.
 * This is for libraries that will require database connection.
 */
$ioc['Database'] = $ioc->share(function($c){
	$CI = get_instance();
	$CI->load->database();
	$dbh = $CI->db->conn_id;
	return $dbh;
});

/**
 * Symfony Request Object augmented with the Authorization header
 */
$ioc['Request'] = $ioc->share(function($c){
	$headers = getallheaders();
	$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
	if(isset($headers['Authorization'])){
		$request->headers->set('Authorization', $headers['Authorization']);
	}
	return $request;
});

//we need to pass the $ioc into the global $config variable, so now it can be accessed by Codeigniter
$config['ioc'] = $ioc;