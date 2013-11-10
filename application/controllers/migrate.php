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
	
		echo "Migration is initialised. Make sure this is not accessible in production!\n";
	
		echo "Currently available migrations:\n";

		print_r($this->migration->find_migrations());

	}
 
	public function latest(){ 
	
		if(!$this->migration->latest()){
			echo $this->migration->error_string();
		}

		echo "Migrated to latest\n";
 
	}
	
	public function current(){
	
		if(!$this->migration->current()){
			echo $this->migration->error_string();
		}

		echo "Migrated to current\n";
	
	}
	
	public function version($num){
	
		if(!$this->migration->version($num)){
			echo $this->migration->error_string();
		}

		echo 'Migrated to version ' . $num . "\n";
	
	}
	
	//restarts the migration from 0 to the number specified or latest
	public function restart($num = false){
	
		$this->migration->version(0);
		
		if(!empty($num)){
		
			if(!$this->migration->version($num)){
				echo $this->migration->error_string();
			}

			echo 'Restarted migration to ' . $num . "\n";
		
		}else{
		
			if(!$this->migration->latest()){
				echo $this->migration->error_string();
			}

			echo "Restarted migration to latest\n";
		
		}
	
	}
 
}