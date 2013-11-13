<?php

class Robot extends CI_Controller{

	protected $request;

	public function __construct(){

		parent::__construct();
		$this->load->model('v1/Robot_model');
		$ioc = $this->config->item('ioc');
		$this->request = $ioc['Request'];

	}

	public function query(){

		$parameters = array(
			'url'				=> $this->request->query->get('url'),
			'width'				=> $this->request->query->get('width'),
			'height'			=> $this->request->query->get('height'),
			'imgformat'			=> $this->request->query->get('imgformat'),
			'useragent'			=> $this->request->query->get('useragent'),
			'screenshot'		=> $this->request->query->get('screenshot'),
			'loadimages'		=> $this->request->query->get('loadimages'),
			'javascriptenabled'	=> $this->request->query->get('javascriptenabled'),
			'maxtimeout'		=> $this->request->query->get('maxtimeout'),
			'initialwait'		=> $this->request->query->get('initialwait'),
			'callback'			=> $this->request->query->get('callback'),
			'cache'				=> $this->request->query->get('cache'),
			'cachetime'			=> $this->request->query->get('cachetime'),
		);

		//array filter to remove the null elements
		$parameters = array_filter($parameters);

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
		
			$code = key($this->Robot_model->get_errors());
			
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

		$parameters = array(
			'url'				=> $this->request->request->get('url'),
			'width'				=> $this->request->request->get('width'),
			'height'			=> $this->request->request->get('height'),
			'imgformat'			=> $this->request->request->get('imgformat'),
			'useragent'			=> $this->request->request->get('useragent'),
			'screenshot'		=> $this->request->request->get('screenshot'),
			'loadimages'		=> $this->request->request->get('loadimages'),
			'javascriptenabled'	=> $this->request->request->get('javascriptenabled'),
			'maxtimeout'		=> $this->request->request->get('maxtimeout'),
			'initialwait'		=> $this->request->request->get('initialwait'),
			'callback'			=> $this->request->request->get('callback'),
			'cache'				=> $this->request->request->get('cache'),
			'cachetime'			=> $this->request->request->get('cachetime'),
		);

		//array filter to remove the null elements
		$parameters = array_filter($parameters);

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