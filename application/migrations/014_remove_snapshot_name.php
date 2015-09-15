<?php

class Migration_remove_snapshot_name extends CI_Migration {

    public function up(){

        // removing the snapshot column because we're no longer using it to reference the snapshot cache
        // instead the parameter checksum will be used
        $this->dbforge->drop_column('snapshots', 'snapshot');

    }

    public function down(){

        $this->dbforge->add_column('snapshots', [
            'snapshot'  => [
                'type'          => 'VARCHAR',
                'constraint'    => '200'
            ]
        ]);

    }

}