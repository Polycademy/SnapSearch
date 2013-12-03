<?php

class Accounts_model extends CI_Model{

	protected $accounts_manager;
	protected $emailer;
	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->library('form_validation', false, 'validator');

		$ioc = $this->config->item('ioc');
		$this->accounts_manager = $ioc['PolyAuth\AccountsManager'];
		$this->emailer = $ioc['PolyAuth\Emailer'];

	}

	public function create(){

	}

	public function read(){

	}

	public function read_all(){

	}

	public function update(){

	}

	public function delete(){

	}

	public function send_forgotten_password_confirmation($identifier){

	}

	public function confirm_forgotten_password($input_data){

	}

}