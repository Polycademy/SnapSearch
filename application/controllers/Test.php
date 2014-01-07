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

	public function payments(){

		$this->load->model('Payments_model');

		$payment_history = [
			'userId'		=> 1,
			'chargeToken'	=> 'ch_lfUYEBK14zotCTykezJkfg',
			'date'			=> (new DateTime('2012-06-20T03:10:49Z'))->format('Y-m-d H:i:s'),
			'item'			=> 'SnapSearch API Charge',
			'usageRate'		=> '100000',
			'amount'		=> '10000000',
			'currency'		=> 'AUD',
			'email'			=> 'roger.qiu@polycademy.com',
			'country'		=> 'New Zealand',
		];

		$payment_history['address'] = '42 Sevenoaks St Lathlain 6454 WA';

		$payment_id = $this->Payments_model->create($payment_history);

		echo $payment_id;

	}

	public function payments_update(){

		$payment_id = '14';

		$this->load->model('Payments_model');

		$updated_payment_history = [
			'userId'		=> 1,
			'chargeToken'	=> 'ch_lfUYEBK14zotCTykezJkfg',
			'date'			=> (new DateTime('2012-06-20T03:10:49Z'))->format('Y-m-d H:i:s'),
			'item'			=> 'Blah API Charge',
			'usageRate'		=> '100',
			'amount'		=> '10000',
			'currency'		=> 'AUD',
			'email'			=> 'roger.qiu@polycademy.com',
			'country'		=> 'Crazy Land',
		];

		$updated_payment_history['address'] = '100 Sevenoaks St Lathlain 6454 WA';

		$payment_id = $this->Payments_model->update($payment_id, $updated_payment_history);

	}

	public function payments_read(){

		$this->load->model('Payments_model');

		var_dump($this->Payments_model->read_all());

	}

	public function payments_delete(){

		$this->load->model('Payments_model');

		$this->Payments_model->delete(14);

	}

	public function usage(){

		//test usage

	}

	public function pin(){

		//use test api and create customers.. etc

	}

	public function billing(){

		//finally test the creation of customers and records from the billing

	}

	public function cron(){

		//simulate a cron...

	}

}