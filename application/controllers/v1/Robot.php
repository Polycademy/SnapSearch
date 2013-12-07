<?php

//THE ROBOT needs to incorporate the user accounts system, and also the billing_model to check api limit and increment accordingly
class Robot extends CI_Controller{

	protected $request;

	public function __construct(){

		parent::__construct();
		$this->load->model('v1/Robot_model');
		$ioc = $this->config->item('ioc');
		$this->request = $ioc['Request'];

	}

	public function query(){

		$parameters = $this->request->query->all();

		$query = $this->Robot_model->read_site($parameters);

		if($query){
			
			$content = $query; //assign query
			$code = 'success'; //assign code
			
		}else{

			$errors = $this->Robot_model->get_errors();
			$fallback = $this->Robot_model->get_fallback();

			if(!empty($fallback)){
				$content = array(
					'error'		=> current($errors),
					'fallback'	=> $fallback,
				);
			}else{
				$content = current($errors);
			}
		
			$code = key($errors);
			
			if($code == 'validation_error' OR $code == 'error'){
				$this->output->set_status_header(400);
			}elseif($code == 'system_error'){
				$this->output->set_status_header(500);
			}
			
		}
		
		$output = array(
			'content'	=> $content,
			'code'		=>$code,
		);
		
		Template::compose(false, $output, 'json');

	}

	public function post(){

		$parameters = $this->request->request->all();

		$query = $this->Robot_model->read_site($parameters);
		
		if($query){
			
			$content = $query; //assign query
			$code = 'success'; //assign code
			
		}else{
		
			$errors = $this->Robot_model->get_errors();
			$fallback = $this->Robot_model->get_fallback();

			if(!empty($fallback)){
				$content = array(
					'error'		=> current($errors),
					'fallback'	=> $fallback,
				);
			}else{
				$content = current($errors);
			}

			$code = key($errors);
			
			if($code == 'validation_error' OR $code == 'error'){
				$this->output->set_status_header(400);
			}elseif($code == 'system_error'){
				$this->output->set_status_header(500);
			}
			
		}
		
		$output = array(
			'content'	=> $content,
			'code'		=>$code,
		);
		
		Template::compose(false, $output, 'json');

	}

}