<?php

use SnapSearchClientPHP\SnapSearchException;

class Home extends CI_Controller{

	protected $interceptor;
	protected $authenticator;
	protected $auth_response;
	
	public function __construct(){
	
		parent::__construct();

		$ioc = $this->config->item('ioc');

		$this->interceptor = $ioc['SnapSearchClientPHP'];

		$this->authenticator = $ioc['PolyAuth\Authenticator'];
		$this->authenticator->start();
		$this->auth_response = $this->authenticator->get_response();
	
	}
	
	public function index(){

		$response = false;
		if(ENVIRONMENT == 'production'){
			try{
				$response = $this->interceptor->intercept();
			}catch(SnapSearchClientPHP\SnapSearchException $e){}
		}

		if($response){

			if(!empty($response['headers'])){
				foreach($response['headers'] as $header){
					if($header['name'] == 'Location'){
						header($header['name'] . ': ' . $header['value']);
					}
				}
			}

			$this->output->set_status_header($response['status']);

			echo $response['html'];

		}else{

			$this->auth_response->sendHeaders();
			
			//due to single page app, we're just going with a default layout, no need for server side templating libraries
			$this->load->view('layouts/default_layout');

		}
	
	}

}