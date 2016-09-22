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
	 * @param  boolean $allowed_length Allowed length in ISO8601 duration format
	 * @param  boolean $user_id        Purge a specific user's snapshot
	 */
	public function purge_cache($allowed_length = false, $user_id = false){

		$this->load->model('v1/Robot_model');

		$query = $this->Robot_model->purge_cache($allowed_length, $user_id);

		$today = new DateTime;
		$output = $today->format('Y-m-d H:i:s') . ' - ';

		if($query === true){
			$output .= "Purged cache using length: " . (($allowed_length === false) ? "infinity" : $allowed_length);
			if($user_id){
				$output .= " and user id: $user_id";
			}
			$output .= "\n";
			echo $output;
		}else{
			echo $query . "\n";
		}

	}

	public function purge_lockfiles ($allowed_length = false) {

		$path = $this->config->item('lockfiles_path');

		if ($allowed_length === false) {

			$command = "find $path -name '*.lock' -type f -delete -print 2>&1";

		} else {

			// allowed_length is integer in terms of number of days
			$allowed_length = intval($allowed_length);

			$command = "find $path -name '*.lock' -type f -mtime +$allowed_length -delete -print 2>&1";

		}

		exec ($command, $command_output, $exit);

		$today = new DateTime;
		$output = $today->format('Y-m-d H:i:s') . ' - ';

		if ($exit == 0) {
			if (empty($command_output)) {
				$command_output = ['Nothing to Delete'];
			}
			if (!empty($allowed_length)) {
				$output .= "Successfully purged lockfiles older than $allowed_length days: \n" . implode("\n", $command_output) . "\n";
			}else{
				$output .= "Successfully purged all lockfiles: \n" . implode("\n", $command_output) . "\n";
			}
		} else {
			if (empty($command_output)) {
				$command_output = ['No Output'];
			}
			if (!empty($allowed_length)) {
				$output .= "Unsuccessful purging of lockfiles older than $allowed_length: \n" . implode("\n", $command_output)  . "\n";
			} else {
				$output .= "Unsuccessful purging of all lockfiles: \n" . implode("\n", $command_output)  . "\n";
			}
		}

		echo $output;
		
	}

	public function test(){
		
		$this->load->model('Log_model');

		$results = $this->Log_model->read_by_date_group_by_day('2014-03-11 00:00:00');

		var_dump($results);

	}

	public function backup() {
	
		$database = $_ENV['secrets']['database_name'];
		$username = $_ENV['secrets']['database_user'];
		$password = $_ENV['secrets']['database_pass'];

		$command = "mysqldump -u $username -p$password $database | gzip | aws s3 cp - s3://snapsearch/mysql_backup/`date +\%d\%m\%y`.sql.gz --acl authenticated-read --sse --expires `date --date='next year' +\%Y-\%m-\%d` 2>&1";

		exec(
			$command,
			$command_output,
			$exit
		);

		$today = new DateTime;

		if ($exit == 0) {
			echo $today->format('Y-m-d H:i:s') . ' - Successful backup' . "\n";
		} else {
			echo $today->format('Y-m-d H:i:s') . ' - Unsuccessful backup, error: ' . implode("\n", $command_output)  . "\n";
		}

	}

	public function purge_sessions () {

		// This is a total hack. But yea... can't do anything until SnapSearch-II
		// Session files are stored in var/lib/php5/0fea6a13c52b4d47/25368f24b045ca84
		// 0fea6a13c52b4d47/25368f24b045ca84 is created from md5('cache')
		// 'cache' is hardcoded by the tedivm cache library

		$today = new \DateTime;

		$polyauth_config = $this->config->item('polyauth');

		if (!empty($polyauth_config['session_save_path'])) {
			$save_path = $polyauth_config['session_save_path'];
		} else {
			$save_path = session_save_path();
		}

		$save_path = rtrim($save_path, '/') .  "/0fea6a13c52b4d47/25368f24b045ca84/";

		$dir = new \RecursiveDirectoryIterator($save_path, \FilesystemIterator::SKIP_DOTS);

		$file_count = 0;
		$dir_count = 0;

		// recurses on the children before recursing on the directory
		foreach (new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::CHILD_FIRST) as $filename => $file) {

			if ($file->isFile()) {
				include ($filename);
				$expiration_date = new \DateTime();
				$expiration_date->setTimestamp($expiration);
				if ($expiration_date < $today) {
					$file_count++;
					unlink($filename);
				}
			} elseif ($file->isDir()) {
				// will only remove the directory if it has no children
				// returns false when it cannot delete
				if (!$this->contains_children($filename) AND rmdir($filename)) {
					$dir_count++;
				}
			}
		
		}

		echo $today->format('Y-m-d H:i:s') . " - Purged sessions with $file_count files and $dir_count directories.\n";

	}

	protected function contains_children ($dir) {

		// stops when it meets the first file
	    $result = false;

	    if($dh = opendir($dir)) {
	        while(!$result && ($file = readdir($dh)) !== false) {
	            $result = $file !== "." && $file !== "..";
	        }
	        closedir($dh);
	    }
	    return $result;

	}

	/**
	 * This function will be ran from the Cron service. Make sure this is done well and no bugs!
	 */

	public function monthly_tracking () {

		$charge_per_request = 0.2; // in cents
		$minimum_charge = 500; // in cents
		$currency = 'AUD';
		$product_description = 'SnapSearch API Usage';
		$grace_ending_period = new DateInterval('P7D');
		$grace_retry_period = new DateInterval('P1D');
		$today = new DateTimeImmutable;

		echo $today->format('Y-m-d H:i:s') . " - Started Charge Cycle\n";

		$this->load->helper('date');
		$this->load->model('Accounts_model');
		$this->load->model('Usage_model');
		$this->load->model('Billing_model');
		$this->load->model('Stripe_model');
		$this->load->model('Email_model');
		$this->load->model('Invoices_model');
		$this->load->model('Payments_model');

		//we need to paginate across all the users because loading all the users into memory might be a bad idea
		$offset = 0;
		$limit = 200;
		$total_number_of_users = $this->Accounts_model->count();
		$processed_number_of_users = 0;

		//divide the total number of users by the limit to get the number of iterations
		//round to highest nearest integer so we run the query at least once
		$iterations = ceil($total_number_of_users / $limit);

		echo $today->format('Y-m-d H:i:s') . " - Total users: $total_number_of_users\n";
		echo $today->format('Y-m-d H:i:s') . " - Iterations needed: $iterations\n";

		//if the total_number_of_users is 0, then this never runs
		for($i = 1; $i <= $iterations; $i++){

			$users = $this->Accounts_model->read_all($offset, $limit);

			//increase the offset by the limit
			$offset = $offset + $limit;

			//proceed to check their API usage
			foreach($users as $user){

				//plus one to the number of users being charged
				$processed_number_of_users = $processed_number_of_users + 1;

				// when this returns, we go to the next user
				$this->monthly_tracking_process_user(
					$user,
					$charge_per_request, 
					$minimum_charge, 
					$currency,
					$product_description,
					$grace_ending_period,
					$grace_retry_period,
					$today
				);

			}

		}

		echo $today->format('Y-m-d H:i:s') . " - Processed $processed_number_of_users Users\n";

	}

	protected function monthly_tracking_process_user (
		$user,
		$charge_per_request,
		$minimum_charge, 
		$currency,
		$product_description,
		$grace_ending_period,
		$grace_retry_period,
		$today
	) {

		$charge_date = new DateTimeImmutable($user['chargeDate']);

		$grace_ending_date = (is_null($user['graceEndingDate'])) ? NULL : new DateTimeImmutable($user['graceEndingDate']);

		$grace_retry_date = (is_null($user['graceRetryDate'])) ? NULL : new DateTimeImmutable($user['graceRetryDate']);

		$grace_period = (is_null($grace_ending_date) OR is_null($grace_retry_date)) ? false : true; 

		if (!$grace_period) {

			// grace is inactive

			if ($charge_date <= $today) {

				$action = 'charge';

			} elseif ($charge_date > $today) {

				// not due for a charge cycling
				return;

			}

		} elseif ($grace_period) {

			// grace is active

			if ($charge_date <= $today AND $charge_date < $grace_retry_date) {

				$action = 'charge';

			} elseif ($charge_date > $today AND $grace_retry_date > $today) {

				// not due for a charge cycling
				return;

			} elseif ($grace_retry_date <= $today AND $grace_retry_date <= $charge_date) {

				$action = 'grace';

			}

		}

		if ($action == 'charge') {

			// there are 2 situations in which a charge will occur: 
			// when the apiUsage - apiFreeLimit > 0 or 
			// when there is apiLeftOverCharge

			echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Charging the User\n";

			// if the apiUsage is less than the apiFreeLimit, then the charged usage is 0
			$usage = max($user['apiUsage'] - $user['apiFreeLimit'], 0);
			// total usage is the total charged usage (subtracting the apiFreeLimit)
			$total_usage = $usage + $user['apiLeftOverUsage'];

			// charge should be calculated from the total_usage
			$charge = (int) round($total_usage * $charge_per_request);

			// track usage statistics
			$this->Usage_model->create([
				'userId'	=> $user['id'],
				'date'		=> $today->format('Y-m-d H:i:s'),
				'usage'		=> $user['apiUsage'],
				'requests'	=> $user['apiRequests'],
			]);

			$charge_interval = new DateInterval($user['chargeInterval']);
			$next_charge_date = $charge_date->add($charge_interval);

			// clear all stateful billing parameters
			// set chargeDate to the next chargeDate
			// optimistic billing, assuming this billing succeeds
			// if the billing fails for any reason
			// there is a rollback transaction readding in the values
			// this is done because if any errors occur later
			// then we will still have moved the user to the next 
			// charge date
			// preferably this should be in a finally clause because
			// this should actually be the final thing to do
			$this->Accounts_model->update($user['id'], [
				'apiUsage'				=> 0,
				'apiRequests'			=> 0,
				'apiLeftOverUsage'		=> 0,
				'apiLeftOverCharge'		=> 0,
				'apiUsageNotification'	=> 0,
				'chargeDate'		    => $next_charge_date->format('Y-m-d H:i:s'),
			]);

			$total_usage_with_free_usage = $user['apiUsage'] + $user['apiLeftOverUsage'];
			echo $today->format('Y-m-d H:i:s') . 
				" - User: #{$user['id']} {$user['email']} " . 
				"Current Requests: {$user['apiRequests']} " . 
				"Current Usage: {$user['apiUsage']} " . 
				"Total Usage: $total_usage_with_free_usage " . 
				"Total Charged Usage: $total_usage " . 
				"Charge: $charge\n";

			// handle minimum charge or where the charge is 0
			if ($charge < $minimum_charge) {

				// under the minimum charge, just accumulate and skip

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Rollover Charge or Charge is Zero\n";

				$this->Accounts_model->update($user['id'], [
					'apiLeftOverUsage'	=> $total_usage,
					'apiLeftOverCharge'	=> $charge,
				]);

				return;

			}

			// handle invalid or missing billing source
			// get the user's active and not invalid credit card
			$billing_record = $this->Billing_model->read_all($user['id'], true, true);
			if (!$billing_record) {

				// billing source is invalid or missing

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} No active billing record\n";

				// terminate service
				// and clear any grace period
				$this->Accounts_model->update($user['id'], [
					'apiLeftOverUsage'	=> $total_usage,
					'apiLeftOverCharge'	=> $charge,
					'apiLimit'			=> $user['apiFreeLimit'], 
					'graceEndingDate'   => null,
					'graceRetryDate'    => null,
				]);

				$email = $this->Email_model->prepare_email('email/billing_error_source_email', [
					'month'			=> $today->format('F'),
					'year'			=> $today->format('Y'),
					'username'		=> $user['username'],
					'charge_error'	=> 'No active and valid credit cards were found in the billing records.',
				]);

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Sending billing error email\n";

				$this->Email_model->send_email(
					'enquiry@snapsearch.io',
					[$user['email'], 'enquiry@snapsearch.io'],
					'SnapSearch Invalid or Missing Card Error for ' . $today->format('F') . ' ' . $today->format('Y'),
					$email
				);

				// next user
				return;

			}

			// handle charge

			$billing_record = $billing_record[0];
			$customer_token = $billing_record['customerToken'];
			$charge_query = $this->Stripe_model->charge_customer($customer_token, 
				[
					'amount'      => $charge,
					'currency'    => $currency, 
					'description' => $product_description,
				]
			);

			if (!$charge_query) {

				// unsuccessful charge

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Charge Invalid\n";

				$charge_errors = $this->Stripe_model->get_errors();
				$charge_error_message = '';
				if(isset($charge_errors['validation_error'])){
					$charge_error_message .= implode(' | ', $charge_errors['validation_error']);
				}elseif(isset($charge_errors['system_error'])){
					$charge_error_message .= $charge_errors['system_error'];
				}

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Saving left over usage and charge\n";

				// store the left over charge, but don't terminate service yet
				$query = $this->Accounts_model->update($user['id'], [
					'apiLeftOverUsage'	=> $total_usage,
					'apiLeftOverCharge'	=> $charge,
				]);

				// we also don't invalidate the payment source

				$grace_retry_period_human_readable = interval_to_human($grace_retry_period);

				if (!$grace_period) {

					// setup a new grace period
					$next_grace_ending_date = $today->add($grace_ending_period);
					$next_grace_retry_date = $today->add($grace_retry_period);

					$this->Accounts_model->update($user['id'], [
						'graceEndingDate'	=> $next_grace_ending_date->format('Y-m-d H:i:s'),
						'graceRetryDate'	=> $next_grace_retry_date->format('Y-m-d H:i:s'),
					]);

					$email = $this->Email_model->prepare_email('email/billing_error_new_grace_email', [
						'month'								=> $today->format('F'),
						'year'								=> $today->format('Y'),
						'username'							=> $user['username'],
						'charge_error'	      				=> $charge_error_message, 
						'grace_ending_date' 				=> $next_grace_ending_date->format('Y-m-d H:i:s'),
						'grace_retry_period_human_readable' => $grace_retry_period_human_readable,
					]);

					echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Sending billing error email with new grace\n";

					$this->Email_model->send_email(
						'enquiry@snapsearch.io',
						[$user['email'], 'enquiry@snapsearch.io'],
						'SnapSearch First Billing Error for ' . $today->format('F') . ' ' . $today->format('Y'),
						$email
					);

				} elseif ($grace_period) {

					// continue with existing grace period
					
					$email = $this->Email_model->prepare_email('email/billing_error_continuing_grace_email', 
						[
							'month'								=> $today->format('F'),
							'year'								=> $today->format('Y'),
							'username'							=> $user['username'],
							'charge_error'	      				=> $charge_error_message, 
							'grace_ending_date' 				=> $grace_ending_date->format('Y-m-d H:i:s'),
							'grace_retry_period_human_readable' => $grace_retry_period_human_readable,
						]
					);

					echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Sending billing error email with continuing grace\n";

					$this->Email_model->send_email(
						'enquiry@snapsearch.io',
						[$user['email'], 'enquiry@snapsearch.io'],
						'SnapSearch Continuing Billing Error for ' . $today->format('F') . ' ' . $today->format('Y'),
						$email
					);

				}

				return;

			}

			// handle successful charge

			echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Charge Successful\n";

			// create invoice with details from the charge object
			// the charge object details come from stripe

			$payment_history = [
				'userId'		=> $user['id'],
				'chargeToken'	=> $charge_query->id,
				'item'			=> $product_description,
				'usageRate'		=> $total_usage,
				'amount'		=> $charge,
				'currency'		=> $currency,
				'email'			=> $user['email'],
			];

			$payment_date = new DateTimeImmutable('@' . $charge_query->created);
			$payment_history['date'] = $payment_date->format('Y-m-d H:i:s');

			if (!empty($charge_query->source->country)) {
				$payment_history['country'] = $charge_query->source->country;
			}

			$address = [];
			if (!empty($charge_query->source->address_line1)) $address[] = $charge_query->source->address_line1;
			if (!empty($charge_query->source->address_line2)) $address[] = $charge_query->source->address_line2;
			if (!empty($charge_query->source->address_city)) $address[] = $charge_query->source->address_city;
			if (!empty($charge_query->source->address_state)) $address[] = $charge_query->source->address_state;
			if (!empty($address)) {
				$payment_history['address'] = implode(' ', $address);	
			}
			
			echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Created invoice\n";

			// should return ['invoiceNumber', 'invoiceFile']
			$invoice_data = $this->Invoices_model->create($payment_history, true);

			// if invoice data didn't get saved, we just set the invoice_number to be a error message, and we don't send an invoice attachment, however we still save the payment history, and this is important!
			if ($invoice_data) {

				$invoice_number = $invoice_data['invoiceNumber'];
				$invoice_file = $invoice_data['invoiceFile'];

			} else {

				$invoice_number = "Invoice Creation Failed";
				$invoice_file = false;

			}

			// store a record to the invoice number in the payment history
			$payment_history['invoiceNumber'] = $invoice_number;

			echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Created payment record\n";

			$this->Payments_model->create($payment_history);

			$email = $this->Email_model->prepare_email('email/invoice_email', [
				'month'			=> $today->format('F'),
				'year'			=> $today->format('Y'),
				'username'		=> $user['username'],
			]);

			echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Sending invoice email\n";

			if ($invoice_file) {

				//send the email with the invoice attached
				$this->Email_model->send_email(
					'enquiry@snapsearch.io',
					[$user['email'], 'enquiry@snapsearch.io'],
					'SnapSearch Monthly Invoice for ' 
						. $today->format('F') . ' ' 
						. $today->format('Y'),
					$email,
					null,
					[
						'SnapSearch Invoice for ' . $today->format('F') . ' ' . $today->format('Y') . '.pdf'	=> $invoice_file
					]
				);

			} else {

				//send the email with no invoice attached
				$this->Email_model->send_email(
					'enquiry@snapsearch.io',
					[$user['email'], 'enquiry@snapsearch.io'],
					'SnapSearch Monthly Invoice for ' . $today->format('F') . ' ' . $today->format('Y'),
					$email
				);

			}

			return;

		} elseif ($action == 'grace') {

			echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Retrying in the Grace Period for the User\n";

			// charge is only derived from the api left over usage
			$total_usage = $user['apiLeftOverUsage'];
			$charge = (int) round($total_usage * $charge_per_request);

			echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Retrying with charge: $charge\n";

			// handle minimum charge for grace
			if ($charge < $minimum_charge) {

				// this can only happen if the minimum_charge changed 
				// or that the left over charge was mutated in between 
				// a failed charge and a grace retry

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Rollover Charge for Grace Action\n";

				$this->Accounts_model->update($user['id'], [
					'graceEndingDate' => null,
					'graceRetryDate'  => null,
				]);

				return;

			}

			// handle invalid or missing billing source

			// get the user's active and not invalid credit card
			$billing_record = $this->Billing_model->read_all($user['id'], true, true);
			
			if (!$billing_record) {

				// billing source is invalid or missing

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} No active billing record for grace retry\n";

				// terminate service
				// and clear any grace period
				$this->Accounts_model->update($user['id'], [
					'apiLimit'			=> $user['apiFreeLimit'], 
					'graceEndingDate'   => null,
					'graceRetryDate'    => null,
				]);

				$email = $this->Email_model->prepare_email('email/billing_error_source_email', [
					'month'			=> $today->format('F'),
					'year'			=> $today->format('Y'),
					'username'		=> $user['username'],
					'charge_error'	=> 'No active and valid credit cards were found in the billing records for the purposes of a grace retry.',
				]);

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Sending billing error email\n";

				$this->Email_model->send_email(
					'enquiry@snapsearch.io',
					[$user['email'], 'enquiry@snapsearch.io'],
					'SnapSearch Invalid or Missing Card Error for ' . $today->format('F') . ' ' . $today->format('Y'),
					$email
				);

				// next user
				return;

			}

			// handle charge

			$billing_record = $billing_record[0];
			$customer_token = $billing_record['customerToken'];
			$charge_query = $this->Stripe_model->charge_customer($customer_token, 
				[
					'amount'      => $charge,
					'currency'    => $currency, 
					'description' => $product_description,
				]
			);

			if (!$charge_query) {

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Grace charge invalid\n";

				$charge_errors = $this->Stripe_model->get_errors();
				$charge_error_message = '';
				if(isset($charge_errors['validation_error'])){
					$charge_error_message .= implode(' | ', $charge_errors['validation_error']);
				}elseif(isset($charge_errors['system_error'])){
					$charge_error_message .= $charge_errors['system_error'];
				}

				// is this the final grace retry?

				if ($grace_retry_date >= $grace_ending_date) {

					echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Last grace retry failed!\n";

					// last grace retry
					// terminating service and grace period
					$this->Accounts_model->update($user['id'], [
						'apiLimit'			=> $user['apiFreeLimit'], 
						'graceEndingDate'   => null,
						'graceRetryDate'    => null,
					]);

					echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Terminated service!\n";

					// make the card inactive on the last failed grace retry
					$this->Billing_model->update($billing_record['id'], [
						'active'	  		=> false,
						'cardInvalid' 		=> true,
						'cardInvalidReason' => $charge_error_message
					]);

					echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Invalidated card!\n";

					$email = $this->Email_model->prepare_email('email/billing_error_last_grace_email', [
						'month'						=> $today->format('F'),
						'year'						=> $today->format('Y'),
						'username'					=> $user['username'],
						'charge_error'	      		=> $charge_error_message,
					]);

					$this->Email_model->send_email(
						'enquiry@snapsearch.io',
						[$user['email'], 'enquiry@snapsearch.io'],
						'SnapSearch Last Retry Billing Error for ' . $today->format('F') . ' ' . $today->format('Y'),
						$email
					);

				} elseif ($grace_retry_date < $grace_ending_date) {

					// grace retry failed, try again on the next grace retry

					echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Grace retry failed!\n";

					$this->Accounts_model->update($user['id'], [
						'graceRetryDate' => $grace_retry_date->add($grace_retry_period)->format('Y-m-d H:i:s'),
					]);

					$grace_retry_period_human_readable = interval_to_human($grace_retry_period);

					$email = $this->Email_model->prepare_email('email/billing_error_retry_grace_email', [
						'month'								=> $today->format('F'),
						'year'								=> $today->format('Y'),
						'username'							=> $user['username'],
						'charge_error'	      				=> $charge_error_message,
						'grace_ending_date' 				=> $grace_ending_date->format('Y-m-d H:i:s'),
						'grace_retry_period_human_readable' => $grace_retry_period_human_readable,
					]);

					$this->Email_model->send_email(
						'enquiry@snapsearch.io',
						[$user['email'], 'enquiry@snapsearch.io'],
						'SnapSearch Retry Billing Error for ' . $today->format('F') . ' ' . $today->format('Y'),
						$email
					);

				}

				return;

			} else {

				// successfully charged 

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Grace Retry Charge Successful\n";

				$this->Accounts_model->update($user['id'], [
					'apiLeftOverUsage'  => 0,
					'apiLeftOverCharge' => 0,
					'graceEndingDate'	=> null,
					'graceRetryDate'	=> null,
				]);

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Ended grace period\n";

				$payment_history = [
					'userId'		=> $user['id'],
					'chargeToken'	=> $charge_query->id,
					'item'			=> $product_description,
					'usageRate'		=> $total_usage,
					'amount'		=> $charge,
					'currency'		=> $currency,
					'email'			=> $user['email'],
				];

				$payment_date = new DateTimeImmutable('@' . $charge_query->created);
				$payment_history['date'] = $payment_date->format('Y-m-d H:i:s');

				if (!empty($charge_query->source->country)) {
					$payment_history['country'] = $charge_query->source->country;
				}

				$address = [];
				if (!empty($charge_query->source->address_line1)) $address[] = $charge_query->source->address_line1;
				if (!empty($charge_query->source->address_line2)) $address[] = $charge_query->source->address_line2;
				if (!empty($charge_query->source->address_city)) $address[] = $charge_query->source->address_city;
				if (!empty($charge_query->source->address_state)) $address[] = $charge_query->source->address_state;
				if (!empty($address)) {
					$payment_history['address'] = implode(' ', $address);	
				}

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Created invoice\n";

				// should return ['invoiceNumber', 'invoiceFile']
				$invoice_data = $this->Invoices_model->create($payment_history, true);

				if ($invoice_data) {

					$invoice_number = $invoice_data['invoiceNumber'];
					$invoice_file = $invoice_data['invoiceFile'];

				} else {

					$invoice_number = "Invoice Creation Failed";
					$invoice_file = false;

				}

				// store a record to the invoice number in the payment history
				$payment_history['invoiceNumber'] = $invoice_number;

				echo $today->format('Y-m-d H:i:s') . " - User: #{$user['id']} Created payment record\n";

				$this->Payments_model->create($payment_history);

				$email = $this->Email_model->prepare_email('email/invoice_email_ending_grace', [
					'month'			=> $today->format('F'),
					'year'			=> $today->format('Y'),
					'username'		=> $user['username'],
				]);

				if ($invoice_file) {

					//send the email with the invoice attached
					$this->Email_model->send_email(
						'enquiry@snapsearch.io',
						[$user['email'], 'enquiry@snapsearch.io'],
						'SnapSearch Monthly Invoice for ' 
							. $today->format('F') . ' ' 
							. $today->format('Y'),
						$email,
						null,
						[
							'SnapSearch Invoice for ' . $today->format('F') . ' ' . $today->format('Y') . '.pdf'	=> $invoice_file
						]
					);

				} else {

					//send the email with no invoice attached
					$this->Email_model->send_email(
						'enquiry@snapsearch.io',
						[$user['email'], 'enquiry@snapsearch.io'],
						'SnapSearch Monthly Invoice for ' . $today->format('F') . ' ' . $today->format('Y'),
						$email
					);

				}

				return;

			}

		}

	}

}