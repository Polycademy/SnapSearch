<?php

/*
	Except for admins
	the userId should always be the currently logged in user
	it should not be able to be updated or assigned to other people
 */

class Billing extends CI_Controller{

	protected $authenticator;
	protected $auth_response;
	protected $user;

	public function __construct(){

		parent::__construct();

		$this->load->model('Billing_model');
		$this->load->model('Stripe_model');

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
		$valid = $this->input->get('valid', true);
		$valid = ($valid) ? $valid : null;

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

			$query = $this->Billing_model->read_all($user_id, $active, $valid);

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

		// we cannot use the Template::compose function, as that converts all string-like integers into integers
		// including "0000" which is a possible card hint
		header('Content-type: application/json');
		echo json_encode($output);

	}

	public function show($id){

		$query = $this->Billing_model->read($id);

		if($query){

			$user_id = $query['userId'];
			if(!$this->user->authorized([
				'roles'	=> 'admin'
			], [
				'users'	=> $user_id
			])){

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
		
		// we cannot use the Template::compose function, as that converts all string-like integers into integers
		// including "0000" which is a possible card hint
		header('Content-type: application/json');
		echo json_encode($output);

	}

	public function create(){

		// this will have userId, stripeToken, stripeEmail
		$data = $this->input->json(false);

		$user_id = (isset($data['userId'])) ? intval($data['userId']) : 0;

		if(!$this->user->authorized([
			'roles'	=> 'admin'
		], [
			'users'	=> $user_id
		])){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to submitting billing information.';
			$code = 'error';

		}else{

			$stripe_query = $this->Stripe_model->create_customer($data);

			if ($stripe_query) {
				$data = array_merge($data, $stripe_query);
				$billing_query = $this->Billing_model->create($data);
			}

			if(!$stripe_query){

				$content = current($this->Stripe_model->get_errors());
				$code = key($this->Stripe_model->get_errors());

			}elseif(!$billing_query){

				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());

			}else{

				$content = $billing_query;
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

	/**
	 * There are 2 aspects to updating the card.
	 * Updating the card details on Stripe (which may result in updating our own database).
	 * Or updating the card details on the current database.
	 * If we are fully integrating with Stripe, its best not to try to perform state replication, that is 
	 * having 2 sources of state. Like it wouldn't be useful for us to hold the last 4 digits while 
	 * Stripe also has the last 4 digits. If we want to improve redundancy by holding the data ourselves, 
	 * this adds the extra complication of state replication, and having to maintain 2 branches of 
	 * control flow, one for dealing with updating our own database and one for updating the other database 
	 * and maintaing some lens between them.
	 * This update handler is really about updating Stripe's details and synchronising the relevant changes 
	 * to our database.
	 * Also note that updating Stripe details occurs through their own form, and we just get the details 
	 * post-hoc.
	 * Also remember that the email and card details are associated to the stripe token, and the stripe 
	 * token is associated to a customer as a source. So it doesn't really matter if the user updates their 
	 * email, since that's just associated to a customer as a source. The customer doesn't really have a 
	 * main email beyond their default source's email.
	 */
	public function update($id){

		$data = $this->input->json(false);

		$user_id = (isset($data['userId'])) ? intval($data['userId']) : 0;

		if(!$this->user->authorized([
			'roles'	=> 'admin'
		], [
			'users'	=> $user_id
		])){

			$this->auth_response->setStatusCode(401);
			$content = 'Not authorized to update billing information.';
			$code = 'error';

		}else{

			//non administrators are not allowed the change whether card is invalid or not
			//the only way for people to resolve their billing error is delete their errored card
			//and create a new card
			if(!$this->user->authorized(['roles' => 'admin'])){
				unset(
					$data['cardInvalid'],
					$data['cardInvalidReason']
				);
			}

			$customer_billing_query = $this->Billing_model->read($id);
			
			if ($customer_billing_query) {
				$stripe_query = $this->Stripe_model->update_customer($customer_billing_query['customerToken'], $data);
				if ($stripe_query) {
					$data = array_merge($data, $stripe_query);
					$billing_query = $this->Billing_model->update($id, $data);
				}	
			}
			
			if(!$customer_billing_query){

				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());

			}elseif(!$stripe_query){

				$content = current($this->Stripe_model->get_errors());
				$code = key($this->Stripe_model->get_errors());

			}elseif(!$billing_query){

				$content = current($this->Billing_model->get_errors());
				$code = key($this->Billing_model->get_errors());

			}else{

				$content = $billing_query;
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

			if(!$this->user->authorized([
				'roles'	=> 'admin'
			], [
				'users'	=> $user_id
			])){

				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to delete billing information.';
				$code = 'error';

			}else{

				$this->Stripe_model->delete_customer($query['customerToken']);
				$query = $this->Billing_model->delete($id);
				$content = (int) $id;
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