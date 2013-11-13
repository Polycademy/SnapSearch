<?php

class Migration_change_snapshot_to_be_stored_on_fs extends CI_Migration {

	public function up(){

		$this->dbforge->modify_column('snapshots', [
			'snapshot'	=> [
				'type'			=> 'VARCHAR',
				'constraint'	=> '200' //filename
			]
		]);

	}

	public function down(){

		$this->dbforge->modify_column('snapshots', [
			'snapshot'	=> [
				'type'	=> 'TEXT'
			]
		]);

	}

}