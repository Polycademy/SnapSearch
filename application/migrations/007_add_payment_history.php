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
			'date'	=> [
				'type'	=> 'DATETIME'
			],
			'amount'	=> [
				'type'			=> 'VARCHAR',
				'constraint'	=> '40'
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