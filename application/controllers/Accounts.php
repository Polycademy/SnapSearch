<?php

class Accounts extends CI_Controller{

	protected $authenticator;
	protected $auth_response;
	protected $user;

	protected $api_free_limit;
	protected $charge_interval;

	public function __construct(){

		parent::__construct();

		//models
		$this->load->model('Accounts_model');

		$ioc = $this->config->item('ioc');

		//authentication
		$this->authenticator = $ioc['PolyAuth\Authenticator'];
		$this->authenticator->start();
		$this->auth_response = $this->authenticator->get_response();
		$this->user = $this->authenticator->get_user();

		//configurable properties
		$this->api_free_limit = 1500;
		$this->charge_interval = 'P30D';

	}

	public function index(){

		$offset = $this->input->get('offset', true);
		$limit = $this->input->get('limit', true);

		if(empty($limit)) $limit = 100;
		if(empty($offset)) $offset = 0;

		$query = $this->Accounts_model->read_all($offset, $limit);

		if($query){

			//if admin we get to see all the details, but if not admin we need to remove properties
			if(!$this->user->authorized(['roles' => 'admin'])){
				foreach($query as &$user){
					unset(
						$user['email'],
						$user['passwordChange'],
						$user['activationCode'],
						$user['forgottenCode'],
						$user['forgottenDate'],
						$user['autoCode'],
						$user['autoDate'],
						$user['sharedKey'],
						$user['apiLimit'],
						$user['apiFreeLimit'],
						$user['apiUsage'],
						$user['apiRequests'],
						$user['apiLeftOverUsage'],
						$user['apiLeftOverCharge'],
						$user['chargeInterval'],
						$user['chargeDate']
					);
				}
			}

			$content = $query;
			$code = 'success';
			
		}else{

			$this->auth_response->setStatusCode(404);
			$content = current($this->Accounts_model->get_errors());
			$code = key($this->Accounts_model->get_errors());
			
		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=>$code,
		);
		
		Template::compose(false, $output, 'json');

	}

	//this is for admin, single logged in user, and others
	public function show($id){

		$query = $this->Accounts_model->read($id);

		if($query){

			//if user is not an admin and does not match the user id
			//then unset all the private variables	
			if(!$this->user->authorized([
				'roles'	=> 'admin'
			], [
				'users'	=> $id
			])){	
				unset(
					$query['email'],
					$query['passwordChange'],
					$query['activationCode'],
					$query['forgottenCode'],
					$query['forgottenDate'],
					$query['autoCode'],
					$query['autoDate'],
					$query['sharedKey'],
					$query['apiLimit'],
					$query['apiFreeLimit'],
					$query['apiUsage'],
					$query['apiRequests'],
					$query['apiLeftOverUsage'],
					$query['apiLeftOverCharge'],
					$query['chargeInterval'],
					$query['chargeDate']
				);
			}

			$content = $query;
			$code = 'success';
		
		}else{
		
			$this->auth_response->setStatusCode(404);
			$content = current($this->Accounts_model->get_errors());
			$code = key($this->Accounts_model->get_errors());
		
		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);
		
		Template::compose(false, $output, 'json');

	}

	public function create(){

		$data = $this->input->json(false);

		if(!$this->user->authorized(['roles' => 'admin'])){
			unset(
				$data['apiLimit'],
				$data['apiFreeLimit'],
				$data['chargeInterval']
			);
		}

		if(!isset($data['apiLimit'])) $data['apiLimit'] = $this->api_free_limit;
		if(!isset($data['apiFreeLimit'])) $data['apiFreeLimit'] = $this->api_free_limit;
		if(!isset($data['chargeInterval'])) $data['chargeInterval'] = $this->charge_interval;

		$query = $this->Accounts_model->create($data);
		
		if($query){
		
			$this->auth_response->setStatusCode(201);
			$content = $query; //resource id
			$code = 'success';
		
		}else{		
			
			$content = current($this->Accounts_model->get_errors());
			$code = key($this->Accounts_model->get_errors());
			
			if($code == 'validation_error'){
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

	public function update($id){

		//can only be done by the admin or resource owner
		if(!$this->user->authorized([
			'roles'	=> 'admin'
		], [
			'users'	=> $id
		])){

			$content = 'You\'re not authorized to update this user!';
			$code = 'error';
			$this->auth_response->setStatusCode(401);

		}else{

			$data = $this->input->json(false);

			if(!$this->user->authorized(['roles' => 'admin'])){
				unset(
					$data['apiFreeLimit'],
					$data['apiUsage'],
					$data['apiRequests'],
					$data['apiLeftOverUsage'],
					$data['apiLeftOverCharge'],
					$data['chargeInterval'],
					$data['chargeDate']
				);
			}

			$query = $this->Accounts_model->update($id, $data);
			
			if($query){
			
				$content = $id;
				$code = 'success';
				
			}else{
			
				$content = current($this->Accounts_model->get_errors());
				$code = key($this->Accounts_model->get_errors());

				if($code == 'validation_error'){
					$this->auth_response->setStatusCode(400);
				}elseif($code == 'system_error'){
					$this->auth_response->setStatusCode(500);
				}
				
			}

		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);
		
		Template::compose(false, $output, 'json');

	}

	public function delete($id){

		if(!$this->user->authorized([
			'roles'	=> 'admin'
		], [
			'users'	=> $id
		])){

			$content = 'You\'re not authorized to delete this user!';
			$code = 'error';
			$this->auth_response->setStatusCode(401);

		}else{

			$data = $this->input->json(false);

			$query = $this->Accounts_model->delete($id);
			
			if($query){
			
				$content = $id;
				$code = 'success';
				
			}else{
			
				$this->auth_response->setStatusCode(404);
				$content = current($this->Accounts_model->get_errors());
				$code = key($this->Accounts_model->get_errors());
				
			}

		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);
		
		Template::compose(false, $output, 'json');

	}

	//so this will be called when someone clicks on "I forgot my password!"
	//it needs to be passed in an identitifier
	//api/accounts/forgot_password/roger.qiu@polycademy.com
	//given this identifier we'll attempt something
	public function forgotten_password($identifier){

		$query = $this->Accounts_model->send_forgotten_password_confirmation($identifier);

		if($query){

			$content = 'We sent an email allowing you to reset your password.';
			$code = 'success';

		}else{

			$content = current($this->Accounts_model->get_errors());
			$code = key($this->Accounts_model->get_errors());

			if($code == 'validation_error'){
				//identifier did not match anybody
				$this->auth_response->setStatusCode(400);
			}elseif($code == 'system_error'){
				//emailing process did not work
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

	public function confirm_forgotten_password(){

		$data = $this->input->json(false);

		$query = $this->Accounts_model->confirm_forgotten_password($data);
		
		if($query){
		
			$content = $query;
			$code = 'success';
		
		}else{
		
			
			$content = current($this->Accounts_model->get_errors());
			$code = key($this->Accounts_model->get_errors());

			if($code == 'validation_error'){
				//the new password may not pass the requirements (might be the same old password)
				//the forgotten code may be incorrect
				//the user id may not exist in the system
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
	
}