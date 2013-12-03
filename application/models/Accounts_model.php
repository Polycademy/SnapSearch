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

	}

	public function read($id){

		try{

			$user = $this->accounts_manager->get_user($id);
			$user = $user->get_user_data();
			return $user;

		}catch(PolyAuthException $e){

			$this->errors = array(
				'error'	=> $e->get_errors()
			);

			return false;

		}

	}

	public function read_all($offset, $limit){

		$limit = intval($limit);
		$offset = intval($offset);

		try{

			$users = $this->accounts_manager->get_users(null, $offset, $limit);

			$output = [];
			foreach($users as $user){
				$output[] = $user->get_user_data();
			}

			return $output;

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
			'passwordConfirm',
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
				'field'	=> 'passwordConfirm',
				'label'	=> 'Password Confirm',
				'rules'	=> 'required|matches[password]'
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

		if(!isset($data['passwordConfirm'])){
			$validation_errors['passwordConfirm'] = 'Password Confirm is necessary.';
		}

		if(isset($data['tac'])){
			$data['tac'] = filter_var($data['tac'], FILTER_VALIDATE_BOOLEAN);
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

		unset($data['tac']);
		unset($data['passwordConfirm']);

		try{

			$user = $this->accounts_manager->register($data);

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

		$validation_errors = [];
		if($this->validator->run() ==  false){
			$validation_errors = $this->validator->error_array();
		}

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

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

		try{

			$user = $this->accounts_manager->get_user(false, $identifier);

			$this->accounts_manager->forgotten_password($user);

			return true;

		}catch(PolyAuthException $e){

			$this->errors = array(
				'error'	=> $e->get_errors()
			);

			return false;

		}

	}

	public function confirm_forgotten_password($input_data){

		$data = elements(array(
			'userId',
			'forgottenCode',
			'newPassword',
			'newPasswordConfirm',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'forgottenCode',
				'label'	=> 'Forgotten Code',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'newPassword',
				'label'	=> 'New Password',
				'rules'	=> 'required'
			),
			array(
				'field'	=> 'newPasswordConfirm',
				'label'	=> 'New Password Confirm',
				'rules'	=> 'required|matches[newPassword]'
			),
		));

		$validation_errors = [];

		if(!isset($data['userId'])){
			$validation_errors['userId'] = 'User ID is necessary.';
		}

		if(!isset($data['forgottenCode'])){
			$validation_errors['forgottenCode'] = 'Forgotten code is necessary.';
		}

		if(!isset($data['newPassword'])){
			$validation_errors['newPassword'] = 'New Password is necessary.';
		}

		if(!isset($data['newPasswordConfirm'])){
			$validation_errors['newPasswordConfirm'] = 'New Password Confirm is necessary.';
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

		//lets get the user
		try{

			$user = $this->accounts_manager->get_user($data['userId']);

			$forgotten_check = $this->accounts_manager->forgotten_check($user, $data['forgottenCode']);

			if($forgotten_check){

				if($this->accounts_manager->forgotten_complete($user, $data['newPassword'])){

					return $data['userId'];

				}else{

					$this->errors = array(
						'system_error'	=> 'Unable to reset password, try again.'
					);
					return false;

				}

			}else{

				$this->errors = array(
					'error'	=> 'Forgotten code has expired or never existed',
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

	public function get_errors(){

		return $this->errors;

	}

}