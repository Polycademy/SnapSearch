<?php

class Billing_model extends CI_Model{

	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->model('Accounts_model');
		$this->load->model('Pin_model');

		$this->load->library('form_validation', false, 'validator');

	}

	public function create($input_data){

		$data = elements(array(
			'userId',
			'email',
			'active',
			'cardNumber',
			'cardCvc',
			'cardExpiryMonth',
			'cardExpiryYear',
			'cardName',
			'cardAddress',
			'cardCity',
			'cardPostCode',
			'cardState',
			'cardCountry',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'email',
				'label'	=> 'Email',
				'rules'	=> 'required|valid_email',
			),
			array(
				'field'	=> 'active'
				'label'	=> 'Active',
				'rules'	=> 'boolean_style',
			),
			array(
				'field'	=> 'cardNumber',
				'label'	=> 'Credit Card Number',
				'rules'	=> 'required|integer'
			),
			array(
				'field'	=> 'cardCvc',
				'label'	=> 'Card CVC',
				'rules'	=> 'required|integer'
			),
			array(
				'field'	=> 'cardExpiryMonth',
				'label'	=> 'Card Expiry Month',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'cardExpiryYear',
				'label'	=> 'Card Expiry Year',
				'rules'	=> 'required|integer'
			),
			array(
				'field'	=> 'cardName',
				'label'	=> 'Card Name',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'cardAddress', //card address is the combined one line address
				'label'	=> 'Card Address',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'cardCity',
				'label'	=> 'Card City',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'cardPostCode',
				'label'	=> 'Card Post Code',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'cardState', //card state is not a required
				'label'	=> 'Card State',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardCountry',
				'label'	=> 'Card Country',
				'rules'	=> 'required'
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

		$data['cardHint'] = substr($data['cardNumber'], -4);
		//convert active into binary boolean
		$data['active'] = (isset($data['active'])) ? intval(filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)) : 0;
		$data['active'] = (isset($data['active'])) ? intval(filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)) : 0;
		//all cards upon creation shouldn't be invalid
		$data['cardInvalid'] = 0;
		$data['customerToken'] = $this->Pin_model->create_customer($data);

		$query = false;
		if($data['customerToken']){

			$db_data = elements(array(
				'userId',
				'cardHint',
				'customerToken',
				'active',
				'cardInvalid',
			), $data, null, true);

			$query = $this->db->insert('billing', $db_data);
		
		}

		if(!$query){

			$this->errors = array(
				'system_error'	=> 'Problem creating customer reference in Pin service and/or inserting the data into billing table.',
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
				'id'			=> $row->id,
				'userId'		=> $row->userId,
				'cardHint'		=> $row->cardHint,
				'customerToken'	=> $row->customerToken, //customerToken is sensitive information, do not allow non-admins to see
				'active'		=> $row->active,
				'cardInvalid'	=> $row->active,
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

		//for card data to be updated, all the necessary card data must exist
		$data = elements(array(
			'userId',
			'email',
			'active',
			'cardInvalid',
			'cardNumber',
			'cardCvc',
			'cardExpiryMonth',
			'cardExpiryYear',
			'cardName',
			'cardAddress',
			'cardCity',
			'cardPostCode',
			'cardState',
			'cardCountry',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'email',
				'label'	=> 'Email',
				'rules'	=> 'valid_email',
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
				'field'	=> 'cardNumber',
				'label'	=> 'Credit Card Number',
				'rules'	=> 'integer'
			),
			array(
				'field'	=> 'cardCvc',
				'label'	=> 'Card CVC',
				'rules'	=> 'integer'
			),
			array(
				'field'	=> 'cardExpiryMonth',
				'label'	=> 'Card Expiry Month',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'cardExpiryYear',
				'label'	=> 'Card Expiry Year',
				'rules'	=> 'integer'
			),
			array(
				'field'	=> 'cardName',
				'label'	=> 'Card Name',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardAddress',
				'label'	=> 'Card Address',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardCity',
				'label'	=> 'Card City',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardPostCode',
				'label'	=> 'Card Post Code',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'cardState',
				'label'	=> 'Card State',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardCountry',
				'label'	=> 'Card Country',
				'rules'	=> ''
			),
		));

		$validation_errors = [];

		if(isset($data['userId']) AND !$this->Accounts_model->read($data['userId'])){
			$validation_errors['userId'] = 'Billing information can only be updated with an existing user account.';
		}

		//one for all, all for one, if any of the card data is set, all the card data must be set
		$updating_card = false;
		$keys = array_keys($data);
		$necessary_keys = array(
			'cardNumber',
			'cardCvc',
			'cardExpiryMonth',
			'cardExpiryYear',
			'cardName',
			'cardAddress',
			'cardCity',
			'cardCountry'
		);
		foreach($necessary_keys as $necessary_key){
			if(array_key_exists($data, $necessary_key){
				$updating_card =  true;
				break;
			}
		}
		if($updating_card){
			if(count(array_intersect_key(array_flip($necessary_keys), $data)) !== count($necessary_keys)){
				$validation_errors['card'] = 'Updating the credit card requires all necessary credit card values to be supplied. This includes Card Number, Card CVC, Card Expiry Month, Card Expiry Year, Card Name, Card Address, Card City and Card Country.';
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

		//get the customer token, the customerToken cannot be updated
		$query = $this->db->get_where('billing', array('id' => $id));
		if($query->num_rows() > 0){
			$customer_token = $query->row()->customerToken;
		}else{
			$this->errors = array(
				'error'	=> 'Billing record to be updated was not found.',
			);
			return false;
		}

		//begin transaction
		$this->db->trans_begin();

		if(isset($data['cardNumber'])){
			$data['cardHint'] = substr($data['cardNumber'], -4);
		}
		$data['active'] = (isset($data['active'])) ? intval(filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)) : 0;
		$data['cardInvalid'] = (isset($data['cardInvalid'])) ? intval(filter_var($data['cardInvalid'], FILTER_VALIDATE_BOOLEAN)) : 0;

		$db_data = elements(array(
			'userId',
			'cardHint',
			'active',
			'cardInvalid',
		), $data, null, true);

		//update the record
		$this->db->update('billing', $db_data, array('id' => $id));

		//update the card if necessary
		$card_updated = false;
		if($updating_card){
			$card_updated = $this->Pin_model->update_customer($customer_token, $data);
		}

		//if the db record was updated or that the card was updated, then everything is fine
		//if the trans_status was affected, it's a system error
		//otherwise, nothing needed to be updated
		if($this->db->affected_rows() > 0 OR $card_updated){

			$this->db->trans_commit();
			return true;
		
		}elseif($this->db->trans_status() === FALSE){

			$this->db->trans_rollback();
			$this->errors = array(
				'system_error'	=> 'Billing record in the database could not be updated.',
			);
			return false;
		
		}else{

			$this->db->trans_rollback();
			$this->errors = array(
				'error'	=> 'Billing record in the database did not need to be updated and/or the credit card could not be or did not need to be updated by the Pin service.',
			);
			return false;
		
		}

	}

	/**
	 * Deletes the billing information. It's not possible to delete the customer object in the Pin service
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