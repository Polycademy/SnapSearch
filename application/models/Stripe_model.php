<?php

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;

/**
 * Stripe Model contacts the Stripe service API to CRUD customers.
 */
class Stripe_model extends CI_Model{

    protected $errors;

    public function __construct(){

        parent::__construct();
        $this->load->library('form_validation', false, 'validator');
        $api_key = $_ENV['secrets']['stripe_api_key'];
        Stripe::setApiKey($api_key);

    }

    public function test () {

        $api_key = $_ENV['secrets']['stripe_api_test_key'];
        Stripe::setApiKey($api_key);

    }

    /**
     * Stripe handles all user data, and gives back a token and email of the user.
     * The token represents a "new charge/customer request".
     * Here we confirm the request and convert it into a real customer.
     */
    public function create_customer($input_data){
        
        $data = elements(array(
            'stripeToken',
            'stripeEmail',
        ), $input_data, null, true);

        try {

            // stripe will validate these parameters themselves
            $customer = Customer::create([
                "source"      => $data['stripeToken'],
                "description" => $data['stripeEmail']
            ]);

            return array(
                'customerToken' => $customer->id,
                'customerEmail' => $data['stripeEmail'],
                'cardNumber'    => $customer->sources->data[0]->last4,
            );

        } catch (\Stripe\Error\RateLimit $e) {
        
            // Too many requests made to the API too quickly
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Too many requests to Stripe server, it responded with a ' . $response_status,
            );
            return false;

        } catch (\Stripe\Error\InvalidRequest $e) {

            // Invalid stripe parameters
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Invalid request parameters to Stripe server, it responded with a ' . $response_status,
            );
            return false;
            
        } catch (\Stripe\Error\Authentication $e) {
            
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'SnapSearch could not authenticate with Stripe, please contact the administrators. Stripe responded with ' . $response_status
            );
            return false;
        
        } catch (\Stripe\Error\ApiConnection $e) {
            
            $this->errors = array(
                'system_error' => 'SnapSearch could not connect to Stripe. Please try again later.'
            );
            return false;
        
        } catch (\Stripe\Error\Base $e) {
        
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later. Stripe responded with ' . $response_status,
            );
            return false;
            
        } catch (Exception $e) {
        
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later.',
            );
            return false;
        
        }

    }

    public function read_customer ($customer_id) {

        try {

            $customer = Customer::retrieve($customer_id);

            return array(
                'customerId'      => $customer_id,
                'customerEmail'   => $customer->email,
                'cardNumber'      => $customer->sources->data[0]->last4,
                'customerCreated' => $customer->created
            );

        } catch (\Stripe\Error\RateLimit $e) {
        
            // Too many requests made to the API too quickly
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Too many requests to Stripe server, it responded with a ' . $response_status,
            );
            return false;

        } catch (\Stripe\Error\InvalidRequest $e) {

            // Invalid stripe parameters
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Invalid request parameters to Stripe server, it responded with a ' . $response_status,
            );
            return false;
            
        } catch (\Stripe\Error\Authentication $e) {
            
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'SnapSearch could not authenticate with Stripe, please contact the administrators. Stripe responded with ' . $response_status
            );
            return false;
        
        } catch (\Stripe\Error\ApiConnection $e) {
            
            $this->errors = array(
                'system_error' => 'SnapSearch could not connect to Stripe. Please try again later.'
            );
            return false;
        
        } catch (\Stripe\Error\Base $e) {
        
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later. Stripe responded with ' . $response_status,
            );
            return false;
            
        } catch (Exception $e) {
        
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later.',
            );
            return false;
        
        }

    }

    public function charge_customer ($customer_id, $input_data) {

        $data = elements(array(
            'amount',
            'currency', 
            'description', 
        ), $input_data, null, true);

        $this->validator->set_data($data);

        $this->validator->set_rules(array(
            array(
                'field' => 'amount',
                'label' => 'Amount',
                'rules' => 'required|numeric|greater_than_equal_to[50]' // for aud, see https://support.stripe.com/questions/what-is-the-minimum-amount-i-can-charge-with-stripe
            ),
            array(
                'field' => 'currency',
                'label' => 'Currency',
                'rules' => 'required|alpha|max_length[3]',
            ),
            array(
                'field' => 'description',
                'label' => 'description',
                'rules' => ''
            )
        ));

        $validation_errors = [];

        if($this->validator->run() ==  false){
            $validation_errors = array_merge($validation_errors, $this->validator->error_array());
        }

        if(!empty($validation_errors)){
            $this->errors = array(
                'validation_error'  => $validation_errors
            );
            return false;
        }

        try {

            $data['customer'] = $customer_id;
            $charge = Charge::create($data);
            return $charge;

        } catch (\Stripe\Error\Card $e) {

            $response_status = $e->getHttpStatus();
            $response_body = $e->getJsonBody();
            $card_error_code = $response_body['error']['code'];
            $card_error_message = $response_body['error']['message'];
            $this->errors = array(
                'validation_error' => [ 
                    'card' => "Error charging your card on Stripe. It responded with status code: $response_status, and message: $card_error_code - $card_error_message" 
                ],
            );
            return false;

        } catch (\Stripe\Error\RateLimit $e) {
        
            // Too many requests made to the API too quickly
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Too many requests to Stripe server, it responded with a ' . $response_status,
            );
            return false;

        } catch (\Stripe\Error\InvalidRequest $e) {

            // Invalid stripe parameters

            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Invalid request parameters to Stripe server, it responded with a ' . $response_status,
            );
            return false;
            
        } catch (\Stripe\Error\Authentication $e) {
            
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'SnapSearch could not authenticate with Stripe, please contact the administrators. Stripe responded with ' . $response_status
            );
            return false;
        
        } catch (\Stripe\Error\ApiConnection $e) {
            
            $this->errors = array(
                'system_error' => 'SnapSearch could not connect to Stripe. Please try again later.'
            );
            return false;
        
        } catch (\Stripe\Error\Base $e) {
        
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later. Stripe responded with ' . $response_status,
            );
            return false;
            
        } catch (Exception $e) {
        
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later.',
            );
            
            return false;

        }

    }

    /**
     * This updates the card details for a given customer.
     */
    public function update_customer ($customer_id, $input_data) {

        $data = elements(array(
            'stripeToken',
            'stripeEmail',
        ), $input_data, null, true);

        try {

            $customer = Customer::retrieve($customer_id);
            $customer->source = $data['stripeToken'];
            $customer->save();

            return array(
                'customerToken' => $customer->id,
                'customerEmail' => $data['stripeEmail'],
                'cardNumber'    => $customer->sources->data[0]->last4,
            );

        }  catch (\Stripe\Error\RateLimit $e) {
        
            // Too many requests made to the API too quickly
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Too many requests to Stripe server, it responded with a ' . $response_status,
            );
            return false;

        } catch (\Stripe\Error\InvalidRequest $e) {

            // Invalid stripe parameters
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Invalid request parameters to Stripe server, it responded with a ' . $response_status,
            );
            return false;
            
        } catch (\Stripe\Error\Authentication $e) {
            
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'SnapSearch could not authenticate with Stripe, please contact the administrators. Stripe responded with ' . $response_status
            );
            return false;
        
        } catch (\Stripe\Error\ApiConnection $e) {
            
            $this->errors = array(
                'system_error' => 'SnapSearch could not connect to Stripe. Please try again later.'
            );
            return false;
        
        } catch (\Stripe\Error\Base $e) {
        
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later. Stripe responded with ' . $response_status,
            );
            return false;
            
        } catch (Exception $e) {
        
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later.',
            );
            return false;
        
        }

    }

    public function delete_customer ($customer_id) {

        try {

            $customer = Customer::retrieve($customer_id);
            $customer->delete();

        }  catch (\Stripe\Error\RateLimit $e) {
        
            // Too many requests made to the API too quickly
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Too many requests to Stripe server, it responded with a ' . $response_status,
            );
            return false;

        } catch (\Stripe\Error\InvalidRequest $e) {

            // Invalid stripe parameters
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Invalid request parameters to Stripe server, it responded with a ' . $response_status,
            );
            return false;
            
        } catch (\Stripe\Error\Authentication $e) {
            
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'SnapSearch could not authenticate with Stripe, please contact the administrators. Stripe responded with ' . $response_status
            );
            return false;
        
        } catch (\Stripe\Error\ApiConnection $e) {
            
            $this->errors = array(
                'system_error' => 'SnapSearch could not connect to Stripe. Please try again later.'
            );
            return false;
        
        } catch (\Stripe\Error\Base $e) {
        
            $response_status = $e->getHttpStatus();
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later. Stripe responded with ' . $response_status,
            );
            return false;
            
        } catch (Exception $e) {
        
            $this->errors = array(
                'system_error' => 'Unknown error when attempting to create customer in Stripe. Please try again later.',
            );
            return false;
        
        }

    }

    public function get_errors(){

        return $this->errors;

    }

}