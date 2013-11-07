<?php

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class Robot_model extends CI_Model{

	protected $errors;

	public function __construct(){

		parent::__construct();

	}

	public function read_site($input_parameters){

		$parameters = elements(array(
			'url',
			'width',
			'height',
			'imgformat',
			'useragent',
			'screenshot',
			'loadimages',
			'javascriptenabled',
			'maxtimeout',
			'initialwait',
			'callback',
			'cache',
			'cachetime',
		), $input_parameters, null, true);

		try{

			v::allOf(
				v::key(
					'url', 
					v::call(
						'parse_url', 
						v:arr()
						->key('scheme', v::notEmpty()),
						->key('host', v::domain())
					)
				)->setName('Url (url)'), 
				v::key('width', v::int()->between(200, 4000, true), false)->setName('Width (width)'), 
				v::key('height', v::int()->between(200, 4000, true), false)->setName('Height (height)'), 
				v::key(
					'imgformat', 
					v::string()->oneOf(
						v::equals('png'),
						v::equals('jpg'),
						v::equals('jpeg')
					), 
					false
				)->setName('Image format (imgformat)'), 
				v::key('useragent', v::string()->length(1, 2000, true), false)->setName('Useragent (useragent)'),
				v::key(
					'screenshot', 
					v::string()->oneOf(
						v::equals('true'),
						v::equals('false')
					),
					false
				)->setName('Screenshot (screenshot)'),
				v::key(
					'loadimages', 
					v::string()->oneOf(
						v::equals('true'),
						v::equals('false')
					),
					false
				)->setName('Load images (loadimages)'),
				v::key(
					'javascriptenabled', 
					v::string()->oneOf(
						v::equals('true'),
						v::equals('false')
					),
					false
				)->setName('Javascript enabled (javascriptenabled)'),
				v::key('maxtimeout', v::int()->between(1000, 15000, true), false)->setName('Max timeout (maxtimeout)'),
				v::key('initialwait', v::int(), false)->setName('Initial wait (initialwait)'),
				v::key('callback', v::string()->length(1, 5000), false)->setName('Callback (callback)'),
				v::key(
					'cache', 
					v::string()->oneOf(
						v::equals('true'),
						v::equals('false')
					),
					false
				)->setName('Cache (cache)'),
				v::key('cachetime', v::int()->between(1, 50), false)->setName('Cache time (cachetime)'),
			)->assert($parameters);
		
			if(isset($parameters['maxtimeout']) AND isset($parameters['initialwait'])){
				//initialwait has to be lower than maxtimeout
				if($parameters['initialwait'] >= $parameters['maxtimeout']){
					throw new ValidationException('Initial wait (initialwait) needs to be lower than Max timeout (maxtimeout)');
				}
			}

		}catch(Exception $e){

			$e->findMessages(array(‘name’, ‘email’, ‘date_start’));

		}

		//check if $parameters['cache'] is true, if false hit the robot
		//check if the url has been cached before with the relevant account id (each account may have their own caches)
		//check the cache time to see has expired, if expired proceed to hit the robot
		//if the cache has not expired, return the cache

	}

	public function read_cache($id, $url){

	}

	//will add or update to the cache regarding the id, url and snapshot data
	public function upsert_cache($id, $url, $snapshot, $date){

	}

	public function delete_cache($id, $url){

	}

}