<?php

class Migration_make_address_and_country_optional extends CI_Migration {

    public function up(){

        $this->db->query('ALTER TABLE payment_history MODIFY address TEXT NULL');
        $this->db->query('ALTER TABLE payment_history MODIFY country TEXT NULL');

    }

    public function down(){

        $this->db->query('ALTER TABLE payment_history MODIFY address TEXT NOT NULL');
        $this->db->query('ALTER TABLE payment_history MODIFY country TEXT NOT NULL');

    }

}