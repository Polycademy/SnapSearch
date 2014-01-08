<?php

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

	public function index(){

		$user_id = $this->input->get('user', true);
		$offset = $this->input->get('offset', true);
		$limit = $this->input->get('limit', true);

		$authorized = false;

		if(!$user_id){

			if($this->user->authorized(false, 'admin')){

				$authorized = true;

			}else{

				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to view this information.';
				$code = 'error';

			}

		}else{

			if($this->user->authorized(false, 'admin') OR $this->user->authorized(false, false, $user_id)){

				$authorized = true;

			}else{

				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to view this information.';
				$code = 'error';

			}

		}

		if($authorized){

			if(empty($limit)) $limit = 100;
			if(empty($offset)) $offset = 0;

			$query = $this->Usage_model->read_all($offset, $limit, $user_id);

			if($query){

				$content = $query;
				$code = 'success';

			}else{

				$this->auth_response->setStatusCode(404);
				$content = current($this->Usage_model->get_errors());
				$code = key($this->Usage_model->get_errors());

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