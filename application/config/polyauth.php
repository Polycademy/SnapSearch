<?php

$config['polyauth'] = array(
	//EMAIL SETTINGS
	'email'					=> true,
	'email_smtp'			=> true,
	'email_host'			=> 'smtp.mandrillapp.com',
	'email_port'			=> '587',
	'email_auth'			=> true,
	'email_username'		=> $_ENV['secrets']['mandrill_user'],
	'email_password'		=> $_ENV['secrets']['mandrill_key'],
	'email_smtp_secure'		=> 'tls',
	'email_from'			=> 'enquiry@polycademy.com',
	'email_from_name'		=> 'SnapSearch',
	'email_html'			=> true,
	// 'email_forgotten_password_template'	=> '', //THIS NEEDS TO HAVE A LOADED TEMPLATE
	//LOGIN SETTINGS
	'login_identity'		=> 'email',
	//REGISTRATION SETTINGS
	'reg_activation'		=> false,
);