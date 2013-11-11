<?php

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;

class Robot_model extends CI_Model{

	protected $robot_uri;
	protected $client;
	protected $errors;
	protected $fallback;

	public function __construct(){

		parent::__construct();

		$this->robot_uri = 'http://127.0.0.1:8499';
		$this->client = new Client;
		$this->client->setUserAgent('Snapsearch');

		//validation libraries suck, we need to find a better way...
		//perhaps wrap over the Respect\Validator library (they have a couple of good validators!)
		//Provide a fluent interface, that allows the use of closures/anonymous functions as extensions
		//In fact it should operate similar to a DIC. Allowing you to register functions to be used as validators!
		//The whole point of the validator library is to provide shareable reusable validation routines and data
		//In an easy to understand way...
		$this->load->library('form_validation', false, 'validator');

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

		$this->validator->set_data($parameters);

		$this->validator->set_rules([
			[
				'field'	=> 'url',
				'label'	=> 'Url (url)',
				'rules'	=> 'required|trim|valid_url',
			],
			[
				'field'	=> 'width',
				'label'	=> 'Width (width)',
				'rules'	=> 'greater_than_equal_to[200]|less_than_equal_to[4000]',
			],
			[
				'field'	=> 'height',
				'label'	=> 'Height (height)',
				'rules'	=> 'greater_than_equal_to[200]|less_than_equal_to[4000]',
			],
			[
				'field'	=> 'imgformat',
				'label'	=> 'Image format (imgformat)',
				'rules'	=> 'image_format',
			],
			[
				'field'	=> 'useragent',
				'label'	=> 'Useragent (useragent)',
				'rules'	=> 'min_length[1]|max_length[2000]',
			],
			[
				'field'	=> 'screenshot',
				'label'	=> 'Screenshot (screenshot)',
				'rules'	=> 'boolean_style',
			],
			[
				'field'	=> 'navigate',
				'label'	=> 'Navigate (navigate)',
				'rules'	=> 'boolean_style',
			],
			[
				'field'	=> 'loadimages',
				'label'	=> 'Load images (loadimages)',
				'rules'	=> 'boolean_style',
			],
			[
				'field'	=> 'javascriptenabled',
				'label'	=> 'Javascript enabled (javascriptenabled)',
				'rules'	=> 'boolean_style',
			],
			[
				'field'	=> 'maxtimeout',
				'label'	=> 'Max timeout (maxtimeout)',
				'rules'	=> 'greater_than_equal_to[1000]|less_than_equal_to[15000]',
			],
			[
				'field'	=> 'callback',
				'label'	=> 'Callback (callback)',
				'rules'	=> 'min_length[1]|max_length[5000]',
			],
			[
				'field'	=> 'cache',
				'label'	=> 'Cache (cache)',
				'rules'	=> 'boolean_style',
			],
			[
				'field'	=> 'cachetime',
				'label'	=> 'Cache time (cachetime)',
				'rules'	=> 'greater_than_equal_to[1]|less_than_equal_to[50]',
			],
		]);

		$validation_errors = [];

		if(isset($parameters['maxtimeout']) AND isset($parameters['initialwait'])){
			//initialwait has to be lower than maxtimeout
			if($parameters['initialwait'] >= $parameters['maxtimeout']){
				$validation_errors['initialwait'] = 'Initial wait (initialwait) needs to be lower than Max timeout (maxtimeout)';
			}
		}

		if($this->validator->run() ==  false){
			$validation_errors += $this->validator->error_array();
		}

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);

			return false;

		}

		//default cache parameters of true and 24 hours
		if(isset($parameters['cache'])){
			$parameters['cache'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['cache'] = true;
		}
		if(!isset($parameters['cachetime'])) $parameters['cachetime'] = 24;

		$existing_cache_id = false;
		if($parameters['cache']){

			//we need the user id, for now we're going to assume 1 for everybody
			$cache = $this->read_cache($USER_ID, $parameters['url']);

			if($cache){

				//valid date is the current time minus $cache_time in hours
				$current_date = new DateTime();
				$valid_date = $current_date->sub(new DateInterval('PT' . $parameters['cachetime'] . 'H'))->format('Y-m-d H:i:s');

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
			//if there was a cache, we can pass back the fallback as well
			log_message('error', 'Snapsearch PHP application received a 400/500 from Robot\'s load balancer or robot itself.');
			$this->errors = array(
				'system_error'	=> 'Robot service is a bit broken. Try again later.',
			);
			if($existing_cache_id) $this->fallback = json_decode($cache['snapshot'], true);
			return false;

		}catch(CurlException $e){

			log_message('error', 'Snapsearch PHP application received a curl error when contacting the robot load balancer. See: ' . $e->getMessage());
			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);
			if($existing_cache_id) $this->fallback = json_decode($cache['snapshot'], true);
			return false;

		}

		if($response['message'] == 'Failed'){

			$this->errors = array(
				'error'	=> 'Robot could not open uri: ' . $parameters['url']
			);
			if($existing_cache_id) $this->fallback = json_decode($cache['snapshot'], true);
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

		//if id is available, that means we need to update, else we need to insert
		if($id){

			$this->db->where('id', $id);
			$query = $this->db->update('snapshots', array(
				'date'		=> date('Y-m-d H:i:s'),
				'snapshot'	=> $snapshot,
			));

			//should be able to update
			if($this->db->affected_rows() <= 0){

				return false;

			}

		}else{

			$query = $this->db->insert('snapshots', array(
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

	public function get_fallback(){

		return $this->fallback;

	}

}