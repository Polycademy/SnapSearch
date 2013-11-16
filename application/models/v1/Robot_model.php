<?php

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

class Robot_model extends CI_Model{

	protected $robot_uri;
	protected $client;
	protected $filesystem;
	protected $errors;
	protected $fallback;

	public function __construct(){

		parent::__construct();

		$this->robot_uri = 'http://127.0.0.1:8499';
		
		$this->client = new Client;
		$this->client->setUserAgent('Snapsearch');

		//snapshots should be a relative directory to index.php
		$this->filesystem = new Filesystem(new LocalAdapter('snapshots', true));

		$this->load->library('form_validation', false, 'validator');

	}

	public function read_site($input_parameters){

		$USER_ID = 1;

		$parameters = elements(array(
			'url',
			'width',
			'height',
			'imgformat',
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
				'rules'	=> 'required|trim|prep_url|valid_url',
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
			$parameters['cache'] = filter_var($parameters['cache'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['cache'] = true;
		}
		if(!isset($parameters['cachetime'])) $parameters['cachetime'] = 24;

		//we need a checksum of the parameters to compare with the cache's checksum
		$parameters_checksum = md5(json_encode($parameters));

		$existing_cache_id = false;
		$existing_cache_name = false;
		//we need the user id, for now we're going to assume 1 for everybody
		$cache = $this->read_cache($USER_ID, $parameters_checksum);

		if($cache){

			//we can update the cache when it's invalid
			//but also it can still be used if we run against an error
			$existing_cache_id = $cache['id'];
			$existing_cache_name = $cache['snapshot'];

			if($parameters['cache']){

				//valid date is the current time minus $cache_time in hours
				$current_date = new DateTime();
				$valid_date = $current_date->sub(new DateInterval('PT' . $parameters['cachetime'] . 'H'))->format('Y-m-d H:i:s');

				//the cache's date of entry has to be more recent or equal to the valid date
				if(strtotime($cache['date']) >= strtotime($valid_date)){
					return json_decode($cache['snapshotData'], true);
				}

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

			//decode the returned json into an array
			$response = $request->send();
			$response_string = $response->getBody(true);
			$response_array = $response->json();

		}catch(BadResponseException $e){

			//a bad response exception can come from 400 or 500 errors, this should not happen
			//if there was a cache, we can pass back the fallback as well
			log_message('error', 'Snapsearch PHP application received a 400/500 from Robot\'s load balancer or robot itself.');
			$this->errors = array(
				'system_error'	=> 'Robot service is a bit broken. Try again later.',
			);
			if($existing_cache_id) $this->fallback = json_decode($cache['snapshotData'], true);
			return false;

		}catch(CurlException $e){

			log_message('error', 'Snapsearch PHP application received a curl error when contacting the robot load balancer. See: ' . $e->getMessage());
			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);
			if($existing_cache_id) $this->fallback = json_decode($cache['snapshotData'], true);
			return false;

		}

		if($response_array['message'] == 'Failed'){

			$this->errors = array(
				'error'	=> 'Robot could not open uri: ' . $parameters['url']
			);
			if($existing_cache_id) $this->fallback = json_decode($cache['snapshotData'], true);
			return false;

		}
		
		//only cache the result if the cache option was true, subsequent requests would never request for cached data that had their cache parameter as false, because matching checksums would require the request's parameters to also have cache being false, which would prevent us from requesting from the cache
		if($parameters['cache']){

			if($existing_cache_id){
				$this->update_cache($existing_cache_id, $existing_cache_name, $response_string);
			}else{
				$this->insert_cache($USER_ID, $parameters['url'], $response_string, $parameters_checksum);
			}

		}

		return $response_array;

	}

	public function read_cache($user_id, $parameters_checksum){

		//get the snapshot record for the relevant user and url
		$query = $this->db->get_where(
			'snapshots', 
			array(
				'userId' 				=> $user_id, 
				'parametersChecksum'	=> $parameters_checksum,
			)
		);

		if($query->num_rows() > 0){
			
			$row = $query->row();

			$data = [
				'id'					=> $row->id,
				'userId'				=> $row->userId,
				'date'					=> $row->date,
				'snapshot'				=> $row->snapshot,
				'parametersChecksum'	=> $row->parametersChecksum
			];

			if(!$this->filesystem->has($data['snapshot'])){
				$this->delete_cache($data['id']);
				return false;
			}

			$snapshot_data = $this->filesystem->read($data['snapshot']);
			$snapshot_data = gzuncompress($snapshot_data);
			$data['snapshotData'] = $snapshot_data;

			return $data;
			
		}else{
		
			return false;
		
		}

	}

	public function insert_cache($user_id, $url, $snapshot_data, $parameters_checksum){

		//get a unique filename first
		do{
			$snapshot = uniqid('snapshot', true);
			if(!$this->filesystem->has($snapshot)) break;
		}while(true);

		//compress the data
		$snapshot_data = gzcompress($snapshot_data, 9);

		//write the snapshot data, and potentially overwrite it
		$this->filesystem->write($snapshot, $snapshot_data, true);

		//insert the snapshot filename to the database
		$query = $this->db->insert('snapshots', array(
			'userId'				=> $user_id,
			'url'					=> $url,
			'date'					=> date('Y-m-d H:i:s'),
			'snapshot'				=> $snapshot,
			'parametersChecksum'	=> $parameters_checksum,
		));

		if(!$query){
			$msg = $this->db->error()['message'];
			$num = $this->db->error()['code'];
			$last_query = $this->db->last_query();
			log_message('error', 'Problem inserting into snapshots table: ' . $msg . ' (' . $num . '), using this query: "' . $last_query . '"');
		}

		return true;

	}

	public function update_cache($id, $snapshot, $snapshot_data){

		//update the date timestamp for this cached data
		$this->db->where('id', $id);
		$query = $this->db->update('snapshots', array(
			'date'	=> date('Y-m-d H:i:s'),
		));

		//compress the snapshot
		$snapshot_data = gzcompress($snapshot_data, 9);

		//overwrite the cached file
		$this->filesystem->write($snapshot, $snapshot_data, true);

		return true;

	}

	public function delete_cache($id, $filename = false){

		$query = $this->db->delete('snapshots', array('id' => $id));

		if($filename AND $this->filesystem->has($filename)){
			$this->filesystem->delete($filename);
		}

		return true;

	}

	public function purge_cache($allowed_length = false, $user_id = false){

		$cutoff_date = false;
		try{
			if($allowed_length){
				$current_date = new DateTime();
				$allowed_length = new DateInterval($allowed_length);
				$cutoff_date = $current_date->sub($allowed_length)->format('Y-m-d H:i:s');
			}
		}catch(Exception $e){
			return $e->getMessage();
		}

		if($cutoff_date){
			$this->db->where('date <', $cutoff_date);
		}

		if(is_int($user_id)){
			$this->db->where('userId', $user_id);
		}

		$query = $this->db->get('snapshots');

		if($query->num_rows() > 0){
			
			foreach($query->result() as $row){

				$this->delete_cache($row->id, $row->snapshot);

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