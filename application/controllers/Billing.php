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
	 * There are 2 aspects to updating the card:
	 * 
	 * 1. Updating the card details on Stripe (which leads to 2.).
	 * 2. Updating the card details on our database (which doesn't lead to 1.).
	 *
	 * Stripe details are updated through their own iframed form.
	 * The JS application receives a token from this operation.
	 * The token will be submitted to us here.
	 *
	 * All we need to do is to take the token and update our own database for the current user.
	 * Although the DB was built for multiple cards per user. The application code forces 1 
	 * customer per user.
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

			// customerToken should be acquired from the database not from user input
			unset($data['customerToken']);

			// non administrators are not allowed the change whether card is invalid or not
			// the only way for people to resolve their billing error is delete their errored card
			// and create a new card
			if(!$this->user->authorized(['roles' => 'admin'])){
				unset(
					$data['cardInvalid'],
					$data['cardInvalidReason']
				);
			}

			$customer_billing_query = $this->Billing_model->read($id);

			// if the user is not an admin, and also not the owner of this 
			// billing record, then they are not allowed update this billing record
			$allowed_to_update_this_id = true;
			if (
				$customer_billing_query 
				AND !$this->user->authorized(['roles' => 'admin'])
				AND $customer_billing_query['userId'] != $user_id 
			) {
				$allowed_to_update_this_id = false;
				$this->auth_response->setStatusCode(401);
				$content = 'Not authorized to update billing information.';
				$code = 'error';
			}

			if ($allowed_to_update_this_id) {
		
				if ($customer_billing_query) {
					
					// if there's billing record for this id
					// then we use the customerToken of the billing record to 
					// identify the customer on the stripe database
					// and associate the new or updated billing source identified by the stripeToken
					// the only thing required in this data are:
					// stripeToken and stripeEmail
					$stripe_query = $this->Stripe_model->update_customer($customer_billing_query['customerToken'], $data);

					if ($stripe_query) {

						// if successful, then we get back:
						// the same customerToken
						// a potentially different customerEmail
						// and a potentially different cardNumber
						// this is merged with the existing update data
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
	 * Unlike the update operation, this actually just deletes the billing token id.
	 * This id is not the stripe token, but the actual billing id.
	 * 
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
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