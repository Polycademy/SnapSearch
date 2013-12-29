<?php

class Billing extends CI_Controller{

	protected $authenticator;
	protected $auth_response;
	protected $user;

	public function __construct(){

		parent::__construct();

		$this->load->model('Billing_model');
		$this->load->model('Pin_model');

		$ioc = $this->config->item('ioc');
		$this->authenticator = $ioc['PolyAuth\Authenticator'];
		$this->authenticator->start();

		$this->auth_response = $this->authenticator->get_response();
		$this->user = $this->authenticator->get_user();

	}

	public function index(){

		$user_id = $this->input->get('user', true);
		$active = $this->input->get('active', true);
		$active = ($active) ? $active : null;

		if(!$user_id){

			//all the billing information is only available for the admin
			if(!$this->user->authorized(false, 'admin')){

				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to view this information.';
				$code = 'error';
				$authorized = false;

			}else{

				$authorized = true;

			}
			
		}else{

			if(!$this->user->authorized(false, 'admin') AND !$this->user->authorized(false, false $user_id)){

				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to view this information.';
				$code = 'error';
				$authorized = false;

			}else{

				$authorized = true;

			}

		}

		if($authorized){

			$query = $this->Billing_model->read_all($user_id, $active);

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

	public function show($id){

		$query = $this->Billing_model->read($id);

		if($query){

			$user_id = $query['userId'];
			if(!$this->user->authorized(false, 'admin') AND !$this->user->authorized(false, false $user_id)){

				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to view this information.';
				$code = 'error';

			}else{

				$content = $query;
				$code = 'success';

			}

		}else{

			$this->auth_response->setStatusCode(404);
			$content = current($this->Billing_model->get_errors());
			$code = key($this->Billing_model->get_errors());

		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);
		
		Template::compose(false, $output, 'json');

	}

	public function create(){

		$data = $this->input->json(false);
		$user_id = (isset($data['userId'])) ? intval($user_id) : 0;

		if(!$this->user->authorized(false, 'admin') AND !$this->user->authorized(false, false $user_id)){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to submitting billing information.';
			$code = 'error';

		}else{

			$pin_query = $this->Pin_model->create_customer($data);
			if($pin_query){
				$data['customerToken'] = $pin_query;
				$billing_query = $this->Billing_model->create($data);
			}

			if(!$pin_query){

				$content = current($this->Pin_model->get_errors());
				$code = key($this->Pin_model->get_errors());

			}elseif(!$billing_query){

				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());

			}else{

				$content = $query;
				$code = 'success';

			}

			if($code == 'success'){
				$this->auth_response->setStatusCode(201);
			}elseif($code == 'validation_error'){
				$this->auth_response->setStatusCode(400);
			}elseif($code == 'system_error'){
				$this->auth_response->setStatusCode(500);
			}

			$this->auth_response->sendHeaders();
			
			$output = array(
				'content'	=> $content,
				'code'		=> $code,
			);
			
			Template::compose(false, $output, 'json');

		}

	}

	public function update($id){

		$data = $this->input->json(false);
		$user_id = (isset($data['userId'])) ? intval($user_id) : 0;

		if(!$this->user->authorized(false, 'admin') AND !$this->user->authorized(false, false $user_id)){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to update billing information.';
			$code = 'error';

		}else{

			//only if the data is attempting to update the card do we try to update the card
			$updating_card = false;
			$keys = array_keys($data);
			$necessary_keys = array(
				'cardNumber',
				'cardCvc',
				'cardExpiryMonth',
				'cardExpiryYear',
				'cardName',
				'cardAddress',
				'cardCity',
				'cardCountry'
			);
			foreach($necessary_keys as $necessary_key){
				if(array_key_exists($data, $necessary_key){
					$updating_card =  true;
					break;
				}
			}

			if($updating_card){

				$customer_token_query = $this->Billing_model->read($id);
				if($customer_token_query){
					$pin_query = $this->Pin_model->update_customer($customer_token_query['customerToken'], $data);
					if($pin_query){
						$billing_query = $this->Billing_model->update($id, $data);
					}
				}

			}else{

				$customer_token_query = true;
				$pin_query = true;
				$billing_query = $this->Billing_model->update($id, $data);

			}

			if(!$customer_token_query){

				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());

			}elseif(!$pin_query){

				$content = current($this->Pin_model->get_errors());
				$code = key($this->Pin_model->get_errors());

			}elseif(!$billing_query){

				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());

			}else{

				$content = $query;
				$code = 'success';

			}

			if($code == 'success'){
				$this->auth_response->setStatusCode(200);
			}elseif($code == 'validation_error'){
				$this->auth_response->setStatusCode(400);
			}elseif($code == 'system_error'){
				$this->auth_response->setStatusCode(500);
			}

			$this->auth_response->sendHeaders();
			
			$output = array(
				'content'	=> $content,
				'code'		=> $code,
			);
			
			Template::compose(false, $output, 'json');

		}

	}

	public function delete($id){

		$query = $this->Billing_model->read($id);

		if($query){

			$user_id = $query['userId'];

			if(!$this->user->authorized(false, 'admin') AND !$this->user->authorized(false, false, $user_id)){

				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to delete billing information.';
				$code = 'error';

			}else{

				$query = $this->Billing_model->delete($id);
				$content = $id;
				$code = 'success';

			}

		}else{

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