<?php

/**
 * Payments Model manages the payment history. 
 * This means CRUDing the payment_history, and also creating and deleting invoices.
 */
class Payments_model extends CI_Model{

	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->model('Accounts_model');

		$this->load->library('form_validation', false, 'validator');

	}

	/**
	 * Create a payment record for a particular user. There can be multiple records.
	 * This happens after the invoice is already created.
	 * @param  array           $input_data Array of data
	 * @return integer|boolean
	 */
	public function create($input_data){

		$data = elements(array(
			'userId',
			'chargeToken',
			'date', //from Pin API charge_time
			'item',
			'usageRate',
			'amount',
			'currency',			
			'email',
			'address',
			'country',
			'invoiceNumber',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'chargeToken',
				'label'	=> 'Charge Token',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'date',
				'label'	=> 'Date of Charge',
				'rules'	=> 'required|valid_date',
			),
			array(
				'field'	=> 'item',
				'label'	=> 'Item Description',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'usageRate',
				'label'	=> 'Usage Rate',
				'rules'	=> 'required|integer'
			),
			array(
				'field'	=> 'amount',
				'label'	=> 'Amount in Cents',
				'rules'	=> 'required|numeric|greater_than[99]',
			),
			array(
				'field'	=> 'currency',
				'label'	=> 'Currency',
				'rules'	=> 'required|alpha|max_length[3]',
			),
			array(
				'field'	=> 'email',
				'label'	=> 'User Email',
				'rules'	=> 'required|valid_email',
			),
			array(
				'field'	=> 'address',
				'label'	=> 'Address',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'country',
				'label'	=> 'Country',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'invoiceNumber',
				'label'	=> 'Invoice Number',
				'rules'	=> 'required'
			)
		));

		$validation_errors = [];

		if(!$this->Accounts_model->read($data['userId'])){
			$validation_errors['userId'] = 'Payments information can only be created for an existing user account.';
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

		$query = $this->db->insert('payment_history', $data);

		if(!$query){

			$this->errors = [
				'system_error'	=> 'Problem inserting data to payments table and/or creating the invoice file.'
			];

			return false;

		}

		return $this->db->insert_id();

	}

	public function read($id){

		$query = $this->db->get_where('payment_history', ['id'	=> $id]);

		if($query->num_rows() > 0){

			$row = $query->row();

			$data = array(
				'id'				=> $row->id,
				'userId'			=> $row->userId,
				'chargeToken'		=> $row->chargeToken,
				'date'				=> $row->date,
				'item'				=> $row->item,
				'usageRate'			=> $row->usageRate,
				'amount'			=> $row->amount,
				'currency'			=> $row->currency,
				'email'				=> $row->email,
				'address'			=> $row->address,
				'country'			=> $row->country,
				'invoiceNumber'		=> $row->invoiceNumber,
			);

			return $data;

		}else{
		
			$this->errors = array(
				'error' => 'Could not find payment history record.'
			);

			return false;
		
		}

	}

	/**
	 * Read all of payments history, can be searched by the user id
	 * @param  boolean       $offset  Integer to offset
	 * @param  boolean       $limit   Integer to limit
	 * @param  boolean       $user_id Payments information specific to a user
	 * @return array|boolean
	 */
	public function read_all($offset = false, $limit = false, $user_id = false){

		$offset = ($offset) ? (int) $offset : 0;
		$limit = ($limit) ? (int) $limit : false;

        if($offset < 0){
            $offset = 0;
        }

        if($limit < 0) {
            $limit = 0;
        }

		$this->db->select('*');
		$this->db->from('payment_history');
		$this->db->order_by('date', 'desc');
		if(is_integer($offset) AND is_integer($limit)){
			$this->db->limit($limit, $offset);
		}
		if($user_id){
			$this->db->where('userId', $user_id);
		}

		$query = $this->db->get();

		if($query->num_rows() > 0){

			foreach($query->result() as $row){

				$data[] = array(
					'id'				=> $row->id,
					'userId'			=> $row->userId,
					'chargeToken'		=> $row->chargeToken,
					'date'				=> $row->date,
					'item'				=> $row->item,
					'usageRate'			=> $row->usageRate,
					'amount'			=> $row->amount,
					'currency'			=> $row->currency,
					'email'				=> $row->email,
					'address'			=> $row->address,
					'country'			=> $row->country,
					'invoiceNumber'		=> $row->invoiceNumber,
				);

			}

			return $data;

		}else{

			$this->errors = array(
				'error' => 'No payment history to be found.'
			);
			
			return false;

		}

	}

	/**
	 * Update a payment record.
	 * @param  integer $id
	 * @param  array   $input_data
	 * @return integer|boolean
	 */
	public function update($id, $input_data){

		$data = elements(array(
			'userId',
			'chargeToken',
			'date',
			'item',
			'usageRate',
			'amount',
			'currency',			
			'email',
			'address',
			'country',
			'invoiceNumber',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'chargeToken',
				'label'	=> 'Charge Token',
				'rules'	=> '',
			),
			array(
				'field'	=> 'date',
				'label'	=> 'Date of Charge',
				'rules'	=> 'valid_date',
			),
			array(
				'field'	=> 'item',
				'label'	=> 'Item Description',
				'rules'	=> '',
			),
			array(
				'field'	=> 'usageRate',
				'label'	=> 'Usage Rate',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'amount',
				'label'	=> 'Amount in Cents',
				'rules'	=> 'numeric',
			),
			array(
				'field'	=> 'currency',
				'label'	=> 'Currency',
				'rules'	=> 'alpha|max_length[3]',
			),
			array(
				'field'	=> 'email',
				'label'	=> 'User Email',
				'rules'	=> 'valid_email',
			),
			array(
				'field'	=> 'address',
				'label'	=> 'Address',
				'rules'	=> '',
			),
			array(
				'field'	=> 'country',
				'label'	=> 'Country',
				'rules'	=> '',
			),
			array(
				'field'	=> 'invoiceNumber',
				'label'	=> 'Invoice Number',
				'rules'	=> ''
			)
		));

		$validation_errors = [];

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

		//update the record with the new data, the invoiceNumber however is not allowed to be updated
		$this->db->update('payment_history', $data, array('id' => $id));

		//if no rows were affected, nothing was changed
		if($this->db->affected_rows() < 1){

			$this->errors = array(
				'error'	=> 'Payment record doesn\'t need to update or record wasn\'t found.',
			);

			return false;

		}

		return true;

	}

	/**
	 * Deletes the payment record and associated invoice file
	 * @param  integer $id
	 * @return boolean  
	 */
	public function delete($id){

		//delete record
		$this->db->delete('payment_history', array('id' => $id));

		if($this->db->affected_rows() > 0){

			return true;

		}else{

			$this->errors = array(
				'error'	=> 'No payment record to delete.',
			);
			
			return false;

		}

	}

	public function get_errors(){

		return $this->errors;
		
	}

}