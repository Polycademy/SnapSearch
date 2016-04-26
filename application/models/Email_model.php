<?php

class Email_model extends CI_Model{

	protected $mailer;
	protected $sparkpost_user;
	protected $sparkpost_pass;
	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->mailer = new \PHPMailer;
		$this->sparkpost_user = $_ENV['secrets']['sparkpost_api_user'];
		$this->sparkpost_pass = $_ENV['secrets']['sparkpost_api_pass'];

		$this->load->library('form_validation', false, 'validator');

	}

	public function prepare_email($template, $data){

		return $this->load->view($template, $data, true);

	}

	public function send_email(
		$from_address,
		array $to_addresses,
		$subject,
		$html_message,
		array $attachments = null,
		array $attachment_strings = null
	){

		$this->mailer->IsSMTP();
		$this->mailer->Host = 'smtp.sparkpostmail.com';
		$this->mailer->Port = 587;
		$this->mailer->SMTPAuth = true;
		$this->mailer->Username = $this->sparkpost_user;
		$this->mailer->Password = $this->sparkpost_pass;
		$this->mailer->SMTPSecure = 'tls';

		$this->mailer->From = $from_address;
		$this->mailer->FromName = 'SnapSearch';
		foreach($to_addresses as $to){
			$this->mailer->AddAddress($to);
		}

		$this->mailer->Subject = $subject;
		$this->mailer->Body = $html_message;
		$this->mailer->isHTML(true);

		if($attachments){
			foreach($attachments as $attachment_name => $attachment_file){
				if(is_string($attachment_name)){
					$this->mailer->addAttachment($attachment_file, $attachment_name);
				}else{
					$this->mailer->addAttachment($attachment_file);
				}
			}
		}

		//binary string attachments need to have a specified name
		if($attachment_strings){
			foreach($attachment_strings as $attachment_name => $attachment_file){
				$this->mailer->addStringAttachment($attachment_file, $attachment_name);
			}
		}

		if(!$this->mailer->Send()){
			$this->errors = [
				'system_error'	=> 'Mailer Error: ' . $this->mailer->ErrorInfo,
			];
			return false;
		}

		return true;

	}

	public function get_errors(){

		return $this->errors;

	}

}