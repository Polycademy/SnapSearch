<?php

class Migration_change_invoice_file_to_invoice_number extends CI_Migration {

    public function up(){

        $this->dbforge->modify_column('payment_history', [
            'invoiceFile'  => [
                'name'  => 'invoiceNumber',
                'type'  => 'TEXT',
                'null'  => false,
            ]
        ]);

    }

    public function down(){

        $this->dbforge->modify_column('payment_history', [
            'invoiceNumber'  => [
                'name'  => 'invoiceFile',
                'type'  => 'TEXT',
                'null'  => 'false',
            ]
        ]);

    }

}