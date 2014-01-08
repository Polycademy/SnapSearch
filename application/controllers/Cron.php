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
				$charge_date = new DateTime($user['chargeDate']);

				if($charge_date > $today){
					//if the charge_date is after today, then we'll skip this user, as this user is not scheduled for a monthly checkup
					//if it was equal or less than today, then we'll use this user, if it is less, then that means we missed a charge
					continue;
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
					'date'		=> $today->format('Y-m-d H:i:s'),
					'usage'		=> $user['apiUsage'],
					'requests'	=> $user['apiRequests'],
				]);

				$charge_interval = new DateInterval($user['chargeInterval']);
				$next_charge_date = $charge_date->add($charge_interval);

				//clear apiUsage, apiRequests and apiLeftOverCharge for this month and add the next month's chargeDate
				$this->Accounts_model->update($user['id'], [
					'apiUsage'			=> 0,
					'apiRequests'		=> 0,
					'apiLeftOverCharge'	=> 0,
					'chargeDate'		=> $next_charge_date->format('Y-m-d H:i:s'),
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
							'month'			=> $today->format('F'),
							'year'			=> $today->format('Y'),
							'username'		=> $user['username'],
							'charge_error'	=> $charge_error_message,
							'user_id'		=> $user['id'],
						]);

						//send the email
						$this->Email_model->send_email([
							'enquiry@polycademy.com',
							[$user['email']],
							'SnapSearch Billing Error for ' . $today->format('F') . ' ' . $today->format('Y'),
							$email,
						]);

						//move on to the next user!

					}else{

						$payment_history = [
							'userId'		=> $user['id'],
							'chargeToken'	=> $charge_query['token'],
							'item'			=> $product_description,
							'usageRate'		=> $user['apiUsage'],
							'amount'		=> $charge,
							'currency'		=> $currency,
							'email'			=> $user['email'],
							'country'		=> $charge_query['card']['address_country'],
						];

						//add the payment date from the charge query's date
						$payment_date = new DateTime($charge_query['created_at']);
						$payment_date = $payment_date->format('Y-m-d H:i:s');
						$payment_history['date'] = $payment_date;

						if(!empty($charge_query['card']['address_line1'])) $address[] = $charge_query['card']['address_line1'];
						if(!empty($charge_query['card']['address_line2'])) $address[] = $charge_query['card']['address_line2'];
						if(!empty($charge_query['card']['address_city'])) $address[] = $charge_query['card']['address_city'];
						if(!empty($charge_query['card']['address_postcode'])) $address[] = $charge_query['card']['address_postcode'];
						if(!empty($charge_query['card']['address_state'])) $address[] = $charge_query['card']['address_state'];

						$payment_history['address'] = implode(' ', $address);

						$payment_id = $this->Payments_model->create($payment_history);

						$invoice_file_location = $this->Payments_model->read($payment_id)['invoiceFilePath'];

						$email = $this->Email_model->prepare_email('emails/invoice_email', [
							'month'			=> $today->format('F'),
							'year'			=> $today->format('Y'),
							'username'		=> $user['username'],
							'user_id'		=> $user['id'],
						]);

						//send the email
						$this->Email_model->send_email([
							'enquiry@polycademy.com',
							[$user['email']],
							'SnapSearch Monthly Invoice for ' . $today->format('F') . ' ' . $today->format('Y'),
							$email,
							[
								'SnapSearch Invoice for ' . $today->format('F') . $today->format('Y') . '.pdf'	=> $invoice_file_location
							]
						]);

					}

				}

			}

		}

	}

}