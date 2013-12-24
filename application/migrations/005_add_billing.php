<?php

/**
 * Billing Users table represents a list of customer tokens associated with credit cards on the Pin service.
 * There can be multiple customer tokens for each user account.
 * userId is a reference to the user accounts
 * customerToken is the id of the customer object which could be reference to another data source containing the credit card information. This is of course for pin.net
 * active is a boolean indicating that this card is available to be charged, if multiple cards are active, the biller should use the first one
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
			'cardHint'	=> [
				'type'		=> 'INT',
				'unsigned'	=> TRUE,
			],
			'customerToken'	=> [
				'type'		=> 'TEXT',
			],
			'active'	=> [
				'type'			=> 'TINYINT',
				'constraint'	=> '1',
				'unsigned'		=> TRUE,
				'default'		=> 0,
			],
			'cardInvalid'	=> [
				'type'			=> 'TINYINT',
				'constraint'	=> '1',
				'unsigned'		=> TRUE,
				'default'		=> 0,
			],
		]);

		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->create_table('billing', true);

	}

	public function down(){

		$this->dbforge->drop_table('billing_users');

	}

}