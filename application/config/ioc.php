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
	$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
	//php-fpm doesn't support getallheaders yet: https://bugs.php.net/bug.php?id=62596
	//we need to test if authorization exists for cgi mode normally...
	if(function_exists('getallheaders')){
		$headers = getallheaders();
		if(isset($headers['Authorization'])){
			$request->headers->set('Authorization', $headers['Authorization']);
		}
	}
	//parsing JSON body content
	if(0 === strpos($request->headers->get('Content-Type'), 'application/json')){
		$json_data = json_decode($request->getContent(), true);
		$request->request->replace(is_array($json_data) ? $json_data : array());
	}
	return $request;
});

/**
 * PolyAuth
 * Accounts Management
 * Authentication using CookieStrategy but also HTTPBasicStrategy
 */
$ioc['PolyAuth\Options'] = $ioc->share(function($c){
	return new PolyAuth\Options($this->config['polyauth']);
});

$ioc['PolyAuth\Language'] = $ioc->share(function($c){
	return new PolyAuth\Language;
});

$ioc['PolyAuth\Storage'] = $ioc->share(function($c){
	return new PolyAuth\Storage\MySQLAdapter($c['Database'], $c['PolyAuth\Options']);
});

$ioc['PolyAuth\Emailer'] = $ioc->share(function($c){
	return new PolyAuth\Emailer($c['PolyAuth\Options'], $c['PolyAuth\Language']);
});

$ioc['PolyAuth\AccountsManager'] = $ioc->share(function($c){
	return new PolyAuth\Accounts\AccountsManager(
		$c['PolyAuth\Storage'], 
		$c['PolyAuth\Options'], 
		$c['PolyAuth\Language']
	);
});

$ioc['PolyAuth\Authentication\CookieStrategy'] = $ioc->share(function($c){
	return new PolyAuth\Authentication\AuthStrategies\CookieStrategy(
		$c['PolyAuth\Storage'], 
		$c['PolyAuth\Options'], 
		$c['PolyAuth\Language'], 
		new PolyAuth\Sessions\SessionManager(
			$c['PolyAuth\Options'], 
			$c['PolyAuth\Language'], 
			new PolyAuth\Sessions\Persistence\FileSystemPersistence
		)
	);
});

$ioc['PolyAuth\Authentication\HTTPBasicStrategy'] = $ioc->share(function($c){
	return new PolyAuth\Authentication\AuthStrategies\HTTPBasicStrategy(
		$c['PolyAuth\Storage'], 
		$c['PolyAuth\Options'], 
		$c['PolyAuth\Language'], 
		new PolyAuth\Sessions\SessionManager(
			$c['PolyAuth\Options'], 
			$c['PolyAuth\Language'], 
			new PolyAuth\Sessions\Persistence\MemoryPersistence
		)
	);
});

$ioc['PolyAuth\Authenticator'] = $ioc->share(function($c){
	return new PolyAuth\Authentication\Authenticator(
		new PolyAuth\Authentication\AuthStrategies\CompositeStrategy(
			$c['PolyAuth\Authentication\CookieStrategy'],
			$c['PolyAuth\Authentication\HTTPBasicStrategy']
		), 
		$c['PolyAuth\Storage'], 
		$c['PolyAuth\Options'], 
		$c['PolyAuth\Language']
	);
});

//we need to pass the $ioc into the global $config variable, so now it can be accessed by Codeigniter
$config['ioc'] = $ioc;