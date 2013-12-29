<?php

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;

/**
 * Pin Model contacts the Pin service API to CRUD customers.
 */
class Pin_model extends CI_Model{

	protected $client;
	protected $api_key;

	public function __construct(){

		$this->client = new Client;
		$this->client->setUserAgent('Snapsearch');


	}

	public function create_customer(){

		//should return the customer code

	}

	public function update_customer($customer_token, $data){

		//needs all or nothing
		//if nothing is provided in the data


	}

	public function charge_customer(){

		//take customer code, and charge, and return charge code

	}

}