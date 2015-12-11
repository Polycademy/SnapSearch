<?php

class Migration_make_parameterschecksum_unique extends CI_Migration {

    public function up(){

        // clear any previous locks
        $this->db->query('UNLOCK TABLES');

        // lock table, mysql requires locking the aliases as well, how weird
        $this->db->query('LOCK TABLES snapshots WRITE, snapshots s1 WRITE, snapshots s2 READ');

        // deletes duplicates, but keeps the most recent row with the highest id
        $this->db->query(
            'DELETE s1 
             FROM snapshots s1, snapshots s2 
             WHERE s1.id < s2.id 
             AND s1.parametersChecksum = s2.parametersChecksum'
        );

        // replace the index with a unique index
        $this->db->query('DROP INDEX idx_parametersChecksum ON snapshots');
        $this->db->query('CREATE UNIQUE INDEX idx_parametersChecksum ON snapshots(parametersChecksum)');

        // unlock table
        $this->db->query('UNLOCK TABLES');

    }

    public function down(){

        // in the reverse case, we just drop the unique index, and re-add the normal index
        $this->db->query('DROP INDEX idx_parametersChecksum ON snapshots');
        $this->db->query('CREATE INDEX idx_parametersChecksum ON snapshots(parametersChecksum)');

    }

}