<?php

class Migration_add_snapshots extends CI_Migration {

	public function up(){
	
		$this->dbforge->add_field('id');
		
		$this->dbforge->add_field(array(
			'userId' => array(
				'type'			=> 'INT',
			),
			'url' => array(
				'type'			=> 'TEXT',
			),
			'date' => array(
				'type'			=> 'DATETIME'
			),
			'snapshot' => array(
				'type'			=> 'TEXT',
			),
		));
		
		$this->dbforge->create_table('snapshots');

	}

	public function down(){
	
		$this->dbforge->drop_table('snapshots');
	
	}
}