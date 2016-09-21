<?php

use PHPPdf\Core\FacadeBuilder as PDFBuilder;

use Aws\S3\S3Client;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3 as AwsS3Adapter;
use Gaufrette\File;

/**
 * Invoice model CRUDs the invoice pdf files
 */
class Invoices_model extends CI_Model{

    protected $filesystem;
    protected $errors;

    public function __construct(){

        parent::__construct();

        $this->load->library('form_validation', false, 'validator');

        //amazon s3 for invoices, same bucket as snapshots, and it will create it if it doesn't exist
        $this->filesystem = new Filesystem(
            new AwsS3Adapter(
                S3Client::factory([
                    'key'       => $_ENV['secrets']['s3_api_key'],
                    'secret'    => $_ENV['secrets']['s3_api_secret'],
                ]),
                'snapsearch',
                [
                    'create'    => true
                ]
            )
        );

    }

    public function create($input_data, $return_with_file = false){

        $data = elements(array(
            'userId',
            'date',
            'email',
            'address',
            'country',
            'item',
            'usageRate',
            'currency',
            'amount',
        ), $input_data, null, true);

        $this->validator->set_data($data);

        $this->validator->set_rules(array(
            array(
                'field' => 'userId',
                'label' => 'User ID',
                'rules' => 'required|integer',
            ),
            array(
                'field' => 'date',
                'label' => 'Date of Charge',
                'rules' => 'required|valid_date',
            ),
            array(
                'field' => 'email',
                'label' => 'User Email',
                'rules' => 'required|valid_email',
            ),
            array(
                'field' => 'address',
                'label' => 'Address',
                'rules' => '',
            ),
            array(
                'field' => 'country',
                'label' => 'Country',
                'rules' => '',
            ),
            array(
                'field' => 'item',
                'label' => 'Item Description',
                'rules' => 'required',
            ),
            array(
                'field' => 'usageRate',
                'label' => 'Usage Rate',
                'rules' => 'required|integer'
            ),
            array(
                'field' => 'currency',
                'label' => 'Currency',
                'rules' => 'required|alpha|max_length[3]',
            ),
            array(
                'field' => 'amount',
                'label' => 'Amount in Cents',
                'rules' => 'required|numeric|greater_than[99]',
            ),
        ));

        $validation_errors = [];

        if($this->validator->run() ==  false){
            $validation_errors = array_merge($validation_errors, $this->validator->error_array());
        }

        $this->validator->reset_validation();

        if(!empty($validation_errors)){

            $this->errors = array(
                'validation_error'  => $validation_errors
            );
            return false;

        }

        //get a unique filename first
        do{
            $invoice_number = uniqid();
            if(!$this->filesystem->has($invoice_number)) break;
        }while(true);

        //the invoice template will need this as their invoice ID
        $data['invoiceNumber'] = $invoice_number;

        //calculate tax dollars of 10% inclusive but only in Australia, so it can be the full word or the ISO standard
        if(!empty($data['country']) AND (strtolower($data['country']) == 'australia' OR strtolower($data['country']) == 'au')){
            $data['tax'] = '$' . round(($data['amount'] * 0.1) / 100, 2);
        }else{
            $data['tax'] = '$0';
        } 

        if (empty($data['address'])) {
            $data['address'] = 'Unknown';   
        }

        if (empty($data['country'])) {
            $data['country'] = 'Unknown';
        }

        //convert the cents into dollars
        $data['amount'] = '$' . $data['amount'] / 100;

        //add the logo image of Polycademy (make sure this file actually exists, or there will be a FATAL error)
        $data['logo'] = FCPATH . 'assets/img/polycademy_logo.png';

        //load the invoice templates and styles
        $invoice_template = $this->load->view('invoices/invoice', $data, true);
        $invoice_style = $this->load->view('invoices/invoice_style', false, true);

        //build the pdf
        $pdf_builder = PDFBuilder::create()->build();
        $pdf = $pdf_builder->render($invoice_template, $invoice_style);

        //setup the invoice file and write to the filesystem
        //the name will be the number + the extension
        $invoice_name = $invoice_number . '.pdf';
        $invoice_file = new File($invoice_name, $this->filesystem);
        $s3_query = $invoice_file->setContent($pdf, [
            'ContentType'   => 'application/pdf',
        ]);

        if($s3_query){
            if($return_with_file){
                //in some cases, we need both the invoice_number and pdf, such as for the CRON
                return [
                    'invoiceNumber' => $invoice_number,
                    'invoiceFile'   => $pdf,
                ];
            }else{
                return $invoice_number;
            }
        }else{
            $this->errors = [
                'system_error'  => 'Could not save invoice file.'
            ];
            return false;
        }

    }

