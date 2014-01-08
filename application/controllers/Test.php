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
		$this->load->model('Usage_model');

		$this->Usage_model->create([
			'userId'	=> 1,
			'date'		=> date('Y-m-d H:i:s'),
			'usage'		=> 1000,
			'requests'	=> 10000,
		]);

		$this->Usage_model->create([
			'userId'	=> 2,
			'date'		=> date('Y-m-d H:i:s'),
			'usage'		=> 1000,
			'requests'	=> 10000,
		]);

		$data = $this->Usage_model->read_all();

		var_dump($data);

		$first = $this->Usage_model->read_all(false, false, 2);

		var_dump($first);

	}

	public function usage_update(){

		$this->load->model('Usage_model');

		$this->Usage_model->update(1, [
			'usage'	=> 100000,
			'requests'	=> 100000
		]);

		$this->Usage_model->delete(2);

	}

	public function pin(){

		$this->load->model('Pin_model');

		$this->Pin_model->test();

		$pin_query = $this->Pin_model->create_customer([
			'email' 		=> 'test@test.com',
			'cardNumber'	=> ' 4100000000000001',
			'cardCvc'		=> '123',
			'cardExpiryMonth'	=> '05',
			'cardExpiryYear'	=> '2015',
			'cardName'		=> 'Roland C Robot',
			'cardAddress'	=> '42 Sevenoaks St',
			'cardCity'		=> 'Lathlain',
			'cardCountry'	=> 'Australia',
		]);

		var_dump($pin_query);

		var_dump($this->Pin_model->get_errors());

	}

	public function pin_update(){

		$this->load->model('Pin_model');

		$this->Pin_model->test();

		$pin_query = $this->Pin_model->update_customer('cus_JrYjPr0VGBxW0WgPrvvyCg', [
			'email'	=> 'new@new.com',
			'cardNumber'	=> '4100000000000001',
			'cardCvc'		=> '123',
			'cardExpiryMonth'	=> '05',
			'cardExpiryYear'	=> '2015',
			'cardName'		=> 'Roland C Robot',
			'cardAddress'	=> '42 Sevenoaks St',
			'cardState'		=> 'NSW',
			'cardPostCode'	=> '2220',
			'cardCity'		=> 'Lathlain',
			'cardCountry'	=> 'Australia',
		]);

		var_dump($pin_query);

		var_dump($this->Pin_model->get_errors());

	}

	public function pin_charge(){

		$this->load->model('Pin_model');

		$this->Pin_model->test();

		$pin_query = $this->Pin_model->charge_customer([
			'email'			=> 'test@test.com',
			'description'	=> 'SnapSearch API Charge',
			'amount'		=> '10000',
			'ipAddress'		=> '::1',
			'currency'		=> 'AUD',
			'customerToken'	=> 'cus_Do_KEk4ajiy4X3Luo-R-dA',
		]);

		var_dump($pin_query);

		var_dump($this->Pin_model->get_errors());

	}

	public function billing(){

		$this->load->model('Billing_model');

		$query = $this->Billing_model->create([
			'userId'		=> '1',
			'cardNumber'	=> '4100000000000001',
			'customerToken'	=> 'cus_Do_KEk4ajiy4X3Luo-R-dA',
			'active'		=> '1',
		]);

		var_dump($this->Billing_model->get_errors());

	}

	public function billing_update(){

		$this->load->model('Billing_model');

		$query = $this->Billing_model->update(4, [
			'userId'		=> '1',
			'cardNumber'	=> '4100000000000001',
			'customerToken'	=> 'cus_Do_KEk4ajiy4X3Luo-R-dA',
			'active'		=> '1',
		]);

		var_dump($query);

		var_dump($this->Billing_model->get_errors());

	}

	public function billing_delete(){

		$this->load->model('Billing_model');

		$query = $this->Billing_model->delete(14);

		var_dump($query);

		var_dump($this->Billing_model->get_errors());

	}

	public function cron(){

		//simulate a cron...

	}

	//test out navigation false (redirect false)
	//then add SSL
	//then deploy
	//and modify dreamitapp
	//then post up libraries! (change SnapSearch to use HTTP basic)
	//then work on the design, and pricing?

}