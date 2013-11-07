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

		$USER_ID = 1;

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

			$this->errors = array(
				'validation_error'	=> 
			);
			return false;

		}

		//default cache parameters of true and 24 hours
		if(!isset($parameters['cache'])) $parameters['cache'] = true;
		if(!isset($parameters['cachetime'])) $parameters['cachetime'] = 24;

		//the cache needs to return data even if it is expired
		//this part determines the expiry
		//then you can have a variable indicating the id of the cache
		//then you can update or insert....

		$existing_cache_id = false;
		if($parameters['cache']){

			//we need the user id, for now we're going to assume 1 for everybody
			$cache = $this->read_cache($USER_ID, $parameters['url']);

			//valid date is the current time minus $cache_time in hours
			$current_date = new DateTime();
			$valid_date = $current_date->sub(new DateInterval('PT' . $cache_time . 'H'))->format('Y-m-d H:i:s');

			if($cache){

				//the cache's date of entry has to be more recent or equal to the valid date
				if(strtotime($cache['date']) >= strtotime($valid_date)){
					return json_decode($cache['snapshot'], true);
				}

				$existing_cache_id = $cache['id'];

			}

		}

		//cache has not been hit, proceed to the robot
		try{

			$request = $this->client->post(
				$this->robot_uri, 
				array(
					'Content-Type'	=> 'application/json'
				),
				json_encode($parameters)
			);

			$response = $request->send()->json();

		}catch(BadResponseException $e){

			//a bad response exception can come from 400 or 500 errors, this should not happen
			log_message('error', 'Snapsearch PHP application received a 400/500 from Robot\'s load balancer or robot itself.');
			$this->errors = array(
				'system_error'	=> 'Robot service is a bit broken. Try again later.'
			);
			return false;

		}catch(CurlException $e){

			log_message('error', 'Snapsearch PHP application received a curl error when contacting the robot load balancer. See: ' . $e->getMessage());
			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);
			return false;

		}

		if($response['message'] == 'Failed'){

			$this->errors = array(
				'error'	=> 'Robot could not open uri: ' . $parameters['url']
			);
			return false;

		}		

		//request has succeeded so we're going to cache the response
		$this->upsert_cache($existing_cache_id, $USER_ID, $parameters['url'], json_encode($response));

		return $response;


	}

	protected function read_cache($user_id, $url){

		//get the snapshot record for the relevant user and url
		$query = $this->db->get_where(
			'snapshots', 
			array(
				'userId' 	=> $user_id, 
				'url' 		=> $url,
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
		
			return false;
		
		}

	}

	protected function upsert_cache($id, $user_id, $url, $snapshot){

		if($id){

			$this->db->where('id', $id);
			$query = $this->db->update('snapshots', array(
				'date'		=> date('Y-m-d H:i:s'),
				'snapshot'	=> $snapshot,
			));

			//should be able to update
			if($query->affected_rows() <= 0){

				return false;

			}

		}else{

			$this->db->insert('snapshots', array(
				'userId'	=> $user_id,
				'url'		=> $url,
				'date'		=> date('Y-m-d H:i:s'),
				'snapshot'	=> $snapshot,
			));

			if(!$query){

				$msg = $this->db->error()['message'];
				$num = $this->db->error()['code'];
				$last_query = $this->db->last_query();
				log_message('error', 'Problem inserting into snapshots table: ' . $msg . ' (' . $num . '), using this query: "' . $last_query . '"');
				return false;

			}

		}

		return true;

	}

	public function get_errors(){

		return $this->errors;

	}

}