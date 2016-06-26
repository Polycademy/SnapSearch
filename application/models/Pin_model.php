<?php

use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Exception\CurlException;

/**
 * Pin Model contacts the Pin service API to CRUD customers.
 */
class Pin_model extends CI_Model{

	protected $client;
	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->library('form_validation', false, 'validator');

		$api_url = 'https://api.pin.net.au/{version}';
		$api_version = '1';
		$api_key = $_ENV['secrets']['pin_api_key'];

		$this->client = new Client($api_url, [
			'version'	=> $api_version,
			'request.options'	=> [
				'auth'	=> [$api_key, '', 'Basic']
			]
		]);

	}

	public function test(){

		$api_url = 'https://test-api.pin.net.au/{version}';
		$api_version = '1';
		$api_key = $_ENV['secrets']['pin_api_test_key'];

		$this->client = new Client($api_url, [
			'version'	=> $api_version,
			'request.options'	=> [
				'auth'	=> [$api_key, '', 'Basic']
			]
		]);

	}

	public function create_customer($input_data){

		$data = elements(array(
			'email',
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
				'field'	=> 'email',
				'label'	=> 'Email',
				'rules'	=> 'required|valid_email',
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
				'field'	=> 'cardPostCode', //card post code is not required
				'label'	=> 'Card Post Code',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardState', //card state is not a required
				'label'	=> 'Card State',
				'rules'	=> '',
			),
			array(
				'field'	=> 'cardCountry',
				'label'	=> 'Card Country',
				'rules'	=> 'required',
			),
		));

		$validation_errors = [];

		if(!isset($data['email']) OR !isset($data['cardNumber']) OR !isset($data['cardCvc']) OR !isset($data['cardExpiryMonth']) OR !isset($data['cardExpiryYear']) OR !isset($data['cardName']) OR !isset($data['cardAddress']) OR !isset($data['cardCity']) OR !isset($data['cardCountry'])){
			$validation_errors['customer'] = 'Necessary fields are missing to create a customer.';
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

		try{

			$pin_data = [
				'email'	=> $data['email'],
				'card'	=> [
					'number'			=> $data['cardNumber'],
					'expiry_month'		=> $data['cardExpiryMonth'],
					'expiry_year'		=> $data['cardExpiryYear'],
					'cvc'				=> $data['cardCvc'],
					'name'				=> $data['cardName'],
					'address_line1'		=> $data['cardAddress'],
					'address_city'		=> $data['cardCity'],
					'address_country'	=> $data['cardCountry'],
				]
			];

			if(isset($data['cardPostCode'])){
				$pin_data['card']['address_postcode'] = $data['cardPostCode'];
			}

			if(isset($data['cardState'])){
				$pin_data['card']['address_state'] = $data['cardState'];
			}

			$request = $this->client->post(
				'customers',
				array(
					'Content-Type'	=> 'application/json'
				),
				json_encode($pin_data)
			);

			$response = $request->send();
			$response_array = $response->json();

		}catch(ClientErrorResponseException $e){

			$response_status = $e->getResponse()->getStatusCode();
			$response_array = $e->getResponse()->json();

			if($response_status == 422){

				foreach($response_array['messages'] as $error){

					$key = $error['param'];
					$message = $error['message'];

					switch($key){
						case 'card.number':
							$key = 'cardNumber';
							break;
						case 'card.expiry_month':
							$key = 'cardExpiryMonth';
							break;
						case 'card.expiry_year':
							$key = 'cardExpiryYear';
							break;
						case 'card.cvc':
							$key = 'cardCvc';
							break;
						case 'card.name':
							$key = 'cardName';
							break;
						case 'card.address_line1':
							$key = 'cardAddress';
							break;
						case 'card.address_city':
							$key = 'cardCity';
							break;
						case 'card.address_postcode':
							$key = 'cardPostCode';
							break;
						case 'card.address_state':
							$key = 'cardState';
							break;
						case 'card.address_country':
							$key = 'cardCountry';
							break;
					}

					$this->errors['validation_error'][$key] = $message;

				}

			}elseif($response_status == 401){

				$this->errors['system_error'] = 'Pin server request was not authenticated.'; 

			}else{

				$this->errors['system_error'] = 'Pin server is not working properly, it responded with a ' . $response_status; 

			}

			return false;

		}catch(ServerErrorResponseException $e){

			$response_status = $e->getResponse()->getStatusCode();
			$this->errors = array(
				'system_error'	=> 'Pin server is not working properly, it responded with a ' . $response_status,
			);
			return false;

		}catch(CurlException $e){

			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);
			return false;

		}

		return $response_array['response']['token'];

	}

	public function update_customer($customer_token, $input_data){

		$data = elements(array(
			'email',
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

		$rules = [];

		$updating_email = false;
		if(isset($data['email']) AND count($data) == 1){

			$updating_email = true;

			//only email, so validate the email
			$rules[] = array(
				'field'	=> 'email',
				'label'	=> 'Email',
				'rules'	=> 'required|valid_email',
			);

		}

		$card_keys = array(
			'cardNumber',
			'cardCvc',
			'cardExpiryMonth',
			'cardExpiryYear',
			'cardName',
			'cardAddress',
			'cardCity',
			'cardCountry'
		);

		$updating_card = false;
		foreach($card_keys as $card_key){
			if(array_key_exists($card_key, $data)){
				$updating_card =  true;
				break;
			}
		}

		if($updating_card){

			//its attempting to update the card, so we need to validate the card details
			$rules = array_merge($rules, [
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
					'rules'	=> '',
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
			]);

		}

		$this->validator->set_rules($rules);

		$validation_errors = [];

		if(!$updating_email AND !$updating_card){
			$validation_errors['customer'] = 'Necessary fields are missing to update a customer.';
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

		try{

			$pin_data = [];
			if($updating_email){
				$pin_data['email']	= $data['email'];
			}

			if($updating_card){
				$pin_data['card'] = [
					'number'			=> $data['cardNumber'],
					'expiry_month'		=> $data['cardExpiryMonth'],
					'expiry_year'		=> $data['cardExpiryYear'],
					'cvc'				=> $data['cardCvc'],
					'name'				=> $data['cardName'],
					'address_line1'		=> $data['cardAddress'],
					'address_city'		=> $data['cardCity'],
					'address_country'	=> $data['cardCountry'],
				];
			}

			if(isset($data['cardPostCode'])){
				$pin_data['card']['address_postcode'] = $data['cardPostCode'];
			}

			if(isset($data['cardState'])){
				$pin_data['card']['address_state'] = $data['cardState'];
			}

			$request = $this->client->put(
				"customers/$customer_token",
				array(
					'Content-Type'	=> 'application/json'
				),
				json_encode($pin_data)
			);

			$response = $request->send();
			$response_array = $response->json();

		}catch(ClientErrorResponseException $e){

			$response_status = $e->getResponse()->getStatusCode();
			$response_array = $e->getResponse()->json();

			if($response_status == 404){

				$this->errors['validation_error']['customerToken'] = 'Customer was not found on the Pin service. Try deleting and creating a new customer reference on the Pin service.'; 

			}elseif($response_status == 422){

				foreach($response_array['messages'] as $error){

					$key = $error['param'];
					$message = $error['message'];

					switch($key){
						case 'card.number':
							$key = 'cardNumber';
							break;
						case 'card.expiry_month':
							$key = 'cardExpiryMonth';
							break;
						case 'card.expiry_year':
							$key = 'cardExpiryYear';
							break;
						case 'card.cvc':
							$key = 'cardCvc';
							break;
						case 'card.name':
							$key = 'cardName';
							break;
						case 'card.address_line1':
							$key = 'cardAddress';
							break;
						case 'card.address_city':
							$key = 'cardCity';
							break;
						case 'card.address_postcode':
							$key = 'cardPostCode';
							break;
						case 'card.address_state':
							$key = 'cardState';
							break;
						case 'card.address_country':
							$key = 'cardCountry';
							break;
					}

					$this->errors['validation_error'][$key] = $message; 	
				}

			}elseif($response_status == 401){

				$this->errors['system_error'] = 'Pin server request was not authenticated.'; 

			}else{

				$this->errors['system_error'] = 'Pin server is not working properly, it responded with a ' . $response_status; 

			}

			return false;

		}catch(ServerErrorResponseException $e){

			$response_status = $e->getResponse()->getStatusCode();
			$this->errors = array(
				'system_error'	=> 'Pin server is not working properly, it responded with a ' . $response_status,
			);
			return false;

		}catch(CurlException $e){

			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);
			return false;

		}

		return true;

	}

	public function charge_customer($input_data){

		$data = elements(array(
			'email',
			'description',
			'amount',
			'ipAddress',
			'currency',
			'customerToken',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'email',
				'label'	=> 'Email',
				'rules'	=> 'required|valid_email',
			),
			array(
				'field'	=> 'description',
				'label'	=> 'Description',
				'rules'	=> 'required'
			),
			array(
				'field'	=> 'amount',
				'label'	=> 'Amount',
				'rules'	=> 'required|numeric|greater_than[99]'
			),
			array(
				'field'	=> 'ipAddress',
				'label'	=> 'IP Address',
				'rules'	=> 'required'
			),
			array(
				'field'	=> 'currency',
				'label'	=> 'Currency',
				'rules'	=> 'required|alpha|max_length[3]',
			),
			array(
				'field'	=> 'customerToken',
				'label'	=> 'Customer Token',
				'rules'	=> 'required',
			),
		));

		$validation_errors = [];

		if(!isset($data['email']) OR !isset($data['description']) OR !isset($data['amount']) OR !isset($data['ipAddress']) OR !isset($data['currency']) OR !isset($data['customerToken'])){
			$validation_errors['charge'] = 'Necessary fields are missing to charge a customer.';
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

		try{

			$pin_data = $data;
			$pin_data['ip_address'] = $pin_data['ipAddress'];
			$pin_data['customer_token'] = $pin_data['customerToken'];
			unset($pin_data['ipAddress']);
			unset($pin_data['customerToken']);

			$request = $this->client->post(
				'charges',
				array(
					'Content-Type'	=> 'application/json'
				),
				json_encode($pin_data)
			);

			$response = $request->send();
			$response_array = $response->json();

		}catch(ClientErrorResponseException $e){

			$response_status = $e->getResponse()->getStatusCode();
			$response_array = $e->getResponse()->json();

			if($response_status == 400){

				$this->errors['validation_error']['customerToken'] = $response_array['error_description'];

			}elseif($response_status == 422){

				foreach($response_array['messages'] as $error){

					$key = $error['param'];
					$message = $error['message'];

					switch($key){
						case 'ip_address':
							$key = 'ipAddress';
							break;
						case 'customer_token':
							$key = 'customerToken';
							break;
					}

					$this->errors['validation_error'][$key] = $message; 	

				}

			}elseif($response_status == 401){

				$this->errors['system_error'] = 'Pin server request was not authenticated.'; 

			}else{

				$this->errors['system_error'] = 'Pin server is not working properly, it responded with a ' . $response_status; 

			}

			return false;

		}catch(ServerErrorResponseException $e){

			$response_status = $e->getResponse()->getStatusCode();
			$this->errors = array(
				'system_error'	=> 'Pin server is not working properly, it responded with a ' . $response_status,
			);
			return false;

		}catch(CurlException $e){

			$this->errors = array(
				'system_error'	=> 'Curl failed. Try again later.'
			);
			return false;

		}

		return $response_array['response'];

	}

	public function get_errors(){

		return $this->errors;

	}

}