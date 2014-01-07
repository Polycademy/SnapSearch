<?php

use PHPPdf\Core\FacadeBuilder as PDFBuilder;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

/**
 * Payments Model manages the payment history. 
 * This means CRUDing the payment_history, and also creating and deleting invoices.
 */
class Payments_model extends CI_Model{

	protected $invoices_location;
	protected $filesystem;
	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->model('Accounts_model');

		$this->load->library('form_validation', false, 'validator');

		$this->invoices_location = 'invoices';

		$this->filesystem = new Filesystem(new LocalAdapter($this->invoices_location, true));

	}

	/**
	 * Create a payment record for a particular user. There can be multiple records.
	 * Also creates the invoice file.
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
		));

		$validation_errors = [];

		if(!$this->Accounts_model->read($data['userId'])){
			$validation_errors['userId'] = 'Payments information can only be created for an existing user account.';
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

		//begin transaction
		$this->db->trans_begin();

		//first insert the payment record without the invoice file
		$this->db->insert('payment_history', $data);

		//we need this invoice id to update with the invoice file once created
		$invoice_id = $this->db->insert_id();

		//create the invoice file using the invoice id as the invoiceNumber
		$invoice_data = $data;
		$invoice_data['invoiceNumber'] = $invoice_id;
		$invoice_file = $this->upsert_invoice($invoice_data);

		//update the record with the location of the invoice file
		$this->db->update(
			'payment_history', 
			[
				'invoiceFile'	=> $invoice_file
			], 
			[
				'id'	=> $invoice_id
			]
		);

		//if any queries failed or the invoice file was not created, we roll back the transaction
		if($this->db->trans_status() === false OR $invoice_file === false){

			$this->db->trans_rollback();
			$this->errors = array(
				'system_error'	=> 'Problem inserting data to payments table and/or creating the invoice file.',
			);
			return false;

		}else{

			$this->db->trans_commit();
		
		}

		return $invoice_id;

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
				'invoiceFile'		=> $row->invoiceFile,
				'invoiceFilePath'	=> $this->invoices_location . '/' . $row->invoiceFile,
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
					'invoiceFile'		=> $row->invoiceFile,
					'invoiceFilePath'	=> $this->invoices_location . '/' . $row->invoiceFile,
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
	 * Update a payment record. It can also update the entire invoice file by overwriting it. 
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
			'invoiceFile',
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
			)
		));

		$validation_errors = [];

		if(isset($data['userId']) AND !$this->Accounts_model->read($data['userId'])){
			$validation_errors['userId'] = 'Payments information can only be updated to an existing user account.';
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

		//begin transaction
		$this->db->trans_begin();

		//update the record with the new data, the invoiceFile however is not allowed to be updated
		$this->db->update('payment_history', $data, array('id' => $id));

		//if no rows were affected, nothing was changed
		if($this->db->affected_rows() < 1){
			$this->db->trans_commit();
			$this->errors = array(
				'error'	=> 'Payment record doesn\'t need to update or record wasn\'t found.',
			);
			return false;
		}

		//get the updated record information
		$payment_record = $this->db->get_where('payment_history', array('id' => $id))->row_array();

		//the invoiceNumber stays the same, and it's the same as the id of the record
		$payment_record['invoiceNumber'] = $payment_record['id'];

		//use the updated $payment_record's information to overwrite the old invoice file
		$invoice_file = $this->upsert_invoice($payment_record, $payment_record['invoiceFile']);

		if($this->db->trans_status() === false OR $invoice_file === false){
			$this->db->trans_rollback();
			$this->errors = array(
				'system_error'	=> 'Problem updating the payment history and/or updating the invoice file.',
			);
			return false;
		}else{
			$this->db->trans_commit();
		}

		return true;

	}

	/**
	 * Deletes the payment record and associated invoice file
	 * @param  integer $id
	 * @return boolean  
	 */
	public function delete($id){

		//delete invoice file
		$query = $this->db->get_where('payment_history', array('id' => $id));
		if($query->num_rows() > 0){
			$this->delete_invoice($query->row()->invoiceFile);
		}

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

	/**
	 * Creates an invoice and saves it in the invoices folder and returns the filename.
	 * Or it can update an invoice if you pass the relevant invoice name.
	 * @param  array          $input_data
	 * @param  string         $invoice_name
	 * @return string|boolean
	 */
	protected function upsert_invoice($input_data, $invoice_name = false){

		$data = elements(array(
			'invoiceNumber', //provide SS1
			'date',
			'userId',
			'email',
			'address',
			'country',
			'item',
			'usageRate',
			'currency',
			'amount',
		), $input_data, null, true);

		//prefix the invoice number by SS
		$data['invoiceNumber'] = 'SS' . $data['invoiceNumber'];

		//calculate tax dollars of 10% inclusive
		if(strtolower($data['country']) == 'australia'){
			$data['tax'] = '$' . round(($data['amount'] * 0.1) / 100, 2);
		}else{
			$data['tax'] = '$0';
		}

		//convert the cents into dollars
		$data['amount'] = '$' . $data['amount'] / 100;

		//add the logo image of Polycademy
		$data['logo'] = FCPATH . 'img/polycademy_logo.png';

		$invoice_template = $this->load->view('invoices/invoice', $data, true);
		$invoice_style = $this->load->view('invoices/invoice_style', false, true);

		$pdf_builder = PDFBuilder::create()->build();
		$pdf = $pdf_builder->render($invoice_template, $invoice_style);

		//we create a new invoice, or update an old invoice
		if(!$invoice_name){
			//get a unique filename first
			do{
				$invoice_name = uniqid('invoices', true);
				if(!$this->filesystem->has($invoice_name)) break;
			}while(true);
			//add the pdf extension
			$invoice_name .= '.pdf';
		}

		$this->filesystem->write($invoice_name, $pdf, true);

		return $invoice_name;

	}

	/**
	 * Deletes an invoice file based on the name
	 * @param  string  $invoice_name
	 * @return boolean
	 */
	protected function delete_invoice($invoice_name){

		if($this->filesystem->has($invoice_name)){
			$this->filesystem->delete($invoice_name);
		}

		return true;

	}

	public function get_errors(){

		return $this->errors;
		
	}

}