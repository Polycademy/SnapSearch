<?php

class Billing extends CI_Controller{

	protected $authenticator;
	protected $auth_response;
	protected $user;

	public function __construct(){

		parent::__construct();

		$this->load->model('Billing_model');

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