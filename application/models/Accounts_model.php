<?php

use PolyAuth\Exceptions\PolyAuthException;

class Accounts_model extends CI_Model{

	protected $accounts_manager;
	protected $emailer;
	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->library('form_validation', false, 'validator');

		$ioc = $this->config->item('ioc');
		$this->accounts_manager = $ioc['PolyAuth\AccountsManager'];
		$this->emailer = $ioc['PolyAuth\Emailer'];

	}

	public function read($id){

		try{

			$user = $this->accounts_manager->get_user($id);
			return $user;

		}catch(PolyAuthException $e){

			$this->errors = array(
				'error'	=> $e->get_errors()
			);

			return false;

		}

	}

	public function read_all($limit, $offset){

		$limit = intval($limit);
		$offset = intval($offset);

		try{

			$user = $this->accounts_manager->get_users(null, $limit, $offset);
			return $user;

		}catch(PolyAuthException $e){

			$this->errors = array(
				'error'	=> $e->get_errors()
			);

			return false;

		}

	}

	public function create($input_data){

		$data = elements(array(
			'username',
			'email',
			'password',
			'tac',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'username',
				'label'	=> 'Username',
				'rules'	=> 'required|htmlspecialchars|min_length[2]|max_length[100]',
			),
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
				'field'	=> 'tac',
				'label'	=> 'Terms and Conditions',
				'rules'	=> 'required|boolean_style'
			),
		));

		$validation_errors = [];

		if(!isset($data['username'])){
			$validation_errors['username'] = 'Username is necessary.';
		}

		if(!isset($data['email'])){
			$validation_errors['email'] = 'Email is necessary.';
		}

		if(!isset($data['password'])){
			$validation_errors['password'] = 'Password is necessary.';
		}

		if(isset($data['tac'])){
			$data['tac'] = filter_var($data['autologin'], FILTER_VALIDATE_BOOLEAN);
			if(!$data['tac']){
				$validation_errors['tac'] = 'Accepting the Terms and Conditions is necessary.';
			}
		}else{
			$validation_errors['tac'] = 'Terms and conditions is necessary.';
		}

		if($this->validator->run() ==  false){
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

		unset $data['tac'];

		try{

			$user = $this->accounts_manager->register_user($data);

			return $user['id'];

		}catch(PolyAuthException $e){

			$this->errors = array(
				'validation_error'	=> $e->get_errors()
			);

			return false;

		}

	}

	//how would this work as complete or partial update
	//and the data fields that are not sent will be be left as it is?
	public function update($id, $input_data){

		$data = elements(array(
			'username',
			'email',
			'password',
			'passwordConfirm',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'username',
				'label'	=> 'Username',
				'rules'	=> 'htmlspecialchars|min_length[2]|max_length[100]',
			),
			array(
				'field'	=> 'email',
				'label'	=> 'Description',
				'rules'	=> 'valid_email|max_length[100]',
			),
			array(
				'field'	=> 'password',
				'label'	=> 'Password',
				'rules'	=> ''
			),
			array(
				'field'	=> 'passwordConfirm',
				'label'	=> 'Password Confirm',
				'rules'	=> 'matches[password]'
			),
		));

		$validator_errors = [];

		//if password wasn't passed in, don't update the password
		if(empty($data['password'])){
			unset($data['password']);
		}

		unset($data['passwordConfirm']);

		try{

			$user = $this->accounts_manager->get_user($id);
			$query = $this->accounts_manager->update_user($user, $data);

			if($query){

				return $id;

			}else{

				$this->errors = array(
					'error'	=> 'Nothing to update'
				);
				return false;

			}

		}catch(PolyAuthException $e){

			$this->errors = array(
				'validation_error'	=> $e->get_errors()
			);

			return false;

		}

	}

	public function delete($id){

		try{

			$user = $this->accounts_manager->get_user($id);
			$query = $this->accounts_manager->deregister($user);
			
			return true;

		}catch(PolyAuthException $e){

			$this->errors = array(
				'error'	=> $e->get_errors()
			);

			return false;

		}

	}

	public function send_forgotten_password_confirmation($identifier){

		

	}

	public function confirm_forgotten_password($input_data){

	}

	public function get_errors(){

		return $errors;

	}

}