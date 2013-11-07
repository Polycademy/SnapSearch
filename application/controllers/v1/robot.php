<?php

class Robot extends CI_Controller{

	public function __construct(){

		parent::__construct();

		$this->load->model('v1/Robot_model');

		//caching in the mysql database
		//support get and create (one expects options in Query Param)
		//The other gets params via the body
		//update will precache it
		//delete will remove it

	}

	public function show(){

	}

	public function create(){

	}

	public function update(){

	}

	public function delete(){
		
	}

}