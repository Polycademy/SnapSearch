<?php

class Test extends CI_Controller{

	protected $authenticator;
	protected $session_persistence;
	protected $accounts_manager;

	public function __construct(){

		parent::__construct();

	}

	public function test_pdf(){

		$amount = 10000;

		$dollars = $amount / 100;
		$currency = 'AUD';

		$data = array(
			'invoiceNumber'	=> 'SS1',
			'date'	=> date('Y-m-d H:i:s'),
			'userId'	=> '1',
			'email'	=> 'roger.qiu@polycademy.com',
			'item'	=> 'SnapSearch API Usage',
			'usageRate'	=> '2450',
			'currency'	=> $currency,
			'amount'	=> '$' . $dollars,
			'logo'		=> FCPATH . 'img/polycademy_logo.png',
		);

		$invoice_template = $this->load->view('invoices/invoice', $data, true);
		$invoice_style = $this->load->view('invoices/invoice_style', $data, true);

		$facade = PHPPdf\Core\FacadeBuilder::create()->build();
		$pdf = $facade->render($invoice_template, $invoice_style);

		header('Content-Type: application/pdf');
		echo $pdf;
		
	}

	public function email(){

		$this->load->model('Email_model');

		$email_template = $this->Email_model->prepare_email('email/invoice_email', [
			'month'			=> date('F'),
			'year'			=> date('Y'),
			'username'		=> 'CMCDragonkai',
			'user_id'		=> 1,
		]);

		$month = date('F');
		$year = date('Y');

		$this->Email_model->send_email(
			'enquiry@polycademy.com',
			['roger.qiu@polycademy.com'],
			'SnapSearch Monthly Invoice for ' . date('F') . ' ' . date('Y'),
			$email_template,
			[
				"Monthly Invoice $month $year"	=> 'invoices/test.txt'
			]
		);

	}

	public function billing_email(){

		$this->load->model('Email_model');

		$month = date('F');
		$year = date('Y');

		$email_template = $this->Email_model->prepare_email('email/billing_error_email', [
			'month'			=> $month,
			'year'			=> $year,
			'username'		=> 'CMCDragonkai',
			'charge_error'	=> 'Some Crazy Message!',
			'user_id'		=> '1',
		]);

		$this->Email_model->send_email(
			'enquiry@polycademy.com',
			['roger.qiu@polycademy.com'],
			'SnapSearch Monthly Invoice for ' . date('F') . ' ' . date('Y'),
			$email_template
		);

	}

}