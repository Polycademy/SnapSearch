<?php

class Migration_add_payment_history extends CI_Migration {

	public function up(){

		//userId points to the user it relates to
		//invoiceFile is a path location to the invoice file
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
			'chargeToken'	=> [
				'type'	=> 'TEXT'
			],
			'date'	=> [
				'type'	=> 'DATETIME'
			],
			'amount'	=> [ //amount in cents 100 => $1
				'type'		=> 'INT'
				'unsigned'	=> TRUE,
			],
			'currency'	=> [
				'type'			=> 'VARCHAR',
				'constraint'	=> '3'
			],
			'invoiceFile'	=> [
				'type'	=> 'TEXT'
			],
		]);

		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->create_table('payment_history', true);

	}

	public function down(){

		$this->dbforge->drop_table('payment_history');

	}

}