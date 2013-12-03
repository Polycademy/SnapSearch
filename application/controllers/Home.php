<?php

class Home extends CI_Controller{

	protected $auth_response;
	
	public function __construct(){
	
		parent::__construct();

		$ioc = $this->config->item('ioc');
		$this->authenticator = $ioc['PolyAuth\Authenticator'];
		$this->authenticator->start();
		$this->auth_response = $this->authenticator->get_response();
	
	}
	
	public function index(){

		$this->auth_response->sendHeaders();

		//when we're in production we can cache the main page for 48 hrs, this requires the cache to be writable, or else this won't work!
		if(ENVIRONMENT == 'production'){
			$this->output->cache(2880);
		}
		
		//due to single page app, we're just going with a default layout, no need for server side templating libraries
		$this->load->view('layouts/default_layout', $this->config->item('sitemeta'));
	
	}

}