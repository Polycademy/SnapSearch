<?php

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
				'field'	=> 'active',
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

		$this->resolve_billing_errors($data['userId']);

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
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'active',
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
				'rules'	=> '',
			)
		));

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
		if(isset($data['cardInvalid'])){
			if(!$data['cardInvalid']){
				$data['cardInvalidReason'] = '';
			}
		}

		$validation_errors = [];

		if(isset($data['userId']) AND !$this->Accounts_model->read($data['userId'])){
			$validation_errors['userId'] = 'Billing information can only be updated with an existing user account.';
		}

		if(isset($data['active']) AND isset($data['cardInvalid'])){
			if($data['active'] AND $data['cardInvalid']){
				$validation_errors['active'] = 'Cannot submit a card that is simultaneously active and invalid.';
			}
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

	/*
		Billing errors can only be resolved by deleting the bad card, and creating a new card
	 */
	protected function resolve_billing_errors($user_id){

		$cards = $this->read_all($user_id);
		
		//if any of the cards are both active and not invalid, we can resolve any potential billing errors
		$can_be_resolved = false;
		foreach($cards as $card){
			if($card['active'] AND !$card['cardInvalid']){
				$can_be_resolved = true;
				break;
			}
		}

		//switch the apiPreviousLimit into the apiLimit, but only if the apiPreviousLimit was greater than 0
		//then default the apiPreviousLimit to 0
		if($can_be_resolved){
			$user = $this->Accounts_model->read($user_id);
			if($user['apiPreviousLimit'] > 0){
				$this->Accounts_model->update($user_id, [
					'apiLimit'	=> $user['apiPreviousLimit'],
					'apiPreviousLimit'	=> 0
				]);
			}
		}

	}

}