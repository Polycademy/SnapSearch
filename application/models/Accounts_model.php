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

	public function create($input_data){
		
		$data = elements(array(
			'username',
			'email',
			'password',
			'apiLimit',
			'apiFreeLimit',
			'chargeInterval',
			'code'
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
				'rules'	=> 'required|max_length[100]'
			),
			array(
				'field'	=> 'apiLimit',
				'label'	=> 'API Limit',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'apiFreeLimit',
				'label'	=> 'API Free limit',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'chargeInterval',
				'label'	=> 'Charge Interval',
				'rules'	=> 'required|valid_date_duration',
			),
			array(
				'field'	=> 'code',
				'label'	=> 'Promo Code',
				'rules'	=> 'alpha_numeric'
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

		if(!isset($data['apiLimit'])){
			$validation_errors['apiLimit'] = 'API Limit is necessary.';
		}

		if(isset($data['apiLimit']) AND isset($data['apiFreeLimit'])){
			if($data['apiLimit'] < $data['apiFreeLimit']){
				$validation_errors['apiLimit'] = 'API Limit cannot be lower than the API Free Limit.';
			}
		}

		if($this->validator->run() ==  false){
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		$this->validator->reset_validation();

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

		$data['createdOn'] = date('Y-m-d H:i:s');

		$data['apiUsage'] = 0;

		$data['apiRequests'] = 0;

		$data['apiLeftOverUsage'] = 0;

		$data['apiLeftOverCharge'] = 0;

		$charge_date = new DateTime($data['createdOn']);
		$charge_date->add(new DateInterval($data['chargeInterval']));
		$charge_date = $charge_date->format('Y-m-d H:i:s');
		$data['chargeDate'] = $charge_date;

		//if the code is a verified code, then set a new apiLimit and apiFreeLimit
		if(isset($data['code'])){
			$verified_code = $this->verify_codes($data['code']);
			if($verified_code){
				$data['apiLimit'] = $verified_code;
				$data['apiFreeLimit'] = $verified_code;
			}
		}

		unset($data['code']);

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

	public function read($id){

		try{

			$user = $this->accounts_manager->get_user($id);
			$user = $user->get_user_data();
			return $user;

		}catch(PolyAuthException $e){

			$this->errors = array(
				'error'	=> $e->get_error_string()
			);

			return false;

		}

	}

	public function read_all($offset = false, $limit = false){

		$offset = ($offset) ? (int) $offset : 0;
		$limit = ($limit) ? (int) $limit : false;

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

	public function update($id, $input_data){

		$data = elements(array(
			'username',
			'email',
			'password',
			'apiLimit',
			'apiFreeLimit',
			'apiUsage',
			'apiRequests',
			'apiLeftOverUsage',
			'apiLeftOverCharge',
			'chargeInterval',
			'chargeDate',
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
				'field'	=> 'apiLimit', //theoretically apiLimit should not be below apiFreeLimit (here or in the database), but it could happen and we would need to compensate for that in the biller code
				'label'	=> 'API Limit',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'apiFreeLimit',
				'label'	=> 'API Free limit',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'apiUsage', //apiUsage can only be updated directly inside the model
				'label'	=> 'API Usage',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'apiRequests',
				'label'	=> 'API Requests',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'apiLeftOverUsage', //apiLeftOverUsage can only be updated directly in the model, which is from the CRON billing, not from the controller
				'label'	=> 'API Left Over Usage',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'apiLeftOverCharge', //apiLeftOverCharge can only be updated directly in the model, which is from the CRON billing, not from the controller. This relies on assuming only one currency is used! This is a pre-tax charge.
				'label'	=> 'API Left Over Charge',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'chargeInterval',
				'label'	=> 'Charge Interval',
				'rules'	=> 'valid_date_duration',
			),
			array(
				'field'	=> 'chargeDate',
				'label'	=> 'Charge Date',
				'rules'	=> 'valid_date',
			),
		));

		$validation_errors = [];

		if(isset($data['apiLimit']) AND isset($data['apiFreeLimit'])){
			if($data['apiLimit'] < $data['apiFreeLimit']){
				$validation_errors['apiLimit'] = 'API Limit cannot be lower than the API Free Limit.';
			}
		}

		//api limit can only be changed if the user has at least an active billing information, but only if it's greater than the apiFreeLimit which can come from the database, or the currently updated data
		if(isset($data['apiLimit'])){
			if($query = $this->read($id)){
				$api_free_limit = (isset($data['apiFreeLimit'])) ? $data['apiFreeLimit'] : $query['apiFreeLimit'];
				if($data['apiLimit'] > $api_free_limit){
					$billing_query = $this->db->get_where('billing', array('userId' => $id, 'active' => 1, 'cardInvalid' => 0));
					if($billing_query->num_rows() < 1){
						$validation_errors['apiLimit'] = 'Cannot update API Limit unless you have valid and active billing information.';
					}
				}
			}
		}

		if($this->validator->run() ==  false){
			$validation_errors = $this->validator->error_array();
		}

		$this->validator->reset_validation();

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

		try{

			$user = $this->accounts_manager->get_user($id);
			$query = $this->accounts_manager->update_user($user, $data);

			if($query){

				return true;

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

	public function count(){

		try{

			$query = $this->accounts_manager->count_users();

			return $query;

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
				'error'	=> $e->get_error_string()
			);

			return false;

		}

	}

	public function confirm_forgotten_password($input_data){

		$data = elements(array(
			'userId',
			'forgottenCode',
			'newPassword',
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

		if($this->validator->run() ==  false){
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		$this->validator->reset_validation();

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
					'error'	=> 'Forgotten code has expired or never existed. You need to resend a forgotten email.',
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

	protected function verify_codes($code){

		$data = file_get_contents(__DIR__ . '/../../codes.json');
		$data = json_decode($data, true);
		$codes = (isset($data['codes'])) ? $data['codes'] : array();

		if(in_array($code, array_keys($codes))){
			return $codes[$code];
		}else{
			return false;
		}

	}

}