<?php

class Accounts extends CI_Controller{

	protected $authenticator;
	protected $auth_response;
	protected $user;

	public function __construct(){

		parent::__construct();

		$this->load->model('Accounts_model');
		$ioc = $this->config->item('ioc');
		$this->authenticator = $ioc['PolyAuth\Authenticator'];

		$this->auth_response = $this->authenticator->get_response();
		$this->user = $this->authenticator->get_user();

	}

	//this is really for admin use
	public function index(){

		$limit = $this->input->get('limit', true);
		$offset = $this->input->get('offset', true);

		if(empty($limit)) $limit = 100;
		if(empty($offset)) $offset = 0;

		$query = $this->Accounts_model->read_all($limit, $offset);
				
		if($query){

			//if admin we get to see all the details, but if not admin we need to remove properties
			if(!$this->user->authorized(false, 'admin')){
				foreach($query as &$user){
					unset(
						$user['email']
						$user['apiLimit'],
						$user['apiFreeLimit'],
						$user['apiUsage'],
						$user['apiLeftOverUsage'],
						$user['chargeInterval'],
						$user['chargeDate'],
						$user['customerToken'],
						$user['cardInvalid']
					);
				}
			}

			$content = $query;
			$code = 'success';
			
		}else{
		
			$this->auth_response->setStatusCode(404);
			$content = current($this->Accounts_model->get_errors());
			$code = key($this->Accounts_model->get_errors());
			
		}

		$this->auth_response->sendHeaders();
		
		$output = array(
			'content'	=> $content,
			'code'		=>$code,
		);
		
		Template::compose(false, $output, 'json');

	}

	//this is for admin, single logged in user, and others
	public function show($id){

		$query = $this->Accounts_model->read($id);

		if($query){

			//if not admin nor the user that owns this resource			
			if(!$this->user->authorized(false, 'admin') AND !$this->user->authorized(false, false, $id)){
				unset(
					$query['email']
					$query['apiLimit'],
					$query['apiFreeLimit'],
					$query['apiUsage'],
					$query['apiLeftOverUsage'],
					$query['chargeInterval'],
					$query['chargeDate'],
					$query['customerToken'],
					$query['cardInvalid']
				);
			}

			$content = $query;
			$code = 'success';
		
		}else{
		
			$this->auth_response->setStatusCode(404);
			$content = current($this->Accounts_model->get_errors());
			$code = key($this->Accounts_model->get_errors());
		
		}
		
		$output = array(
			'content'	=> $content,
			'code'		=> $code,
		);
		
		Template::compose(false, $output, 'json');

	}

	public function create(){


	}

	public function update($id){



	}

	public function delete($id){



	}

	public function activate($id){


	}

	public function forgot_password($id){


	}

	public function confirm_forgot_password($id){

		//get the forgotten code from the query parameter


	}
	
}