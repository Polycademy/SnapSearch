<?php

class Migrate extends CI_Controller {
 
	public function __construct(){
 
		parent::__construct();

		if(!$this->input->is_cli_request()){
			exit;
		}
		
		$this->load->library('migration');
 
	}
	
	public function index(){
	
		echo 'Migration is initialised. Make sure this is not accessible in production! You may need to run this first before running any of the functions, especially if they make consecutive modifications to your tables. Now go do the latest, current, version or restart!';
	
	}
 
	public function latest(){ 
	
		if(!$this->migration->latest()){
			echo $this->migration->error_string();
		}

		echo 'Migrated to latest';
 
	}
	
	public function current(){
	
		if(!$this->migration->current()){
			echo $this->migration->error_string();
		}

		echo 'Migrated to current';
	
	}
	
	public function version($num){
	
		if(!$this->migration->version($num)){
			echo $this->migration->error_string();
		}

		echo 'Migrated to version ' . $num;
	
	}
	
	//restarts the migration from 0 to the number specified or latest
	public function restart($num = false){
	
		$this->migration->version(0);
		
		if(!empty($num)){
		
			if(!$this->migration->version($num)){
				echo $this->migration->error_string();
			}

			echo 'Restarted migration to ' . $num;
		
		}else{
		
			if(!$this->migration->latest()){
				echo $this->migration->error_string();
			}

			echo 'Restarted migration to latest';
		
		}
	
	}
 
}