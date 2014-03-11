<?php

class Migration_add_log extends CI_Migration{

    public function up(){

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'userId'    => [
                'type' => 'INT',
                'unsigned' => TRUE,
            ],
            'date'  => [
                'type'  => 'DATETIME',
            ],
            'type'  => [
                'type'          => 'VARCHAR',
                'constraint'    => '30'
            ],
            'url'   => [
                'type'  => 'TEXT',
            ],
            'responseTime'  => [
                'type'  => 'INT',
                'unsigned'  => TRUE,
            ]
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('log', true);

    }

    public function down(){

        $this->dbforge->drop_table('log');

    }

}