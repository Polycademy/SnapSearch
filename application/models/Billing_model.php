<?php

//we'll only try using one of the cards to be active
//it's too risk to have multiple cards active, and the error handling gets complex
//so make sure only one card is allowed to be the active card
//
//also on create and update
//check if any of the cards are active and has no cardInvalid error
//this can happen if the card gets updated with new information
//or when a new card is created and designated as active
//if this is true, go into Accounts_model and move the apiPreviousLimit to the apiLimit
//
//to resolve billing errors:
//
//1. Delete your error card and a new card and designate as active
//2. Update your error card with new information and designate as active

class Billing_model extends CI_Model{

	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->model('Accounts_model');

		$this->load->library('form_validation', false, 'validator');

	}

	public function create($input_data){

		$data = elements(array(
			'userId',
			'cardNumber',
			'customerToken',
			'active',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'cardNumber',
				'label'	=> 'Credit Card Number',
				'rules'	=> 'required|integer'
			),
			array(
				'field'	=> 'customerToken',
				'label'	=> 'Customer Token',
				'rules'	=> 'required'
			),
			array(
				'field'	=> 'active'
				'label'	=> 'Active',
				'rules'	=> 'boolean_style',
			),
		));

		$validation_errors = [];

		if(!$this->Accounts_model->read($data['userId'])){
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

		//filtering the $data

		$data['cardHint'] = substr($data['cardNumber'], -4);
		//convert active into binary boolean
		$data['active'] = (isset($data['active'])) ? intval(filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)) : 1;
		//all cards upon creation shouldn't be invalid
		$data['cardInvalid'] = 0;

		$query = $this->db->insert('billing', $data);

		if(!$query){

			$this->errors = array(
				'system_error'	=> 'Problem inserting the data into billing table.',
			);

			return false;
		
		}

		return $this->db->insert_id();

	}

	public function read($id){

		$query = $this->db->get_where('billing', array('id' => $id));

		if($query->num_rows() > 0){

			$row = $query->row();

			$data = array(
				'id'				=> $row->id,
				'userId'			=> $row->userId,
				'cardHint'			=> $row->cardHint,
				'customerToken'		=> $row->customerToken,
				'active'			=> $row->active,
				'cardInvalid'		=> $row->cardInvalid,
				'cardInvalidReason'	=> $row->cardInvalidReason,
			);

			return $data;

		}else{
		
			$this->errors = array(
				'error' => 'Could not find specified billing information.'
			);

			return false;
		
		}

	}

	public function read_all($user_id = false, $active = null){

		$this->db->select('*');
		$this->db->from('billing');
		if($user_id){
			$this->db->where('userId', $user_id);
		}
		if($active AND !is_null($active)){
			$this->db->where('active', '1');
		}elseif(!$active AND !is_null($active)){
			$this->db->where('active', '0');
		}

		$query = $this->db->get();

		if($query->num_rows() > 0){

			foreach($query->result() as $row){

				$data[] = array(
					'id'				=> $row->id,
					'userId'			=> $row->userId,
					'cardHint'			=> $row->cardHint,
					'customerToken'		=> $row->customerToken,
					'active'			=> $row->active,
					'cardInvalid'		=> $row->cardInvalid,
					'cardInvalidReason'	=> $row->cardInvalidReason,
				);

			}

			return $data;

		}else{

			$this->errors = array(
				'error' => 'Could not find specified billing information.'
			);
			return false;

		}

	}

	public function update($id, $input_data){

		$data = elements(array(
			'userId',
			'cardNumber',
			'active',
			'cardInvalid',
			'cardInvalidReason',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'cardNumber',
				'label'	=> 'Credit Card Number',
				'rules'	=> 'integer'
			),
			array(
				'field'	=> 'active'
				'label'	=> 'Active',
				'rules'	=> 'boolean_style',
			),
			array(
				'field'	=> 'cardInvalid',
				'label'	=> 'Card Invalid',
				'rules'	=> 'boolean_style',
			),
			array(
				'field'	=> 'cardInvalidReason',
				'label'	=> 'Card Invalid Reason',
				'rules'	=> ''
			)
		));

		$validation_errors = [];

		if(isset($data['userId']) AND !$this->Accounts_model->read($data['userId'])){
			$validation_errors['userId'] = 'Billing information can only be updated with an existing user account.';
		}

		if(isset($data['cardInvalidReason']) AND !isset($data['cardInvalid'])){
			$validation_errors['cardInvalidReason'] = 'Cannot submit a card invalid reason without the card also being invalid';
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

		//filtering the $data

		if(isset($data['cardNumber'])){
			$data['cardHint'] = substr($data['cardNumber'], -4);
		}
		if(isset($data['active'])){
			$data['active'] = intval(filter_var($data['active'], FILTER_VALIDATE_BOOLEAN));
		}
		if(isset($data['cardInvalid'])){
			$data['cardInvalid'] = intval(filter_var($data['cardInvalid'], FILTER_VALIDATE_BOOLEAN));
		}
		//if cardInvalid is false, then we should wipe the cardInvalidReason
		if(!$data['cardInvalid']){
			$data['cardInvalidReason'] = '';
		}

		//we no longer require the cardNumber
		unset($data['cardNumber']);

		$this->db->update('billing', $data, array('id' => $id));

		if($this->db->affected_rows() > 0){

			return true;
		
		}else{
			
			$this->errors = array(
				'error'	=> 'Billing record in the database did not need to be updated.',
			);
			return false;
		
		}

	}

	/**
	 * Deletes the billing information.
	 * @param  integer $id
	 * @return boolean
	 */
	public function delete($id){

		$this->db->delete('billing', array('id' => $id));

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