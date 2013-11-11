<?php

class Migration_add_parameterschecksum_to_snapshots extends CI_Migration {

	public function up(){

		$this->dbforge->add_column('snapshots', [
			'parametersChecksum'	=> [
				'type' 			=> 'VARCHAR',
				'constraint'	=> 32,
			]
		]);

		$this->db->query('CREATE INDEX idx_parametersChecksum ON snapshots(parametersChecksum)');

	}

	public function down(){

		$this->db->query('DROP INDEX idx_parametersChecksum ON snapshots');
		
		$this->dbforge->drop_column('snapshots', 'parametersChecksum');
	
	}

}