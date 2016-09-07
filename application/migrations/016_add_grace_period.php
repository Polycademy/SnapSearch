<?php

class Migration_add_grace_period extends CI_Migration {

    public function up(){

        $this->dbforge->add_column('user_accounts', [
            'graceEndingDate' => [
                'type' => 'DATETIME', 
                'null' => true, 
                'default' => null
            ],
            'graceRetryDate' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null
            ],
        ]);

    }

    public function down(){
        
        $this->dbforge->drop_column('user_accounts', 'graceEndingDate');
        $this->dbforge->drop_column('user_accounts', 'graceRetryDate');
    
    }

}