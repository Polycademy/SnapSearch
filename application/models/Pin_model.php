<?php

use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Exception\CurlException;

/**
 * Pin Model contacts the Pin service API to CRUD customers.
 */
class Pin_model extends CI_Model{

	protected $api_url;
	protected $api_key;
	protected $client;

	public function __construct(){

		$this->api_url = 'https://api.pin.net.au';
		$this->api_key = $_ENV['secrets']['pin_api_key'];
		$this->client = new Client;
		$this->client->setUserAgent('Snapsearch');

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

		if(!isset($data['email']) OR !isset($data['cardNumber']) OR !isset($data['cardCvc']) OR !isset($data['cardExpiryMonth']) OR !isset($data['cardExpiryYear']) OR !isset($data['cardName']) OR !isset($data['cardAddress']) OR !isset($data['cardCity']) OR !isset($data['cardCountry'])){
			$validation_errors['customer'] = 'Necessary fields are missing to create a customer.';
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
				$this->api_url,
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
						case 'number':
							$key = 'cardNumber';
							break;
						case 'expiry_month':
							$key = 'cardExpiryMonth';
							break;
						case 'expiry_year':
							$key = 'cardExpiryYear';
							break;
						case 'cvc':
							$key = 'cardCvc';
							break;
						case 'name':
							$key = 'cardName';
							break;
						case 'address_line1':
							$key = 'cardAddress';
							break;
						case 'address_country':
							$key = 'cardCountry';
							break;
						case 'address_postcode':
							$key = 'cardPostCode';
							break;
						case 'address_state':
							$key = 'cardState';
							break;
					}

					$this->errors['validation_error'][$key] = $message; 	
				}

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

	public function update_customer($customer_token, $data){

		//needs all or nothing
		//if nothing is provided in the data


	}

	public function charge_customer($customer_token, $data){

		//take customer code, and charge, and return charge code

	}

}