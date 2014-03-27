<?php

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;

use Aws\S3\S3Client;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3 as AwsS3Adapter;
use Gaufrette\File;

class Robot_model extends CI_Model{

	protected $robot_uri;
	protected $client;
	protected $filesystem;
	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->robot_uri = 'http://127.0.0.1:8499';
		
		$this->client = new Client;
		$this->client->setUserAgent('Snapsearch', true);

		//using amazon s3 to store the snapshot cache, it will be stored in the snapsearch bucket, and if the bucket doesn't exist, it will create it
		$this->filesystem = new Filesystem(
			new AwsS3Adapter(
				S3Client::factory([
					'key'		=> $_ENV['secrets']['s3_api_key'],
					'secret'	=> $_ENV['secrets']['s3_api_secret'],
				]),
				'snapsearch',
				[
					'create'	=> true
				]
			)
		);

		$this->load->library('form_validation', false, 'validator');

	}

	public function read_site($user_id, $input_parameters){

		$parameters = elements(array(
			'url',
			'useragent',
			'width',
			'height',
			'imgformat',
			'screenshot',
			'navigate',
			'loadimages',
			'javascriptenabled',
			'totaltimeout',
			'maxtimeout',
			'initialwait',
			'callback',
			'meta',
			'cache',
			'cachetime',
			'test'
		), $input_parameters, null, true);

		$this->validator->set_data($parameters);

		$this->validator->set_rules([
			[
				'field'	=> 'url',
				'label'	=> 'Url (url)',
				'rules'	=> 'required',
			],
			[
				'field'	=> 'useragent',
				'label'	=> 'User Agent (useragent)',
				'rules'	=> 'max_length[120]',
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
				'field'	=> 'totaltimeout',
				'label'	=> 'Total timeout (totaltimeout)',
				'rules'	=> 'greater_than_equal_to[10000]|less_than_equal_to[30000]',
			],
			[
				'field'	=> 'maxtimeout',
				'label'	=> 'Max timeout (maxtimeout)',
				'rules'	=> 'greater_than_equal_to[1000]|less_than_equal_to[15000]',
			],
			[
				'field'	=> 'callback',
				'label'	=> 'Callback (callback)',
				'rules'	=> 'min_length[1]|max_length[8000]',
			],
			[
				'field'	=> 'meta',
				'label'	=> 'Meta (meta)',
				'rules'	=> 'boolean_style',
			],
			[
				'field'	=> 'cache',
				'label'	=> 'Cache (cache)',
				'rules'	=> 'boolean_style',
			],
			[
				'field'	=> 'cachetime',
				'label'	=> 'Cache time (cachetime)',
				'rules'	=> 'greater_than_equal_to[1]|less_than_equal_to[200]',
			],
			[
				'field'	=> 'test',
				'label'	=> 'Test Mode (test)',
				'rules'	=> 'boolean_style'
			]
		]);

		$validation_errors = [];

		//parameters that must exist
		if(!isset($parameters['url'])){
			$validation_errors['url'] = 'Url (url) is necessary.';
		}else{
			//validate the url because valid_url sucks
			$url_parts = parse_url($parameters['url']);
			if($url_parts){
				if(
					!isset($url_parts['scheme']) 
					OR !isset($url_parts['host']) 
					OR ($url_parts['scheme'] != 'http' AND $url_parts['scheme'] != 'https')
				){
					$validation_errors['url'] = 'Url (url) must be a valid url containing http or https as the host and a proper host domain.';
				}
			}else{
				$validation_errors['url'] = 'Url (url) is malformed.';
			}
		}

		if(isset($parameters['maxtimeout']) AND isset($parameters['initialwait'])){
			//initialwait has to be lower than maxtimeout
			if($parameters['initialwait'] >= $parameters['maxtimeout']){
				$validation_errors['initialwait'] = 'Initial wait (initialwait) needs to be lower than Max timeout (maxtimeout).';
			}
		}

		if($this->validator->run() ==  false){
			//overwrite the validation with form validation
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);

			return false;

		}

		//is it in test mode?
		if(isset($parameters['test'])){
			$test_mode = filter_var($parameters['cache'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$test_mode = false;
		}
		unset($parameters['test']);

		//if test mode is true, just respond with a message, as test mode has succeeded!
		if($test_mode){

			$response_array = [
				'cache' 			=> null,
				'callbackResult'	=> '',
				'date'				=> time(),
				'headers'			=> [],
				'html'				=> '',
				'message'			=> 'You are in test mode. Your request was received!',
				'pageErrors'		=> [],
				'screensot'			=> '',
				'status'			=> 200
			];

			return $response_array;

		}

		//default navigate parameter of false, meaning we don't follow redirects
		if(isset($parameters['navigate'])){
			$parameters['navigate'] = filter_var($parameters['navigate'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['navigate'] = false;
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
		$cache = $this->read_cache($user_id, $parameters_checksum);

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
					$response_array = json_decode($cache['snapshotData'], true);
					$response_array['cache'] = true;
					return $response_array;
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
			$response_array = $response->json();
			
			//this is not a cached response
			$response_array['cache'] = false;

		}catch(BadResponseException $e){

			//a bad response exception can come from 400 or 500 errors, this should not happen
			log_message('error', 'Snapsearch PHP application received a 400/500 from Robot\'s load balancer or robot itself.');
			$this->errors = array(
				'system_error'	=> 'Robot service is a bit broken. Try again later.',
			);
			return false;

		}catch(CurlException $e){

			log_message('error', 'Snapsearch PHP application received a curl error when contacting the robot load balancer. See: ' . $e->getMessage());
			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);
			return false;

		}

		if($response_array['message'] == 'Failed'){

			$this->errors = array(
				'validation_error'	=> [
					'url'	=> 'Robot could not open url: ' . $parameters['url'],
				],
			);
			return false;

		}

		//SHIM: this is a shim for supporting the scraping of redirected pages, this is because slimerjs currently does not support acquiring the headers or body of a redirection
		if($this->is_redirect($response_array['status'])){

			try{

				//we don't want to follow redirects in this case
				$request = $this->client->get($parameters['url'], [
                    'Accept-Encoding'   => 'gzip, deflate, identity',
				], [
					'allow_redirects'	=> false,
                    'exceptions'        => false
				]);

				$response = $request->send();

				//shim the headers as [['name' => 'Header Name', 'value' => 'Header Value']]
				$response_array['headers'] = [];
				foreach($response->getHeaders() as $header_key => $header_value){
					$response_array['headers'][] = [
						'name'	=> (string) $header_key,
						'value'	=> (string) $header_value,
					];
				}

				//shim the body
				$response_array['html'] = $response->getBody(true);

			}catch(CurlException $e){

				$this->errors = array(
					'system_error'	=> 'Curl failed. Try again later.'
				);
				return false;

			}

		}

		//recalculating the content-length headers if they exist
		//this is because the content-length that came from the server could be different from the final resolve true length due to asynchronous content
		if(isset($response_array['headers'])){
			foreach($response_array['headers'] as $key => $header){
				if(strtolower($header['name']) == 'content-length'){
					$response_array['headers'][$key]['value'] = mb_strlen($response_array['html'], 'utf8');
				}
			}
		}
		
		//only cache the result if the cache option was true, subsequent requests would never request for cached data that had their cache parameter as false, because matching checksums would require the request's parameters to also have cache being false, which would prevent us from requesting from the cache
		if($parameters['cache']){

			$response_string = json_encode($response_array);

			if($existing_cache_id){
				$this->update_cache($existing_cache_id, $existing_cache_name, $response_string);
			}else{
				$this->insert_cache($user_id, $parameters['url'], $response_string, $parameters_checksum);
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

			$snapshot_file = new File($data['snapshot'], $this->filesystem);

			if(!$snapshot_file->exists()){
				$this->delete_cache($data['id']);
				return false;
			}

			$snapshot_data = bzdecompress($snapshot_file->getContent());
			$data['snapshotData'] = $snapshot_data;

			return $data;
			
		}else{
		
			return false;
		
		}

	}

	public function insert_cache($user_id, $url, $snapshot_data, $parameters_checksum){

		//get a unique filename first
		$snapshot_name = uniqid('snapshot', true);

		//compress the data
		$snapshot_data = bzcompress($snapshot_data, 9);

		//setup the file object
		$snapshot_file = new File($snapshot_name, $this->filesystem);

		//write the snapshot data, and potentially overwrite it
		$s3_query = $snapshot_file->setContent($snapshot_data, [
			'ContentType'	=> 'application/x-bzip2',
			'StorageClass'	=> 'REDUCED_REDUNDANCY'
		]);

		if($s3_query){

			//insert the snapshot filename to the database
			$this->db->insert('snapshots', array(
				'userId'				=> $user_id,
				'url'					=> $url,
				'date'					=> date('Y-m-d H:i:s'),
				'snapshot'				=> $snapshot_name,
				'parametersChecksum'	=> $parameters_checksum,
			));

		}

		return true;

	}

	public function update_cache($id, $snapshot_name, $snapshot_data){

		//update the date timestamp for this cached data
		$this->db->where('id', $id);
		$query = $this->db->update('snapshots', array(
			'date'	=> date('Y-m-d H:i:s'),
		));

		//compress the snapshot
		$snapshot_data = bzcompress($snapshot_data, 9);

		//setup the file object
		$snapshot_file = new File($snapshot_name, $this->filesystem);

		//update the cached file
		$snapshot_file->setContent($snapshot_data, [
			'ContentType'	=> 'application/x-bzip2',
			'StorageClass'	=> 'REDUCED_REDUNDANCY'
		]);

		return true;

	}

	public function delete_cache($id, $snapshot_name = false){

		$query = $this->db->delete('snapshots', array('id' => $id));

		if($snapshot_name){
			$snapshot_file = new File($snapshot_name, $this->filesystem);
			if($snapshot_file->exists()){
				$snapshot_file->delete();
			}
		}

		return true;

	}

	public function purge_cache($allowed_length = false, $user_id = false){

		$cutoff_date = false;
		try{
			if($allowed_length){
				$current_date = new DateTime;
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

	protected function is_redirect($status){

		//shim for null status https://github.com/laurentj/slimerjs/issues/167
		if(is_null($status)){
			return true;
		}

		return in_array((string) $status, [
			'301',
			'302',
			'303',
			'305',
			'306',
			'307',
			'308'
		]);

	}

}