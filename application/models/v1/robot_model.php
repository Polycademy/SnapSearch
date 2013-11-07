<?php

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException as RespectValidationException;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;

class Robot_model extends CI_Model{

	protected $robot_uri;
	protected $client;
	protected $errors;

	public function __construct(){

		parent::__construct();
		$this->robot_uri = '127.0.0.1:8499';
		$this->client = new Client;
		$this->client->setUserAgent('Snapsearch');

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
					throw new RespectValidationException('Initial wait (initialwait) needs to be lower than Max timeout (maxtimeout)');
				}
			}

		}catch(Exception $e){

			//get the validation messages working
			$e->findMessages(array(‘name’, ‘email’, ‘date_start’));

		}

		//default cache parameters of true and 24 hours
		if(!isset($parameters['cache'])) $parameters['cache'] = true;
		if(!isset($parameters['cachetime'])) $parameters['cachetime'] = 24;

		if($parameters['cache']){
			//we need the user id, for now we're going to assume 1 for everybody
			$cache = $this->read_cache(1, $parameters['url'], $parameters['cachetime']);
			//if cache returned as true, then we the cache exists and is valid, we can decode the snapshot string and return it
			if($cache){
				return json_decode($cache['snapshot']);
			}
		}

		//cache has not been hit, proceed to the robot

		//send a json request
		$request = $this->client->post(
			$this->robot_uri, 
			array(
				'Content-Type'	=> 'application/json'
			),
			json_encode($parameters)
		);

		$response = $request->send()->json();

		//parse possible failures (from the request itself (so proxy server not operating)) to the robot not being able to access the data
		

		//if succeeded, proceed to cache the item (regardless of cache=true/false), this is so next time the request comes in, it can be received from the cache if the sender changes mind
		$this->upsert_cache(1, $parameters['url'], $response);

		return $response;


	}

	public function read_cache($id, $url, $cache_time){

		//valid date is the current time minus $cache_time in hours
		$current_date = new DateTime();
		$valid_date = $current_date->sub(new DateInterval('PT' . $cache_time . 'H'))->format('Y-m-d H:i:s');

		//get the snapshot record for the relevant user and url
		//the entry record must be more recent or equivalent to the valid date
		$query = $this->db->get_where(
			'snapshots', 
			array(
				'id' 		=> $id, 
				'url' 		=> $url,
				'date >='	=> $valid_date,
			)
		);

		if($query->num_rows() > 0){
			
			$row = $query->row();

			$data = array(
				'id'		=> $row->id,
				'userId'	=> $row->userId,
				'date'		=> $row->date,
				'snapshot'	=> $row->snapshot
			);

			return $data;
			
		}else{
		
			//cache did not exist
			return false;
		
		}

	}

	//will add or update to the cache regarding the id, url and snapshot data
	public function upsert_cache($id, $url, $snapshot){

		$data['date'] = date('Y-m-d H:i:s');

	}

	public function get_errors(){

		return $this->errors;

	}

}