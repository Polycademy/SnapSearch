<?php

/*
	Todo: The monthly_tracking really needs to be wrapped together in a global transaction.
	But that's too hard to do with the structure of Codeigniter's models.
 */
class Cron extends CI_Controller{

	public function __construct(){
 
		parent::__construct();

		if(!$this->input->is_cli_request()){
			exit;
		}

		//no time limit for Cron processes
		//by default calling PHP from the cli should have max_execution_time of 0, but this is here to be sure
		set_time_limit(0);
 
	}

	/**
	 * Purge (clean) the snapshot cache. If no allowed_length is passed, all of the cache is deleted
	 * @param  boolean $allowed_length Allowed length in ISO8601
	 * @param  boolean $user_id        Cache a specific user's snapshot
	 */
	public function purge_cache($allowed_length = false, $user_id = false){

		$this->load->model('v1/Robot_model');

		$query = $this->Robot_model->purge_cache($allowed_length, $user_id);

		if($query === true){
			echo 'Purged cache using length: ' . intval($allowed_length) . ' and user id: ' . $user_id;
		}else{
			echo $query;
		}

	}

	/**
	 * This function will be ran from the Cron service. Make sure this is done well and no bugs!
	 */
	public function monthly_tracking(){

		//1 cent per request
		$charge_per_request = 1;
		$currency = 'AUD';
		$product_description = 'SnapSearch API Usage';

		$this->load->model('Accounts_model');
		$this->load->model('Usage_model');
		$this->load->model('Billing_model');
		$this->load->model('Pin_model');
		$this->load->model('Email_model');
		$this->load->model('Payments_model');

		//we need to paginate across all the users because loading all the users into memory might be a bad idea
		$offset = 0;
		$limit = 300;
		$total_number_of_users = $this->Accounts_model->count();

		//divid the total number of users by the limit to get the number of iterations
		//round to highest nearest integer so we run the query at least once
		$iterations = ceil($total_number_of_users / $limit);

		//if the total_number_of_users is 0, then this never runs
		for($i = 1; $i <= $iterations; $i++){

			$users = $this->Accounts_model->read_all($offset, $limit);
			//increase the offset by the limit
			$offset = $offset + $limit;

			//proceed to check their API usage
			foreach($users as $user){

				//first determine if the $user is currently scheduled for a monthly checkup
				$today = new DateTime;
				$beginning_date = new DateTime($user['createdOn']);
				$charge_interval = new DateInterval($user['chargeInterval']);

				while(true){

					$date_to_check = $beginning_date->add($charge_interval);

					if($date_to_check < $today){
						//if the date_to_check is before today, we'll keep working with the loop
						continue;
					}elseif($date_to_check > $today){
						//if the date_to_check is after today, then we'll skip this user, as this user is not scheduled for a monthly checkup
						continue 2;
					}elseif($date_to_check->format('Y-m-d') == $today->format('Y-m-d')){
						//if the date_to_check is today, then we'll break and use this user
						break;
					}

				}

				//there are 2 situations in which a charge will occur, when the apiUsage - apiFreeLimit > 0 or when there is apiLeftOverCharge

				$usage = $user['apiUsage'] - $user['apiFreeLimit'];

				$charge = 0;
				if($usage > 0){
					//usage is the number of API requests to be charged for
					$charge = $usage * $charge_per_request;
				}

				if($user['apiLeftOverCharge'] > 0){
					//add on the previous left over charge if there was any
					$charge += $user['apiLeftOverCharge']; 
				}

				//track usage statistics
				$this->Usage_model->create([
					'userId'	=> $user['id'],
					'date'		=> date('Y-m-d H:i:s'),
					'usage'		=> $user['apiUsage'],
					'requests'	=> $user['apiRequests'],
				]);

				//clear apiUsage, apiRequests and apiLeftOverCharge for next month
				$this->Accounts_model->update($user['id'], [
					'apiUsage'			=> 0,
					'apiRequests'		=> 0,
					'apiLeftOverCharge'	=> 0,
				]);

				if($charge){
					//get the user's first active billing details, remember there should only be one
					//for each user, and the active one should not be invalid
					$billing_record = $this->Billing_model->read_all($user['id'], true)[0];
					
					$customer_token = $billing_record['customerToken'];

					//prepare the charge the customer
					$charge_query = $this->Pin_model->charge_customer([
						'email'			=> $user['email'],
						'description'	=> $product_description,
						'amount'		=> $charge,
						'ipAddress'		=> $user['ipAddress'],
						'currency'		=> $currency,
						'customerToken'	=> $customer_token,
					]);

					if(!$charge_query){

						//charge was unsuccessful

						//retrieve the errors, there could be system or validation errors
						$charge_errors = $this->Pin_model->get_errors();
						$charge_error_message = '';
						if(isset($charge_errors['validation_error'])){
							$charge_error_message .= implode(' | ', $charge_errors['validation_error']);
						}elseif(isset($charge_errors['system_error'])){
							$charge_error_message .= $charge_errors['system_error'];
						}

						//update the account with the left over charge
						//apiLimit gets reset to apiFreeLimit while also being copied into apiPreviousLimit
						$this->Accounts_model->update($user['id'], [
							'apiLeftOverCharge'	=> $charge,
							'apiLimit'			=> $user['apiFreeLimit'],
							'apiPreviousLimit'	=> $user['apiLimit'],
						]);

						//update the billing details in order to make the current customer object invalid
						$this->Billing_model->update($billing_record['id'], [
							'active'			=> 0,
							'cardInvalid'		=> 1,
							'cardInvalidReason'	=> $charge_error_message,							
						]);

						//prepare the billing error email
						$email = $this->Email_model->prepare_email('emails/billing_error_email', [
							'month'			=> date('F'),
							'year'			=> date('Y'),
							'username'		=> $user['username'],
							'charge_error'	=> $charge_error_message,
							'user_id'		=> $user['id'],
						]);

						//send the email
						$this->Email_model->send_email([
							'enquiry@polycademy.com',
							[$user['email']],
							'SnapSearch Billing Error for ' . date('F') . ' ' . date('Y'),
							$email,
						]);

						//move on to the next user!

					}else{

						$payment_history = [
							'userId'		=> $user['id'],
							'chargeToken'	=> $charge_query['token'],
							'date'			=> (new DateTime($charge_query['created_at']))->format('Y-m-d H:i:s'),
							'item'			=> $product_description,
							'usageRate'		=> $user['apiUsage'],
							'amount'		=> $charge,
							'currency'		=> $currency,
							'email'			=> $user['email'],
							'country'		=> $charge_query['card']['address_country'],
						];

						if(!empty($charge_query['card']['address_line1'])) $address[] $charge_query['card']['address_line1'];
						if(!empty($charge_query['card']['address_line2'])) $address[] $charge_query['card']['address_line2'];
						if(!empty($charge_query['card']['address_city'])) $address[] $charge_query['card']['address_city'];
						if(!empty($charge_query['card']['address_postcode'])) $address[] $charge_query['card']['address_postcode'];
						if(!empty($charge_query['card']['address_state'])) $address[] $charge_query['card']['address_state'];

						$payment_history['address'] = implode(' ', $address);

						$payment_id = $this->Payments_model->create($payment_history);

						$invoice_file_location = $this->Payments_model->read($payment_id)['invoiceFilePath'];

						$email = $this->Email_model->prepare_email('emails/invoice_email', [
							'month'			=> date('F'),
							'year'			=> date('Y'),
							'username'		=> $user['username'],
							'user_id'		=> $user['id'],
						]);

						//send the email
						$this->Email_model->send_email([
							'enquiry@polycademy.com',
							[$user['email']],
							'SnapSearch Monthly Invoice for ' . date('F') . ' ' . date('Y'),
							$email,
							[$invoice_file_location]
						]);

					}

				}

			}

		}

	}

}