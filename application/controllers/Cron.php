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

	public function monthly_billing(){

		$this->load->model('Billing_model');
		$this->load->model('Payments_model');
		$this->load->model('Usage_model');

		//this will execute the billing

	}

}