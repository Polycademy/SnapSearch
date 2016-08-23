<?php

use Stripe\Stripe;
use Stripe\Customer;

class Billing_stripe extends CI_Controller{

    protected $authenticator;
    protected $auth_response;
    protected $user;

    public function __construct(){

        parent::__construct();

        $ioc = $this->config->item('ioc');
        $this->authenticator = $ioc['PolyAuth\Authenticator'];
        $this->authenticator->start();

        $this->auth_response = $this->authenticator->get_response();
        $this->user = $this->authenticator->get_user();

        // try both keys
        // sk_test_S8JJMl8pKwNCE9bROBuqtJqu
        Stripe::setApiKey('sk_live_SqF0iGW1P7pB0Jh06rwK1Q0v');

    }

    public function create () {

        $stripe_token = $this->input->post('stripeToken');
        $stripe_email = $this->input->post('stripeEmail');

        $customer = Customer::create([
            "source"      => $stripe_token,
            "description" => $stripe_email
        ]);

        var_dump($stripe_token);
        var_dump($stripe_email);

        var_dump($customer->id);

        /*
            STORE THE customer->id.
            When it comes to charging, it load the customer->id and run it again.
            // Charge the Customer instead of the card
            \Stripe\Charge::create(array(
              "amount" => 1000, // Amount in cents
              "currency" => "aud",
              "customer" => $customer->id)
            );
         */

    }

}