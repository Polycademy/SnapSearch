<?php

//these ids would be be the user id
class Usage extends CI_Controller{

	public function __construct(){

		parent::__construct();

		$this->load->model('Usage_model');

		$ioc = $this->config->item('ioc');
		$this->authenticator = $ioc['PolyAuth\Authenticator'];
		$this->authenticator->start();

		$this->auth_response = $this->authenticator->get_response();
		$this->user = $this->authenticator->get_user();

	}

	public function show($id){

	}

	public function create(){

	}

	public function update($id){

	}

	public function delete($id){

	}

}