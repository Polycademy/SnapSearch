<?php

/**
 * One to one relationship with Accounts, so we're querying based on user id, not individual ids.
 */
class Billing_model extends CI_Model{

	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->model('Accounts_model');
		$this->load->library('form_validation', false, 'validator');

	}

	public function create($user_id, $input_data){

		$data = elements(array(
			'chargeInterval',
			'chargeDate',
			'customerToken',
			'cardInvalid',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'chargeInterval',
				'label'	=> 'Charge Interval',
				'rules'	=> 'required|valid_date_duration',
			),
			array(
				'field'	=> 'chargeDate'
				'label'	=> 'Charge Date',
				'rules'	=> 'required|valid_date',
			),
			array(
				'field'	=> 'customerToken',
				'label'	=> 'Customer Token',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardInvalid',
				'label'	=> 'Card Invalid',
				'rules'	=> 'boolean_style',
			)
		));

		$validation_errors = [];

		if(strtotime($data['chargeDate']) < time()){
			$validation_errors['chargeDate'] = 'Charge Date cannot be in the past.';
		}

		if(!$this->Accounts_model->read($user_id)){
			$validation_errors['userId'] = 'Billing information can only be created for an existing user account.';
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

		$data['userId'] = $user_id;
		$data['cardInvalid'] = (isset($data['cardInvalid'])) ? intval(filter_var($data['cardInvalid'], FILTER_VALIDATE_BOOLEAN)) : 0;

		$query = $this->db->insert('billing', $data);

		if(!$query){

			$this->errors = array(
				'system_error'	=> 'Problem inserting data to billing table.',
			);

			return false;

		}

		//this data is fundamentally linked one to one to the user, so we use the user id to identify them
		return $user_id;

	}

	public function read($user_id){

		$query = $this->db->get_where('billing', array('userId' => $user_id));

		if($query->num_rows() > 0){

			$row = $query->row();

			$data = array(
				'id'				=> $row->id,
				'userId'			=> $row->userId,
				'chargeInterval'	=> $row->chargeInterval,
				'chargeDate'		=> $row->chargeDate,
				'customerToken'		=> $row->customerToken,
				'cardInvalid'		=> $row->cardInvalid,
			);

			return $data;

		}else{

			$this->errors = array(
				'error' => 'Could not find specified blog post.'
			);
			return false;

		}

	}

	public function update($user_id, $input_data){

		$data = elements(array(
			'chargeInterval',
			'chargeDate',
			'customerToken',
			'cardInvalid',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'chargeInterval',
				'label'	=> 'Charge Interval',
				'rules'	=> 'valid_date_duration',
			),
			array(
				'field'	=> 'chargeDate'
				'label'	=> 'Charge Date',
				'rules'	=> 'valid_date',
			),
			array(
				'field'	=> 'customerToken',
				'label'	=> 'Customer Token',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardInvalid',
				'label'	=> 'Card Invalid',
				'rules'	=> 'boolean_style',
			)
		));

		$validation_errors = [];

		if(strtotime($data['chargeDate']) < time()){
			$validation_errors['chargeDate'] = 'Charge Date cannot be in the past.';
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

		$this->db->where('userId', $user_id);
		$this->db->update('billing', $data);

		if($this->db->affected_rows() > 0){
		
			return true;
		
		}else{
			
			$this->errors = array(
				'error'	=> 'Billing information doesn\'t need to be updated.',
			);
			return false;
		
		}

	}

	public function delete($user_id){

		$this->db->delete('billing', array('userId' => $user_id));

		if($this->db->affected_rows() > 0){

			return true;

		}else{

			$this->errors = array(
				'error'	=> 'No billing information to delete.',
			);
			
			return false;

		}

	}

	public function get_errors(){

		return $this->errors;

	}

}