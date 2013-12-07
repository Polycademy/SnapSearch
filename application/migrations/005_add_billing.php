<?php

/**
 * Billing Users table represents the data that is necessary to charge a customer.
 * userId is a reference to the user accounts
 * apiLimit is the total limit of how many times the api can be accessed
 * apiFreeLimit is the limit that is subtracted from the apiUsage when you're about the charge the amount
 * apiUsage is the number of usages of the API racked up in the chargeInterval, this number will subtract apiFreeLimit and add apiLeftOverUsage and if the number is positive, this is the number that will be multiplied by the charge amount and be charged to the user via the payment gateway, the charge amount will be specified by the handler
 * apiLeftOverUsage will be number of API usages that were not charged for from the previous chargeInterval due to charge errors or no customer reference number
 * chargeInterval is ISO 8601 duration
 * chargeDate is the next date to be charged for
 * customerToken is the id of the customer object which could be reference to another data source containing the credit card information. This is of course for pin.net
 * cardInvalid is a boolean that tells if the previous charge did not work, however if the next charge works, then this switched back to off
 */
class Migration_add_billing extends CI_Migration {

	public function up(){

		$this->dbforge->add_field([
			'id' => [
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE
			],
			'userId'	=> [
				'type' => 'INT',
				'unsigned' => TRUE,
			],
			'chargeInterval'	=> [
				'type'		=> 'TEXT',
			],
			'chargeDate'	=> [
				'type'		=> 'DATETIME'
			],
			'customerToken'	=> [
				'type'		=> 'TEXT',
			],
			'cardInvalid'	=> [
				'type'			=> 'TINYINT',
				'constraint'	=> '1',
				'unsigned'		=> TRUE,
				'default'		=> 0,
			]
		]);

		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->create_table('billing', true);

		//30 days is more accurate then 1 month
		$user = $this->db->get_where('user_accounts', array('id' => '1'))->row();
		$charge_date = new DateTime($user->createdOn);
		$charge_date->add(new DateInterval('P30D'));

		$default_user = array(
			'id'				=> 1,
			'userId'			=> 1,
			'chargeInterval'	=> 'P30D',
			'chargeDate'		=> $charge_date->format('Y-m-d H:i:s'),
		);

		$this->db->insert('billing', $default_user);

	}

	public function down(){

		$this->dbforge->drop_table('billing_users');

	}

}