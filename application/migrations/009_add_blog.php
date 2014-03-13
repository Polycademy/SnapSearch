<?php

class Migration_add_blog extends CI_Migration {

    public function up(){
    
        $this->dbforge->add_field('id');
        
        $this->dbforge->add_field(array(
            'title' => array(
                'type'          => 'VARCHAR',
                'constraint'    => 50,
            ),
            'description' => array(
                'type'          => 'TEXT',
            ),
            'authorId' => array(
                'type'          => 'INT',
            ),
            'date' => array(
                'type'          => 'DATETIME'
            )
        ));
        
        $this->dbforge->create_table('blog');

    }

    public function down(){
    
        $this->dbforge->drop_table('blog');
    
    }

}