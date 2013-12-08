<?php

class Test extends CI_Controller{

	protected $authenticator;
	protected $session_persistence;
	protected $accounts_manager;

	public function __construct(){

		parent::__construct();

		//lets load PolyAuth
		//we need to test the creation of a user account
		//we have 2 strategies we need to involves ourselves
		//the first is CookieStrategy, the Second is the HTTPBasic
		//You'll need to activate CSRF checking on CookieStrat, but ignore the Robot path

		$CI = get_instance();
		$CI->load->database();
		$dbh = $CI->db->conn_id;
		$database = $dbh;

		$options = new PolyAuth\Options(array());

		$language = new PolyAuth\Language;

		$storage = new PolyAuth\Storage\MySQLAdapter($database, $options);

		$emailer = new PolyAuth\Emailer($options, $language);

		$accounts_manager = new PolyAuth\Accounts\AccountsManager($storage, $options, $language);

		$rbac = new PolyAuth\Accounts\Rbac($storage, $language);

		$session_persistence = new PolyAuth\Sessions\Persistence\FileSystemPersistence;
		$session_manager = new PolyAuth\Sessions\SessionManager($options, $language, $session_persistence);

		$cookie_strategy = new PolyAuth\Authentication\AuthStrategies\CookieStrategy($storage, $options, $language, $session_manager);

		$http_basic_strategy = new PolyAuth\Authentication\AuthStrategies\CookieStrategy($storage, $options, $language, $session_manager);

		$composite_strategy = new PolyAuth\Authentication\AuthStrategies\CompositeStrategy($cookie_strategy, $http_basic_strategy);

		$authenticator = new PolyAuth\Authentication\Authenticator($composite_strategy, $storage, $options, $language);

		$this->authenticator = $authenticator;
		$this->session_persistence = $session_persistence;
		$this->accounts_manager = $accounts_manager;

	}

	public function index(){

		$this->authenticator->start();

		//the $user object is not shared (return by value)
		$user = $this->authenticator->get_user();
		//the $response object is shared (return by ref)
		$response = $this->authenticator->get_response();
		//this session object is also shared (return by ref)
		//so therefore, any session properties will be persisted between anonymous users, and between anonymous user -> logged_in_user
		//BUT not between logged_in_user -> anonymous_user
		$session = $this->authenticator->get_session();

		//persisted on server side!
		$session['some_random_data'] = 'WOOT!';

		$response->sendHeaders();

		var_dump('RESPONSE:', (string) $response);

		//will be unauthorized
		var_dump('AUTHORIZED', $user->authorized());

		$this->authenticator->login(array(
			'identity'	=> 'admin@admin.com',
			'password'	=> 'password'
		));

		$logged_in_user = $this->authenticator->get_user();
		$logged_in_session =  $this->authenticator->get_session();

		$logged_in_session['lol'] = 'blah';

		//headers get overwritten easily
		$response->sendHeaders();

		//response is shared, so the object is updated
		var_dump('RESPONSE2:', (string) $response);

		//still unauthorized (due to the same user)
		var_dump('AUTHORIZED2', $user->authorized());

		var_dump('AUTHORIZED3', $logged_in_user->authorized());

		echo $logged_in_session['some_random_data'];
		echo $logged_in_session['lol'];

	}

	public function logged_in(){

		$this->authenticator->start();

		$response = $this->authenticator->get_response();

		var_dump((string) $response);

		$user = $this->authenticator->get_user();

		//should all return true when logged in, and should all return false when logged out
		var_dump('AUTHORIZED', $user->authorized());
		var_dump('AUTHORIZED', $user->authorized('public_read'));
		var_dump('AUTHORIZED', $user->authorized(false, 'admin'));
		var_dump('AUTHORIZED', $user->authorized(false, false, 1));

	}

	public function logout(){

		//rinse and repeat

		$this->authenticator->start();

		$response = $this->authenticator->get_response();

		//LOGOUT!
		$this->authenticator->logout();

		var_dump('RESPONSE:', (string) $response);

		$response->sendHeaders();

		echo 'logged out!';

	}

	public function register_account(){

		//lets register some accounts, then simulate doing modifications to the account which includes:
		//API throttle
		//Billing information (specifically customer reference number)
		//And of course charging through the pin.net API...


		$this->accounts_manager->register(array(
			'username'	=> 'Third user',
			'email'		=> 'third@new.com',
			'password'	=> 'hallabuloo'
		));

		$users = $this->accounts_manager->get_users();

		var_dump($users);

	}

	public function show_accounts(){

		$users = $this->accounts_manager->get_users();

		var_dump($users);

	}

	public function test_pdf(){

		$amount = 10000;

		$dollars = $amount / 100;
		$currency = 'AUD';

		$data = array(
			'invoiceNumber'	=> '#SS1',
			'date'	=> date('Y-m-d H:i:s'),
			'amount'	=> '$' . $dollars,
			'currency'	=> $currency,
			'userId'	=> '1',
			'logo'		=> 'absolutepathtologo',
		);

		$invoice_template = $this->load->view('invoices/invoice', $data, true);
		$invoice_style = $this->load->view('invoices/invoice_style', $data, true);

		$facade = PHPPdf\Core\FacadeBuilder::create()->build();
		$pdf = $facade->render($invoice_template, $invoice_style);

		header('Content-Type: application/pdf');
		echo $pdf;
		
	}

}