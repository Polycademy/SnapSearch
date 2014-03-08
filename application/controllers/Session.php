<?php

use PolyAuth\Exceptions\ValidationExceptions\LoginValidationException;

/**
 * Session Controller should be used for Cookie based Sessions
 */
class Session extends CI_Controller{

	protected $authenticator;
	protected $auth_response;

	public function __construct(){

		parent::__construct();

		$this->load->library('form_validation', false, 'validator');

		$ioc = $this->config->item('ioc');
		$this->authenticator = $ioc['PolyAuth\Authenticator'];

		$this->authenticator->start();
		
		$this->auth_response = $this->authenticator->get_response();
	
	}
	
	/**
	 * Gets the current session. It will return {content: user_id or 'anonymous'}
	 */
	public function show(){

		$user = $this->authenticator->get_user();

		if($user->authorized()){
			$user = $user['id'];
		}else{
			$user = 'anonymous';
		}

		$this->auth_response->sendHeaders();

		$output = array(
			'content'	=> $user,
			'code'		=> 'success',
		);

		Template::compose(false, $output, 'json');

	}

	/**
	 * Login the current session
	 */
	public function create(){

		$data = $this->input->json(false);

		$data = elements(array(
			'email',
			'password',
			'autologin',
		), $data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'email',
				'label'	=> 'Description',
				'rules'	=> 'required|valid_email|max_length[100]',
			),
			array(
				'field'	=> 'password',
				'label'	=> 'Password',
				'rules'	=> 'required'
			),
			array(
				'field'	=> 'autologin',
				'label'	=> 'Autologin',
				'rules'	=> 'boolean_style'
			)
		));

		$validation_errors = [];

		if(!isset($data['email'])){
			$validation_errors['email'] = 'Email is necessary.';
		}

		if(!isset($data['password'])){
			$validation_errors['password'] = 'Password is necessary.';
		}

		if($this->validator->run() ==  false){
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		if(!empty($validation_errors)){

			$this->auth_response->setStatusCode(400);
			$content = $validation_errors;
			$code = 'validation_error';

		}else{

			//parse the boolean style
			if(isset($data['autologin'])){
				$data['autologin'] = filter_var($data['autologin'], FILTER_VALIDATE_BOOLEAN);
			}else{
				$data['autologin'] = false;
			}

			//attempt to login
			try{

				//this should return a UserAccount object or throw an exception
				//it should not just return false
				$this->authenticator->login([
					'identity'	=> $data['email'],
					'password'	=> $data['password'],
					'autologin'	=> $data['autologin']
				]);

				$user = $this->authenticator->get_user();

				if($user->authorized()){

					$this->auth_response->setStatusCode(201);
					$content = $user['id'];
					$code = 'success';

				}else{

					//this part can only be happening if the login function is used with a strategy that doesn't support the login function such as HTTP Basic. This controller should not be used with HTTP basic
					$this->auth_response->setStatusCode(400);
					$content = 'This Sessions Controller should not be used with HTTP Basic Auth. Please report.';
					$code = 'system_error';

				}

			}catch(LoginValidationException $e){

				$this->auth_response->setStatusCode(401);
				$content = $e->get_errors();
				$code = 'validation_error';

			}

		}

		//send the cookies!
		$this->auth_response->sendHeaders();

		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);

		Template::compose(false, $output, 'json');

	}

	/**
	 * Logout the current session
	 */
	public function delete(){

		$this->authenticator->logout();

		$this->auth_response->setStatusCode(204);
		$this->auth_response->sendHeaders();

		$output = array(
			'content'	=> 'Logged out!',
			'code'		=> 'success',
		);

		Template::compose(false, $output, 'json');

	}

}