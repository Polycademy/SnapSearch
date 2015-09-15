<?php

class Migration_change_parameterschecksum_to_64 extends CI_Migration {

    public function up(){

        $this->dbforge->modify_column('snapshots', [
            'parametersChecksum'    => [
                'type'          => 'VARCHAR',
                'constraint'    => 64,
            ]
        ]);

    }

    public function down(){

        $this->dbforge->modify_column('snapshots', [
            'parametersChecksum'    => [
                'type'          => 'VARCHAR',
                'constraint'    => 32,
            ]
        ]);

    }

}