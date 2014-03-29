<?php

class Robot extends CI_Controller{

	protected $request;
	protected $authenticator;
	protected $auth_response;
	protected $user;

	public function __construct(){

		parent::__construct();

		$this->load->model('v1/Robot_model');
		$this->load->model('Accounts_model');
		$this->load->model('Log_model');

		$ioc = $this->config->item('ioc');

		$this->request = $ioc['Request'];

		$this->authenticator = $ioc['PolyAuth\Authenticator'];
		$this->authenticator->start();

		$this->auth_response = $this->authenticator->get_response();
		$this->user = $this->authenticator->get_user();

	}

	public function query(){

		$this->query_robot($this->request->query->all());

	}

	public function post(){

		$this->query_robot($this->request->request->all());

	}

	protected function query_robot($parameters){

		if(!$this->user->authorized()){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to use SnapSearch.';
			$code = 'error';

		}elseif($this->reached_api_limit()){

			$this->auth_response->setStatusCode(429);
			$content = 'Reached API Limit.';
			$code = 'error';

		}else{

			$this->update_api_requests();

			$user_id = $this->user['id'];

			$start_time = (integer) round(microtime(true));
			$query = $this->Robot_model->read_site($user_id, $parameters);
			$end_time = (integer) round(microtime(true));

			if($query){
				
				//only update the api usage if it wasn't a cached response, which means the value is identical to false
				//if the cache value is null, then it was in test mode
				if(isset($query['cache']) AND $query['cache'] === false){
					$this->update_api_usage();
				}

				$this->update_log($parameters, $query, $end_time - $start_time);

				$content = $query; //assign query
				$code = 'success'; //assign code
				
			}else{

				$content = current($this->Robot_model->get_errors());
				$code = key($this->Robot_model->get_errors());
				
			}

			if($code == 'success'){
				$this->auth_response->setStatusCode(200);
			}elseif($code == 'validation_error'){
				$this->auth_response->setStatusCode(400);
			}elseif($code == 'system_error' OR $code == 'error'){
				//normal errors would be server errors, here's no 404s here
				$this->auth_response->setStatusCode(500);
			}

		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);
		
		Template::compose(false, $output, 'json');

	}

	protected function reached_api_limit(){

		//assuming this user is already authorized, since we check the authorisation
		if(!$this->user->authorized()){
			return false;
		}

		$api_limit = $this->user['apiLimit'];
		$api_usage = $this->user['apiUsage'] + 1;

		if($api_usage > $api_limit){
			return true;
		}

		return false;

	}

	protected function update_api_requests(){

		$api_requests = $this->user['apiRequests'] + 1;

		$this->Accounts_model->update($this->user['id'], [
			'apiRequests'	=> $api_requests,
		]);

	}

	protected function update_api_usage(){

		$api_usage = $this->user['apiUsage'] + 1;

		$this->Accounts_model->update($this->user['id'], [
			'apiUsage'	=> $api_usage,
		]);

	}

	/**
	 * Updates the log table
	 * 
	 * @param  array   $parameters    Request parameters
	 * @param  array   $query         Query result from Robot_model
	 * @param  integer $response_time Time in seconds
	 */
	protected function update_log($parameters, $query, $response_time){

		if(isset($query['cache']) AND $query['cache'] === false){
			$type = 'uncached';
		}else{
			$type = 'cached';
		}

		$this->Log_model->create([
            'userId'		=> $this->user['id'],
            'type'			=> $type,
            'url'			=> $parameters['url'],
            'responseTime'	=> $response_time,
		]);

	}

}