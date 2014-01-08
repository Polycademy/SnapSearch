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

		//all cards upon creation shouldn't be invalid
		$data['cardInvalid'] = 0;

		//converting the cardNumber to a cardHint
		if(isset($data['cardNumber'])){
			$data['cardHint'] = substr($data['cardNumber'], -4);
			unset($data['cardNumber']);
		}

		if(isset($data['active'])){
			$data['active'] = intval(filter_var($data['active'], FILTER_VALIDATE_BOOLEAN));
		}else{
			$data['active'] =  1;
		}

		$validation_errors = [];

		if(isset($data['userId']) AND !$this->Accounts_model->read($data['userId'])){
			$validation_errors['userId'] = 'Billing information can only be created for an existing user account.';
		}

		//if the inserted active is intended to be active, check if there are already cards in which they are active
		if(isset($data['userId']) AND $data['active']){
			$active_cards = $this->read_all($data['userId'], true);
			if($active_cards){
				$validation_errors['active'] = 'Only one active card per user account is allowed.';
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

	public function read_all($user_id = false, $active = null, $not_invalid = null){

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
		if($not_invalid AND !is_null($not_invalid)){
			$this->db->where('cardInvalid', '0');
		}elseif(!$not_invalid AND !is_null($not_invalid)){
			$this->db->where('cardInvalid', '1');
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

		//get the user_id of this particular update, it'll be used for active card validation
		//and settling api limit issues
		$query = $this->read($id);
		$user_id = false;
		if($query){
			$user_id = $query['userId'];
		}

		//filtering the $data
		if(isset($data['cardNumber'])){
			$data['cardHint'] = substr($data['cardNumber'], -4);
			unset($data['cardNumber']);
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

		//check if there are any active cards other than this current card
		if(isset($data['active'])){
			if($data['active'] AND $user_id){
				$active_cards = $this->read_all($user_id, true);
				if($active_cards){
					//filter out this particular card if it exists
					$other_cards = array_filter($active_cards, function($value) use ($id){
						return $id != $value['id'];
					});
					if(count($other_cards) > 0){
						$validation_errors['active'] = 'Only one active card per user account is allowed.';
					}
				}
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

		$this->db->update('billing', $data, array('id' => $id));

		if($this->db->affected_rows() > 0){

			$this->settle_api_limit($user_id);

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

		if($query = $this->read($id)){
			$user_id = $query['userId'];
		}

		$this->db->delete('billing', array('id' => $id));

		if($this->db->affected_rows() > 0){

			$this->settle_api_limit($user_id);

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
		Whenever there is a successful change to the Billing records, we need to check if there aren't any active cards left. Then the apiLimit will equal the apiFreeLimit.
	 */
	protected function settle_api_limit($user_id){

		//get all the cards that are active and not invalid
		//when cards are determined to be invalid by the cron job, it already sets the apiLimit to equal the apiFreeLimit
		$cards = $this->read_all($user_id, true, true);

		if(!$cards){

			$user = $this->Accounts_model->read($user_id);

			$query = $this->Accounts_model->update($user_id, [
				'apiLimit'			=> $user['apiFreeLimit'],
			]);

		}
		
	}

}