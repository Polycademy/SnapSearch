<?php

/**
 * Payments controller is a HTTP API that allows the payment history to be viewed from a logged in user.
 */
class Payments extends CI_Controller{

	protected $authenticator;
	protected $auth_response;
	protected $user;

	public function __construct(){

		parent::__construct();

		$this->load->model('Payments_model');

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

			if($this->user->authorized(['roles' => 'admin'])){

				$authorized = true;

			}else{

				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to view this information.';
				$code = 'error';

			}

		}else{

			if($this->user->authorized([
				'roles'	=> 'admin'
			], [
				'users'	=> $user_id
			])){

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

			$query = $this->Payments_model->read_all($offset, $limit, $user_id);

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