<?php

class Migration_add_parameterschecksum_to_snapshots extends CI_Migration {

	public function up(){

		$this->dbforge->add_column('snapshots', [
			'parametersChecksum'	=> [
				'type' 			=> 'VARCHAR',
				'constraint'	=> 32,
			]
		]);

	}

	public function down(){

		$this->dbforge->drop_column('snapshots', 'parameters');
	
	}
}