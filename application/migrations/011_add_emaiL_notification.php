<?php

class Migration_add_email_notification extends CI_Migration {

    public function up(){

        $this->dbforge->add_column('user_accounts', [
            'apiUsageNotification'    => [
                'type'          => 'TINYINT',
                'constraint'    => '1',
                'unsigned'      => TRUE,
                'default'       => 0,
            ]
        ]);

    }

    public function down(){
        
        $this->dbforge->drop_column('user_accounts', 'apiUsageNotification');
    
    }

}