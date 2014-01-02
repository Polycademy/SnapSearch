<?php

//add the billing stuff here too!
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

		$this->load->model('Accounts_model');
		$this->load->model('Usage_model');
		$this->load->model('Billing_model');
		$this->load->model('Pin_model');

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
						'description'	=> 'SnapSearch API Usage',
						'amount'		=> $charge,
						'ipAddress'		=> $user['ipAddress'],
						'currency'		=> $currency,
						'customerToken'	=> $customer_token,
					]);

					if(!$charge_query){

						//failed to charge
						//put failed charge into left over charge
						//send billing failure email
						//update the billing details to make the card inactive and also invalid, add invalid reason from the errors
						//copy the apiLimit into the apiPreviousLimit and make the apiLimit the same as apiFreeLimit

						//the validation_errors can be a multitude, they need to be imploded and added as text to the invalidReason

					}else{

						//charge was successful
						//create payment history record (this record requires information about the customer object)
						//this is returned as a successful response
						//read the invoice file
						//send email and attach invoice file

					}

				}

			}

		}

	}

}