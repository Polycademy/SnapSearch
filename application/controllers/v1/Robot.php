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

			$query = $this->Robot_model->read_site($user_id, $parameters);

			if($query){
				
				//only update the api usage if it wasn't a cached response
				if(empty($query['cache'])){
					$this->update_api_usage();
				}

				$content = $query; //assign query
				$code = 'success'; //assign code
				
			}else{

				$errors = $this->Robot_model->get_errors();
				$fallback = $this->Robot_model->get_fallback();

				if(!empty($fallback)){
					$content = array(
						'error'		=> current($errors),
						'fallback'	=> $fallback,
					);
				}else{
					$content = current($errors);
				}
			
				$code = key($errors);
				
			}

			if($code == 'success'){
				$this->auth_response->setStatusCode(200);
			}elseif($code == 'validation_error' OR $code == 'error'){
				$this->auth_response->setStatusCode(400);
			}elseif($code == 'system_error'){
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
			return false;
		}

		return true;

	}

	protected function update_api_requests(){

		$api_requests = $this->user['apiRequests'] + 1;

		$this->Accounts_model->update($user['id'], [
			'apiRequests'	=> $api_requests,
		]);

	}

	protected function update_api_usage(){

		$api_usage = $this->user['apiUsage'] + 1;

		$this->Accounts_model->update($user['id'], [
			'apiUsage'	=> $api_usage,
		]);

	}

}