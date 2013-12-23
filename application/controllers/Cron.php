<?php

//add the billing stuff here too!
class Cron extends CI_Controller{

	public function __construct(){
 
		parent::__construct();

		if(!$this->input->is_cli_request()){
			exit;
		}
 
	}

	/**
	 * Purge (clean) the snapshot cache. If no allowed_length is passed, all of the cache is deleted
	 * @param  boolean $allowed_length Allowed length in ISO8601
	 * @param  boolean $user_id        Cache a specific user's snapshot
	 */
	public function purge_cache($allowed_length = false, $user_id = false){

		$this->load->model('v1/Robot_model');

		$query = $this->Robot_model->purge_cache($allowed_length, $user_id);

		if($query === true){
			echo 'Purged cache using length: ' . intval($allowed_length) . ' and user id: ' . $user_id;
		}else{
			echo $query;
		}

	}

	/**
	 * This function will be ran from the Cron service. Make sure this is done well and no bugs!
	 * @return [type] [description]
	 */
	public function monthly_billing(){

		$this->load->model('Accounts_model');
		$this->load->model('Usage_model');
		$this->load->model('Billing_model');
		$this->load->model('Pin_model');
		$this->load->model('Payments_model');
		$this->load->model('Email_model');

		//also probably need an email model

		//this will execute the billing
		//cycle through Accounts_model
		//implement API monthly cleanup
		//each account that has a customer object
		//needs to be charged
		//using the Pin model
		//once charged, create the payment record and invoice in the Payments_model
		//send email notification via the Email_model

	}

}