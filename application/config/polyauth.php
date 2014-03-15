<?php

$config['polyauth'] = array(
	//SESSION SETTINGS
	'session_namespace'		=> 'snapsearch',
	//EMAIL SETTINGS
	'email'					=> true,
	'email_smtp'			=> true,
	'email_host'			=> 'smtp.mandrillapp.com',
	'email_port'			=> '587',
	'email_auth'			=> true,
	'email_username'		=> $_ENV['secrets']['mandrill_api_user'],
	'email_password'		=> $_ENV['secrets']['mandrill_api_pass'],
	'email_smtp_secure'		=> 'tls',
	'email_from'			=> 'enquiry@snapsearch.io',
	'email_from_name'		=> 'SnapSearch',
	'email_html'			=> true,
	// 'email_forgotten_password_template'	=> '', //THIS NEEDS TO HAVE A LOADED TEMPLATE
	//LOGIN SETTINGS
	'login_identity'			=> 'email',
	'login_password_complexity'	=> array(
		'min'			=> 6, //('' or false or 8)
		'max'			=> 100,
		'lowercase'		=> false,
		'uppercase'		=> false,
		'number'		=> false,
		'specialchar'	=> false,
		'diffpass'		=> false, //number of characters different from old password ('' or false or 3)
		'diffidentity'	=> false,
		'unique'		=> false, //number of unique characters ('' or false or 4) ('' defaults to 4)
	),
	//REGISTRATION SETTINGS
	'reg_activation'		=> false,
);