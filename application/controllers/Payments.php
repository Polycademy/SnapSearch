<?php

//payments history is only recorded from the controller, but the biller will add payments history on a scheduled basis
class Payments extends CI_Controller{

	public function __construct(){

		parent::__construct();

		$this->load->model('Payments_model');

		$ioc = $this->config->item('ioc');
		$this->authenticator = $ioc['PolyAuth\Authenticator'];
		$this->authenticator->start();

		$this->auth_response = $this->authenticator->get_response();
		$this->user = $this->authenticator->get_user();

	}

	public function show($user_id){

		//private information	
		if(!$this->user->authorized(false, 'admin') AND !$this->user->authorized(false, false $user_id)){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to view this information.';
			$code = 'error';

		}else{

			$query = $this->Payments_model->read($user_id);

			if($query){

				$content = $query;
				$code = 'success';

			}else{

				$this->auth_response->setStatusCode(404);
				$content = current($this->Payments_model->get_errors());
				$code = key($this->Payments_model->get_errors());

			}

		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);
		
		Template::compose(false, $output, 'json');

	}

}