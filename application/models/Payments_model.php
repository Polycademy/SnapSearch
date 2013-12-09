<?php

use PHPPdf\Core\FacadeBuilder as PDFBuilder;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

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
	 * @param  array           $input_data Array of data
	 * @return integer|boolean
	 */
	public function create($input_data){

		$data = elements(array(
			'userId',
			'chargeToken',
			'date',
			'amount',
			'currency',
			'invoiceFile',
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
				'field'	=> 'amount'
				'label'	=> 'Amount in Cents',
				'rules'	=> 'required|numeric',
			),
			array(
				'field'	=> 'currency',
				'label'	=> 'Currency',
				'rules'	=> 'required|alpha|max_length[3]',
			),
			array(
				'field'	=> 'invoiceFile',
				'label'	=> 'Invoice File',
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

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

		$query = $this->db->insert('payment_history', $data);

		if(!$query){

			$this->errors = array(
				'system_error'	=> 'Problem inserting data to payments table.',
			);

			return false;

		}

		return $this->db->insert_id();

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
					'amount'			=> $row->amount,
					'currency'			=> $row->currency,
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
	 * Update a payment record. Theoretically this should never be called. Or else the invoice file will be a mismatch.
	 * @param  integer $id
	 * @param  array   $input_data
	 * @return integer|boolean
	 */
	public function update($id, $input_data){

		$data = elements(array(
			'userId',
			'chargeToken',
			'date',
			'amount',
			'currency',
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
				'field'	=> 'amount'
				'label'	=> 'Amount in Cents',
				'rules'	=> 'numeric',
			),
			array(
				'field'	=> 'currency',
				'label'	=> 'Currency',
				'rules'	=> 'alpha|max_length[3]',
			),
			array(
				'field'	=> 'invoiceFile',
				'label'	=> 'Invoice File',
				'rules'	=> ''
			)
		));

		$validation_errors = [];

		if($this->validator->run() ==  false){
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

		$this->db->update('payment_history', $data, array('id' => $id));

		if($this->db->affected_rows() > 0){

			return true;
		
		}else{
			
			$this->errors = array(
				'error'	=> 'Payment record doesn\'t need to update.',
			);
			return false;
		
		}

	}

	public function delete($id){

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
	 * This is used prior to creating or updating the records.
	 * @param  array          $input_data
	 * @param  string         $invoice_name
	 * @return string|boolean
	 */
	public function create_invoice($input_data, $invoice_name = false){

		$data = elements(array(
			'invoiceNumber', //provide SS1
			'date',
			'userId',
			'email',
			'address',
			'postCode',
			'country'
			'item',
			'usageRate',
			'currency',
			'amount',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'invoiceNumber',
				'label'	=> 'Invoice Number',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'date',
				'label'	=> 'Date',
				'rules'	=> 'required|valid_date',
			),
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'email'
				'label'	=> 'User Email',
				'rules'	=> 'valid_email',
			),
			array(
				'field'	=> 'address',
				'label'	=> 'Address',
				'rules'	=> 'required',
			),
			array(
				'field'	=> 'postCode',
				'label'	=> 'Post Code',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'country',
				'label'	=> 'Country',
				'rules'	=> 'required',
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
				'field'	=> 'currency',
				'label'	=> 'Currency',
				'rules'	=> 'required|alpha|max_length[3]',
			),
			array(
				'field'	=> 'amount',
				'label'	=> 'Amount in Cents',
				'rules'	=> 'required|numeric'
			)
		));

		$validation_errors = [];

		if($this->validator->run() ==  false){
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

		//prefix the invoice number by SS
		$data['invoiceNumber'] = 'SS' . $data['invoiceNumber'];
		//convert the cents into dollars
		$dollars = $data['amount'] / 100;
		$data['amount'] = '$' . $dollars;
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
	public function delete_invoice($invoice_name){

		if($filename AND $this->filesystem->has($invoice_name)){
			$this->filesystem->delete($invoice_name);
		}

		return true;

	}

	public function get_errors(){

		return $this->errors;
		
	}

}