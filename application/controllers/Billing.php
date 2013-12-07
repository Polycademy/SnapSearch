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

	public function show($user_id){

		//private information	
		if(!$this->user->authorized(false, 'admin') AND !$this->user->authorized(false, false $user_id)){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to view this information.';
			$code = 'error';

		}else{

			$query = $this->Billing_model->read($user_id);

			if($query){

				$content = $query;
				$code = 'success';

			}else{

				$this->auth_response->setStatusCode(404);
				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());

			}

		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);
		
		Template::compose(false, $output, 'json');

	}

	public function create($user_id){

		if(!$this->user->authorized(false, 'admin') AND $this->user['id'] != $user_id){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to submitting billing information.';
			$code = 'error';

		}else{

			$data = $this->input->json(false);
			$data['userId'] = $user_id;
			$query = $this->Billing_model->create($data);

			if($query){

				$this->auth_response->setStatusCode(201);
				$content = $query; //resource id
				$code = 'success';

			}else{

				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());
				
				if($code == 'validation_error'){
					$this->auth_response->setStatusCode(400);
				}elseif($code == 'system_error'){
					$this->auth_response->setStatusCode(500);
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

	public function update($user_id){

		if(!$this->user->authorized(false, 'admin') AND $this->user['id'] != $user_id){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to update billing information.';
			$code = 'error';

		}else{

			$data = $this->input->json(false);
			$query = $this->Billing_model->update($user_id, $data);

			if($query){

				$content = $user_id;
				$code = 'success';

			}else{

				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());

				if($code == 'validation_error'){
					$this->auth_response->setStatusCode(400);
				}elseif($code == 'system_error'){
					$this->auth_response->setStatusCode(500);
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

	public function delete($user_id){

		if(!$this->user->authorized(false, 'admin') AND $this->user['id'] != $user_id){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to delete billing information.';
			$code = 'error';

		}else{

			$query = $this->Billing_model->delete($user_id);

			if($query){

				$content = $user_id;
				$code = 'success';

			}else{

				//cant find the thing to delete
				$this->auth_response->setStatusCode(404);
				$content = current($this->Accounts_model->get_errors());
				$code = key($this->Accounts_model->get_errors());

			}

			$this->auth_response->sendHeaders();
			
			$output = array(
				'content'	=> $content,
				'code'		=> $code,
			);
			
			Template::compose(false, $output, 'json');

		}

	}

}