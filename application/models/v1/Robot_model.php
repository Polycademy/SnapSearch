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
			$test_mode = filter_var($parameters['test'], FILTER_VALIDATE_BOOLEAN);
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

		// default filtering

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
			$parameters['meta'] = true
		}

		//default cache parameter of true
		if(isset($parameters['cache'])){
			$parameters['cache'] = filter_var($parameters['cache'], FILTER_VALIDATE_BOOLEAN);
		}else{
			$parameters['cache'] = true;
		}

		//default cachetime parameter of 24 hours
		if(!isset($parameters['cachetime'])) $parameters['cachetime'] = 24;

		//default refresh parameter of false, remove it from the parameters array to prevent it from being hashed
		if(isset($parameters['refresh'])) {
			$refresh = filter_var($parameters['refresh'], FILTER_VALIDATE_BOOLEAN);
		} else {
			$refresh = false;
		}
		unset($parameters['refresh']);		

		//canonicalise the parameters, so we get the same parameter checksum for the same content, and not for different ordering
		ksort($parameters);

		//we need a checksum of the parameters to compare with the cache's checksum
		$parameters_checksum = md5(json_encode($parameters));

		// this will be the date that is assigned to snapshots 
		// it corresponds to starting the snapshot creation process 
		// this allows a more accurate comparison of snapshot event order
		$snapshot_generation_datetime = date('Y-m-d H:i:s');

		// if cache == true, we try to read the cache
		// if cache == false, we skip trying to read the cache
		// also we don't cache the generated snapshot either
		// it's as if the cache never existed
		// also pass in the refresh parameter to make the read_cache function realise there's no need to call amazon s3
		// also pass in the snapshot generation datetime to see if the cache has expired
		if ($parameters['cache'] AND !$refresh) {
			
			$cache = $this->read_cache(
				$user_id, 
				$parameters_checksum, 
				$parameters['cachetime'], 
				$snapshot_generation_datetime
			);

			// OK so we have the cache now.
			// We need to check if the cache is recent enough to send it over.
			if ($cache AND $cache['status'] == 'fresh') {

				$response_array = json_decode($cache['data']['snapshotData'], true);
				$response_array['cache'] = true;
				return $response_array;

			}

			// the cache may be false or expired

		} else {

			$cache = false;

		}

		// we need to integrate refresh request logic, which we haven't done yet
		// would a refresh request wait upon a regenerating thread?
		// yes, because refresh requests need to be serialized, or else it might finish updating prior to the regenerating thread
		// so yes, it does acquire a write lock

		// we are going to use a mutex and event to synchronise cache stampede
		if ($parameters['cache']) {
			
			// this is a nested/countable mutex
			$mutex_sync = new \SyncMutex("snapsearch_$parameters_checksum"); 
			// manual event sync passes through all waiters upon firing
			$event_sync = new \SyncEvent("snapsearch_$parameters_checksum", true); 
			// if we know the cache is out of date, we are going to reset the event (winch it up ready to fire)
			$event_sync->reset();
			
			// this is a procedure that may short return in order to continue
			// 3 situations:
			// 1. we've got the write lock, so we shall proceed with regeneration
			// 2. timeout failure so we return from this function false
			// 3. the cache has been regenerated and so it read from the cache and succeeded
			list($type, $data) = $this->handle_cache_stampede($mutex_sync, $event_sync, $cache, 2);

			switch ($type) {

				case 'write':
					// pass
					break;

				case 'read': 

					return $data;

					break;

				case 'timeout':

					log_message('error', 'Snapsearch PHP application timed out in acquiring a lock to regenerate the cache.');

					$this->errors = array(
						'system_error'	=> 'Robot service timed out in acquiring a lock to regenerate the cache. Try again later.',
					);

					return false;

					break;

				case 'limit': 

					log_message('error', 'Snapsearch PHP application reached the cycle limit in cache regeneration attempts.');

					$this->errors = array(
						'system_error'	=> 'Robot service reached the cycle limit in cache regeneration attempts. Try again later.',
					);

					return false;

					break;

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
			if ($parameters['cache']) $this->db->query("DO RELEASE_LOCK('snapsearch_$parameters_checksum')");
			return false;

		}catch(CurlException $e){

			log_message('error', 'Snapsearch PHP application received a curl error when contacting the robot load balancer. See: ' . $e->getMessage());
			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);
			if ($parameters['cache']) $this->db->query("DO RELEASE_LOCK('snapsearch_$parameters_checksum')");
			return false;

		}

		if($response_array['message'] == 'Failed'){

			$this->errors = array(
				'validation_error'	=> [
					'url'	=> 'Robot could not open url: ' . $parameters['url'],
				],
			);
			if ($parameters['cache']) $this->db->query("DO RELEASE_LOCK('snapsearch_$parameters_checksum')");
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
				if ($parameters['cache']) $this->db->query("DO RELEASE_LOCK('snapsearch_$parameters_checksum')");
				return false;

			}

		}

		//recalculating the content-length headers based on content-type if they exist
		//this is because the content-length that came from the server could be different from the final resolve true length due to asynchronous content
		if(isset($response_array['headers'])){

			$content_charset_used = false;
			$content_charset = null;
			$content_length_used = false;

			foreach($response_array['headers'] as $key => $header){
				if (strtolower($header['name']) == 'content-type') {
					if (preg_match('/;\s*?charset\s*?=\s*?(\S+)/i', $header['value'], $matches)) {
						$content_charset_used = true;
						$content_charset = $matches[1];
					}
				}
				if (strtolower($header['name']) == 'content-length') {
					$content_length_used = true;
				}
			}

			if ($content_charset_used AND $content_length_used) {

				// check if charset is valid charset, and detect it
				// silence the warning if it's gibberish
				if ($length = @mb_strlen($response_array['html'], $content_charset) !== false) {
					$response_array['headers'][$key]['value'] = $length;
				} else {
					// if it was indeed gibberish, then just count it based on utf-8
					$response_array['headers'][$key]['value'] = mb_strlen($response_array['html'], 'utf8');
				}
				
			} elseif ($content_length_used) {
				// no charset, so we're just going to guess that it's utf8
				$response_array['headers'][$key]['value'] = mb_strlen($response_array['html'], 'utf8');
			}

		}
		
		//only cache the result if the cache option was true, subsequent requests would never request for cached data that had their cache parameter as false, because matching checksums would require the request's parameters to also have cache being false, which would prevent us from requesting from the cache
		if($parameters['cache']){

			$response_string = json_encode($response_array);

			// upsert will use parameters checksum as the unique key
			// updating will only occur if the current generation datetime is more recent or the same as the one in the table
			$this->upsert_cache(
				$user_id, 
				$parameters['url'], 
				$response_string, 
				$parameters_checksum, 
				$snapshot_generation_datetime
			);

			$this->db->query("DO RELEASE_LOCK('snapsearch_$parameters_checksum')");

			// REMOVED due to upsert
			// if($existing_cache_id){
			// 	$this->update_cache($existing_cache_id, $existing_cache_name, $response_string);
			// }else{
			// 	$this->insert_cache($user_id, $parameters['url'], $response_string, $parameters_checksum);
			// }
			// REMOVED

		}

		return $response_array;

	}

	protected function handle_cache_stampede($mutex_sync, $event_sync, $cache, $cycle_limit) {

		// later on we need $event_sync->fire();

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

						// if the cache was false or was not fresh
						// then the regenerating thread failed to regenerate the cache
						// at this point, we must cycle back to regenerating with a cycle limit of 1
						// cache will be false and the cycle will be decremented
						return $this->handle_cache_stampede($mutex_sync, $event_sync, false, --$cycle_limit);

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

	// never run read_cache when refresh=true
	public function read_cache($user_id, $parameters_checksum, $parameters_cachetime, $generation_datetime){

		// get the snapshot record where it has a particular user id, and a particular checksum, and that it is most recent snapshot
		$this->db->where('userId', $user_id);
		$this->db->where('parametersChecksum', $parameters_checksum);
		// $this->db->order_by('date', 'DESC');
		// $this->db->limit(1);

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

			if (strtotime($data['date']) >= $valid_timestamp) {
				// fresh

				// do this section ONLY if refresh == false
				// if we are refreshing, there's no need to acquire the actual snapshot data
				// because we don't use the actual snapshot data at all
				// we have enough information from our own database to return
				// if (!$refresh) {

					$snapshot_file = new File($data['snapshot'], $this->filesystem);

					if(!$snapshot_file->exists()){
						$this->delete_cache($data['id']);
						return false;
					}

					$snapshot_data = bzdecompress($snapshot_file->getContent());
					$data['snapshotData'] = $snapshot_data;

				// }

				return [
					'status'	=> 'fresh',
					'data'		=> $data,
				];


			} else {
				//expired

				return [
					'status' 	=> 'expired',
					'data' 		=> $data,
				];

			}
			
		}else{
		
			return false;
		
		}

	}

	public function upsert_cache($user_id, $url, $snapshot_data, $parameters_checksum, $generation_datetime) {

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