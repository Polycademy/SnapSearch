<?php

class Migration_add_canonicalurl_to_log extends CI_Migration {

    public function up(){

        $this->dbforge->add_column('log', [
            'canonicalUrl'    => [
                'type'          => 'TEXT'
            ]
        ]);

        $this->db->query('CREATE INDEX idx_date ON log(date)');

    }

    public function down(){

        $this->db->query('DROP INDEX idx_date ON log');
        
        $this->dbforge->drop_column('log', 'canonicalUrl');
    
    }

}