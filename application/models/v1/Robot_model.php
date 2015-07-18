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
			'refresh',
			'test'
		), $input_parameters, null, true);

		// validate our input data
		if (!empty($validation_errors = $this->validate_robot_request($parameters))) {

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

		// check if the request is a test request
		if ($this->check_test_mode($parameters)) {

			return [
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

		}

		// check if the request will use cache
		$using_cache = $this->check_cache_request($parameters);

		// check if the request is a refresh request
		$refresh = $this->check_refresh_request($parameters);

		// filter the parameters to defaults
		$parameters = $this->default_parameters_filtering($parameters);

		// canonicalise parameter order for checksum generation
		ksort($parameters); 

		// checksum to compare snapshots
		$parameters_checksum = md5(json_encode($parameters));
		
		// generation datetime as the event ordinal
		$snapshot_generation_datetime = date('Y-m-d H:i:s');

		// we only try to read the cache if the cache is to be considered, and this wasn't a refresh request
		$cache = false;
		if ($using_cache AND !$refresh) {
			
			$cache = $this->read_cache(
				$user_id, 
				$parameters_checksum, 
				$parameters['cachetime'], 
				$snapshot_generation_datetime
			);

			// check if the cache is available and recent
			if ($cache AND $cache['status'] == 'fresh') {

				$response_array = json_decode($cache['data']['snapshotData'], true);
				$response_array['cache'] = true;
				return $response_array;

			} else {

				// if it's not recent, make it false
				$cache = false;
			
			}

		}

		// we are going to use a mutex and event to deal with cache stampede
		$mutex_sync = null;
		$event_sync = null;

		if ($using_cache) {

			$snapshot_identifier = "snapsearch_$parameters_checksum";

			// this is a nested/countable mutex
			$mutex_sync = new \SyncMutex($snapshot_identifier); 

			// if it's a refresh request, we want to a get a write lock, but we're not going to try and read from cache
			if (!$refresh) {

				// manual event sync passes through all waiters upon firing
				// if we know the cache is out of date, we are going to reset the event (winch it up ready to fire)
				$event_sync = new \SyncEvent($snapshot_identifier, true); 
				$event_sync->reset();

				list($type, $data) = $this->handle_cache_stampede($mutex_sync, $event_sync, $cache, $snapshot_identifier, 2);

			} else {

				list($type, $data) = $this->handle_refresh_cache_stampede($mutex_sync);
			
			}

			switch ($type) {

				case 'write': // got the write lock, proceed to regeneration
					// pass
				break;

				case 'read': // got the cached response, returning the cache

					$this->release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh);

					return $data;

				break;

				case 'timeout': // could not acquire write lock or did not receive an event firing

					log_message('error', "Snapsearch PHP application timed out in acquiring a lock to regenerate $snapshot_identifier.");

					$this->errors = array(
						'system_error'	=> 'Robot service timed out in acquiring a lock to regenerate the cache. Try again later.',
					);

					$this->release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh);

					return false;

				break;

				case 'limit': // exhausted cycle limit when trying to regenerate the cache

					log_message('error', "Snapsearch PHP application reached the cycle limit in regenerating $snapshot_identifier.");

					$this->errors = array(
						'system_error'	=> 'Robot service reached the cycle limit in cache regeneration attempts. Try again later.',
					);

					$this->release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh);

					return false;

				break;

			}

		}

		// cache has not been hit, proceed to the robot
		try{

			$request = $this->client->post(
				$this->robot_uri, 
				array(
					'Content-Type'	=> 'application/json'
				),
				json_encode($parameters)
			);

			// decode the returned json into an array
			$response = $request->send();
			$response_array = $response->json();
			
			// this is not a cached response
			$response_array['cache'] = false;

		}catch(BadResponseException $e){

			// a bad response exception can come from 400 or 500 errors, this should not happen
			log_message('error', 'Snapsearch PHP application received a 400/500 from Robot\'s load balancer or robot itself.');
			$this->errors = array(
				'system_error'	=> 'Robot service is a bit broken. Try again later.',
			);

			$this->release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh);

			return false;

		}catch(CurlException $e){

			log_message('error', 'Snapsearch PHP application received a curl error when contacting the robot load balancer. See: ' . $e->getMessage());
			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);

			$this->release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh);

			return false;

		}

		if($response_array['message'] == 'Failed'){

			$this->errors = [
				'validation_error'	=> [ 'url'	=> 'Robot could not open url: ' . $parameters['url'] ],
			];

			$this->release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh);

			return false;

		}

		// if we need to handle a redirect, and our handling fails, then we return false
		if (!$response_array = $this->handle_redirect_shim($parameters['url'], $response_array)) {

			$this->errors = [
				'system_error'	=> 'Curl failed. Try again later.'
			];

			$this->release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh);

			return false;

		}

		// recalculating the content-length header based on content-type character set if they exist
		$response_array = $this->recount_content_length($response_array);
		
		// only cache the result if the cache option was true
		if($using_cache){

			$response_string = json_encode($response_array);

			// upsert will use parameters checksum as the unique key
			// updating occurs if the current generation datetime is more recent or the same as the one in the database
			$this->upsert_cache(
				$user_id, 
				$parameters['url'], 
				$response_string, 
				$parameters_checksum, 
				$snapshot_generation_datetime
			);

			$this->release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh);

		}

		return $response_array;

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

	public function update_api_requests ($user_id) {

		$sql = "UPDATE user_accounts SET apiRequests = apiRequests + 1 WHERE id = ?";
		$this->db->query($sql, array($user_id));

		if ($this->db->affected_rows() > 0) {
			return true;
		} else {
			return false;
		}

	}

	public function update_api_usages ($user_id) {

		$sql = "UPDATE user_accounts SET apiUsage = apiUsage + 1 WHERE id = ?";
		$query = $this->db->query($sql, array($user_id));

		if ($this->db->affected_rows() > 0) {
			return true;
		} else {
			return false;
		}

	}

	public function get_errors(){

		return $this->errors;

	}

	protected function recount_content_length ($response_array) {

		// this is because the content-length from the headers can be different from the javascript generated content
		// this also has to deal with different character sets which may need multi-byte counting
		if(isset($response_array['headers'])){

			$content_charset_used = false;
			$content_charset = null;
			$content_length_used = false;
			$content_length_key = null;

			foreach($response_array['headers'] as $key => $header){

				if (strtolower($header['name']) == 'content-type') {
					if (preg_match('/;\s*?charset\s*?=\s*?(\S+)/i', $header['value'], $matches)) {
						$content_charset_used = true;
						$content_charset = $matches[1];
					}
					continue;
				}

				if (strtolower($header['name']) == 'content-length') {
					$content_length_used = true;
					$content_length_key = $key;
					continue;
				}

			}

			if ($content_length_used AND $content_charset_used) {

				// check if charset is valid charset, and detect it
				// silence the warning if it's gibberish
				if ($length = @mb_strlen($response_array['html'], $content_charset) !== false) {
					$response_array['headers'][$content_length_key]['value'] = $length;
				} else {
					// if it was indeed gibberish, then just count it based on utf-8
					$response_array['headers'][$content_length_key]['value'] = mb_strlen($response_array['html'], 'utf8');
				}
				
			} else if ($content_length_used) {
				
				// no charset, so we're just going to guess that it's utf8
				$response_array['headers'][$content_length_key]['value'] = mb_strlen($response_array['html'], 'utf8');
			
			}

		}

		return $response_array;

	}

	protected function handle_redirect_shim ($url, $response_array) {

		//SHIM: this is a shim for supporting the scraping of redirected pages, this is because slimerjs currently does not support acquiring the headers or body of a redirection request
		if($this->is_redirect($response_array['status'])){

			try{

				//we don't want to follow redirects in this case
				$request = $this->client->get($url, [
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

				return false;

			}

		}

		return $response_array;

	}

	protected function validate_url ($url) {

		// empty url!?
		if (empty($url)) return false;

		// prefix the url with http if necessary
	    if (!preg_match("~^(?:ht)tps?://~i", $url)) {
	        $url = "http://" . $url;
	    }

		$url_parts = parse_url($url);

		// malformed url
		if (!$url_parts OR !isset($url_parts['host'])) return false;

		// remove any whitespace
		$url_parts['host'] = trim($url_parts['host']);

		// remove any kind of `[]` for ipv6 because urls may be "[1080:0:0:0:8:800:200C:417A]""
		$url_parts['host'] = trim($url_parts['host'], "[]");

		// check if this an ip address
		// the regex checks for a top-level domain like (.com)
		// it is false if you pass things like "127.0.0.1" or "localhost" as these don't have top-level domains
		// the top-level domain needs at least one non-digit character so it does allow (.4a)
		if (!preg_match("~^[^\s/]+\.[^.\s/]*?[^.0-9\s/]~i", $url_parts['host'])) {

			// ok so it might be an ip address

			// we need to check for loopback addresses, these aren't well supported by filter_var
			// if they are loopbacks, we need to return false
			if (preg_match(
				"~^localhost$|^127(?:\.[0-9]+){0,2}\.[0-9]+$|^(?:0*\:)*?:?0*1$~i", 
				$url_parts['host'])
			) return false;

			// we're going to prevent local, private and reserved addresses
			// this also fails for "localhost" as it's not an ip address
			// return true if it's an ip address that works out
			return !!(filter_var(
				$url_parts['host'], 
				FILTER_VALIDATE_IP, 
				FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			));
		
		}

		// at this point we're pretty sure it's an actual domain, so we can return true

		// we still need to firewall the process using this url from accessing local, private and reserved addresses
		// because DNS resolution can still result in resolving to disallowed addresses 

		return true;

	}

	protected function validate_robot_request ($parameters) {

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
				'field'	=> 'initialwait',
				'label'	=> 'Initial wait (initialwait)',
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
				'rules'	=> 'greater_than_equal_to[1]|less_than_equal_to[720]',
			],
			[
				'field'	=> 'refresh',
				'label'	=> 'Refresh (refresh)',
				'rules'	=> 'boolean_style',
			],
			[
				'field'	=> 'test',
				'label'	=> 'Test Mode (test)',
				'rules'	=> 'boolean_style',
			]
		]);

		$validation_errors = [];

		//parameters that must exist
		if(!isset($parameters['url'])){
			$validation_errors['url'] = 'Url (url) is necessary.';
		}else{
			if (!$this->validate_url($url)) {
				$validation_errors['url'] = 'Url (url) must be a valid url containing http or https as the host and a proper host domain.';
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

		return $validation_errors;

	}

	protected function check_test_mode ($parameters) {

		if(isset($parameters['test'])){
			return filter_var($parameters['test'], FILTER_VALIDATE_BOOLEAN);
		} else {
			return false;
		}

	}

	protected function check_refresh_request ($parameters) {

		if(isset($parameters['refresh'])) {
			return filter_var($parameters['refresh'], FILTER_VALIDATE_BOOLEAN);
		} else {
			return false;
		}

	}

	protected function check_cache_request ($parameters) {

		//default cache parameter of true
		if(isset($parameters['cache'])){
			return filter_var($parameters['cache'], FILTER_VALIDATE_BOOLEAN);
		}else{
			return true;
		}

	}

	protected function default_parameters_filtering ($parameters) {

		// remove unnecessary parameters not relevant to snapshot uniqueness
		unset($parameters['test']);
		unset($parameters['refresh']);
		unset($parameters['cache']);

		// default javascriptenabled of true
		if(isset($parameters['javascriptenabled'])){
			$parameters['javascriptenabled'] = filter_var($parameters['javascriptenabled'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['javascriptenabled'] = true;
		}

		// default loadimages parameter of false
		if(isset($parameters['loadimages'])){
			$parameters['loadimages'] = filter_var($parameters['loadimages'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['loadimages'] = false;
		}

		// default imgformat of png
		if(!isset($parameters['imgformat'])) $parameters['imgformat'] = 'png';

		// default screenshot parameter of false
		if(isset($parameters['screenshot'])){
			$parameters['screenshot'] = filter_var($parameters['screenshot'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['screenshot'] = false;
		}

		//default navigate parameter of false, meaning we don't follow redirects
		if(isset($parameters['navigate'])){
			$parameters['navigate'] = filter_var($parameters['navigate'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['navigate'] = false;
		}

		//default meta parameter of true, so we do use meta parameters if they exist
		if(isset($parameters['meta'])){
			$parameters['meta'] = filter_var($parameters['meta'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['meta'] = true;
		}

		//default cachetime parameter of 24 hours
		if(!isset($parameters['cachetime'])) $parameters['cachetime'] = 24;

		return $parameters;

	}

	protected function release_locks_and_fire_events ($mutex_sync, $event_sync, $using_cache, $refresh) {

		if ($using_cache) {
			if (!$refresh) {
				$mutex_sync->unlock(true);
				$event_sync->fire();
			} else {
				$mutex_sync->unlock(true);
			}
		}

		return true;

	}

	protected function handle_refresh_cache_stampede ($mutex_sync) {

		if ($mutex_sync->lock(33000)) {

			return ['write', null];

		} else {

			return ['timeout', null];

		}

	}

	protected function handle_cache_stampede($mutex_sync, $event_sync, $cache, $snapshot_identifier, $cycle_limit) {

		// if the cycle reached 0, then, we do an early return of cycle limit
		if ($cycle_limit <= 0) {
			return ['limit', null];
		}

		// attempt to lock the mutex in order to regenerate the cache
		// if we succeed acquiring the lock, we just return 
		// if we fail to acquire the lock, we will try to return a valid most recent cache even though it is stale
		if ($mutex_sync->lock(0)) {

			return ['write', null];

		} else {

			if ($cache) {

				return ['read', $this->return_cached_response($cache)];
			
			} else {

				// if the cache was false, this means it does not exist currently
				// we will wait on the regenerating thread before reading
				// time limit of 33 seconds
				if ($event_sync->wait(33000)) {

					$cache = $this->read_cache(
						$user_id, 
						$parameters_checksum, 
						$parameters['cachetime'], 
						$snapshot_generation_datetime
					);

					if ($cache AND $cache['status'] == 'fresh') {

						return ['read', $this->return_cached_response($cache)];

					} else {

						log_message('error', "Snapsearch PHP application had to cycle once for regenerating $snapshot_identifier.");

						// if the cache was false or was not fresh
						// then the regenerating thread failed to regenerate the cache
						// at this point, we must cycle back to regenerating with a cycle limit of 1
						// cache will be false and the cycle will be decremented
						return $this->handle_cache_stampede($mutex_sync, $event_sync, false, $snapshot_identifier, --$cycle_limit);

					}

				} else {

					// timed out waiting for the event to fire
					// we must return an error
					// this should never happen
					return ['timeout', null];

				}

			}

		}

	}

	protected function return_cached_response ($cache) {

		$response_array = json_decode($cache['data']['snapshotData'], true);
		$response_array['cache'] = true;
		return $response_array;

	}

	protected function read_cache($user_id, $parameters_checksum, $parameters_cachetime, $generation_datetime){

		// get the snapshot record where it has a particular user id, and a particular checksum
		$this->db->where('userId', $user_id);
		$this->db->where('parametersChecksum', $parameters_checksum);

		$query = $this->db->get('snapshots');

		if($query->num_rows() > 0){
			
			$row = $query->row();

			$data = [
				'id'					=> $row->id,
				'userId'				=> $row->userId,
				'date'					=> $row->date,
				'snapshot'				=> $row->snapshot,
				'parametersChecksum'	=> $row->parametersChecksum
			];

			// database stores the time where snapshot was created
			// this looks for where sql_time >= (current_time - cachetime_period)
			$valid_timestamp = (new DateTime($generation_datetime))->sub(new DateInterval('PT' . $parameters_cachetime . 'H'))->getTimeStamp();

			if (strtotime($data['date']) >= $valid_timestamp) { // fresh
				
				$snapshot_file = new File($data['snapshot'], $this->filesystem);

				if(!$snapshot_file->exists()){
					$this->delete_cache($data['id']);
					return false;
				}

				$snapshot_data = bzdecompress($snapshot_file->getContent());
				$data['snapshotData'] = $snapshot_data;

				return [
					'status'	=> 'fresh',
					'data'		=> $data,
				];


			} else { //expired

				return [
					'status' 	=> 'expired',
					'data' 		=> $data,
				];

			}
			
		}else{
		
			return false;
		
		}

	}

	protected function upsert_cache($user_id, $url, $snapshot_data, $parameters_checksum, $generation_datetime) {

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

			// proceed with upsert
			// what's weird is that during an update, the only things that should need to be updated is snapshot name and date, the rest of the parameters don't really need to be updated
			$upsert_query =
				"INSERT INTO 
				 snapshots (
				 	userId, 
				 	url, 
				 	date, 
				 	snapshot, 
				 	parametersChecksum
				 ) 
				 VALUES (
				 	?, 
				 	?, 
				 	?, 
				 	?, 
				 	?
				 ) 
				 ON DUPLICATE KEY UPDATE 
				 	userId = IF(date <= VALUES(date), VALUES(userId), userId), 
				 	url = IF(date <= VALUES(date), VALUES(url), url), 
				 	snapshot = IF(date <= VALUES(date), VALUES(snapshot), snapshot), 
				 	parametersChecksum = IF(date <= VALUES(date), VALUES(parametersChecksum), parametersChecksum), 
				 	date = IF(date <= VALUES(date), VALUES(date), date),  		
				 ";


			$this->db->query($upsert_query, array(
				$user_id, 
				$url, 
				$generation_datetime, 
				$snapshot_name, 
				$parameters_checksum
			)); 

		}

		return true;

	}

	protected function delete_cache($id, $snapshot_name = false){

		$query = $this->db->delete('snapshots', array('id' => $id));

		if($snapshot_name){
			$snapshot_file = new File($snapshot_name, $this->filesystem);
			if($snapshot_file->exists()){
				$snapshot_file->delete();
			}
		}

		return true;

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