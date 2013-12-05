<?php

//add the billing stuff here too!
class Cron extends CI_Controller{

	public function __construct(){
 
		parent::__construct();

		if(!$this->input->is_cli_request()){
			exit;
		}
		
		$this->load->model('v1/Robot_model');
 
	}

	public function purge_cache($allowed_length = false, $user_id = false){

		$query = $this->Robot_model->purge_cache($allowed_length, $user_id);

		if($query === true){
			echo 'Purged cache using length: ' . $allowed_length . ' and user id: ' . $user_id;
		}else{
			echo $query;
		}

	}

}