    public function read($invoice_number, $user_id = false){

        if($user_id){
            $query = $this->db->get_where('payment_history', ['invoiceFile' => $invoice_number, 'userId' => $user_id]);
            if($query->num_rows() < 1){
                $this->errors = [
                    'error' => 'Not your invoice.'
                ];
                return false;
            }
        }

        $invoice_name = $invoice_number . '.pdf';
        $invoice_file = new File($invoice_name, $this->filesystem);

        if($invoice_file->exists()){
            return $invoice_file->getContent();
        }

        $this->errors = [
            'error' => 'Could not find invoice.'
        ];
        return false;

    }

    /**
     * Updating an invoice requires all of the data during creation, this is because we cannot extract the existing data from the existing PDF. In the future we could look into PDF metadata. Or a new file format.
     */
    public function update($invoice_number, $data, $user_id = false, $return_as_file = false){

        if($user_id){
            $query = $this->db->get_where('payment_history', ['invoiceFile' => $invoice_number, 'userId' => $user_id]);
            if($query->num_rows() < 1){
                $this->errors = [
                    'error' => 'Not your invoice.'
                ];
                return false;
            }
        }

        $data = elements(array(
            'userId',
            'date',
            'email',
            'address',
            'country',
            'item',
            'usageRate',
            'currency',
            'amount',
        ), $input_data, null, true);

        $this->validator->set_data($data);

        $this->validator->set_rules(array(
            array(
                'field' => 'userId',
                'label' => 'User ID',
                'rules' => 'required|integer',
            ),
            array(
                'field' => 'date',
                'label' => 'Date of Charge',
                'rules' => 'required|valid_date',
            ),
            array(
                'field' => 'email',
                'label' => 'User Email',
                'rules' => 'required|valid_email',
            ),
            array(
                'field' => 'address',
                'label' => 'Address',
                'rules' => '',
            ),
            array(
                'field' => 'country',
                'label' => 'Country',
                'rules' => '',
            ),
            array(
                'field' => 'item',
                'label' => 'Item Description',
                'rules' => 'required',
            ),
            array(
                'field' => 'usageRate',
                'label' => 'Usage Rate',
                'rules' => 'required|integer'
            ),
            array(
                'field' => 'currency',
                'label' => 'Currency',
                'rules' => 'required|alpha|max_length[3]',
            ),
            array(
                'field' => 'amount',
                'label' => 'Amount in Cents',
                'rules' => 'required|numeric|greater_than[99]',
            ),
        ));

        $validation_errors = [];

        if($this->validator->run() ==  false){
            $validation_errors = array_merge($validation_errors, $this->validator->error_array());
        }

        $this->validator->reset_validation();

        if(!empty($validation_errors)){

            $this->errors = array(
                'validation_error'  => $validation_errors
            );
            return false;

        }

        $invoice_name = $invoice_number . '.pdf';
        $invoice_file = new File($invoice_name, $this->filesystem);

        if(!$invoice_file->exists()){
            $this->errors = array(
                'error' => 'Cannot find invoice file.'
            );
            return false;
        }

        $data['invoiceNumber'] = $invoice_number;

        //calculate tax dollars of 10% inclusive
        if(!empty($data['country']) AND (strtolower($data['country']) == 'australia' OR strtolower($data['country']) == 'au')){
            $data['tax'] = '$' . round(($data['amount'] * 0.1) / 100, 2);
        }else{
            $data['tax'] = '$0';
        } 

        if (empty($data['address'])) {
            $data['address'] = 'Unknown';   
        }

        if (empty($data['country'])) {
            $data['country'] = 'Unknown';
        }

        //convert the cents into dollars
        $data['amount'] = '$' . $data['amount'] / 100;

        //add the logo image of Polycademy
        $data['logo'] = FCPATH . 'img/polycademy_logo.png';

        //load the invoice templates and styles
        $invoice_template = $this->load->view('invoices/invoice', $data, true);
        $invoice_style = $this->load->view('invoices/invoice_style', false, true);

        //build the pdf
        $pdf_builder = PDFBuilder::create()->build();
        $pdf = $pdf_builder->render($invoice_template, $invoice_style);
        
        $s3_query = $invoice_file->setContent($pdf, [
            'ContentType'   => 'application/pdf',
        ]);

        if($s3_query){
            if($return_as_file){
                return $pdf;
            }else{
                return true;
            }
        }else{
            $this->errors = [
                'system_error'  => 'Could not save invoice file.'
            ];
            return false;
        }

    }

    public function delete($invoice_number, $user_id = false){

        if($user_id){
            $query = $this->db->get_where('payment_history', ['invoiceFile' => $invoice_number, 'userId' => $user_id]);
            if($query->num_rows() < 1){
                $this->errors = [
                    'error' => 'Not your invoice.'
                ];
                return false;
            }
        }

        $invoice_name = $invoice_number . '.pdf';
        $invoice_file = new File($invoice_name, $this->filesystem);

        if($invoice_file->exists()){
            return $invoice_file->delete();
        }

        $this->errors = [
            'error' => 'Could not find invoice.'
        ];
        return false;

    }

    public function get_errors(){

        return $this->errors;

    }

}