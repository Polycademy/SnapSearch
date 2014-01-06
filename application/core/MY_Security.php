<?php

class MY_Security extends CI_Security{

	//overriding the normal csrf_verify, this gets automatically called in the Input library's constructor
	//verifying on POST and PUT and DELETE
	public function csrf_verify(){

		$request_method = strtoupper($_SERVER['REQUEST_METHOD']);

		//If it is GET, ignore the rest
		if($request_method == 'GET' OR $request_method == 'HEAD' OR $request_method == 'OPTIONS'){
			return $this->csrf_set_cookie();
		}

		// Check if URI has been whitelisted from CSRF checks
		if($exclude_uris = config_item('csrf_exclude_uris')){
			$uri = load_class('URI', 'core');
			if(in_array($uri->uri_string(), $exclude_uris)){
				return $this;
			}
		}

		//Double submit cookie method: COOKIE needs to exist and at least either POST or SERVER needs to exist and at least one of the POST or SERVER must match the COOKIE
		if(
			!isset($_COOKIE[$this->_csrf_cookie_name])
			OR
			(
				!isset($_POST[$this->_csrf_cookie_name])
				AND
				!isset($_SERVER['HTTP_X_XSRF_TOKEN'])
			)
		){

			$this->csrf_show_error();

		}

		//if CSRF token was in the POST, then it needs to match the cookie
		if(isset($_POST[$this->_csrf_token_name])){
			if($_POST[$this->_csrf_token_name] !== $_COOKIE[$this->_csrf_cookie_name]){
				$this->csrf_show_error();
			}
		}

		//if CSRF token was in the SERVER (headers), then it needs to match the cookie
		if(isset($_SERVER['HTTP_X_XSRF_TOKEN'])){
			if($_SERVER['HTTP_X_XSRF_TOKEN'] !== $_COOKIE[$this->_csrf_cookie_name]){
				$this->csrf_show_error();
			}
		}
		
		// We kill this since we're done and we don't want to polute the _POST array
		unset($_POST[$this->_csrf_token_name]);

		if(config_item('csrf_regenerate')){
			unset($_COOKIE[$this->_csrf_cookie_name]);
			$this->_csrf_hash = '';
		}

		$this->_csrf_set_hash();
		$this->csrf_set_cookie();

		log_message('debug', 'CSRF token verified');
		return $this;
	
	}

}