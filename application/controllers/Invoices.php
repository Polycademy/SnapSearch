<?php

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Invoice download controller, it will need to check against authentication, and allow the invoice to be downloaded
 */
class Invoices extends CI_Controller{

    protected $authenticator;
    protected $auth_response;
    protected $user;

    public function __construct(){

        parent::__construct();

        $ioc = $this->config->item('ioc');

        $this->load->model('Invoices_model');

        $this->request = $ioc['Request'];

        $this->authenticator = $ioc['PolyAuth\Authenticator'];
        $this->authenticator->start();

        $this->auth_response = $this->authenticator->get_response();
        $this->user = $this->authenticator->get_user();

    }

    public function show($id){

        if(!$this->user->authorized()){

            $this->auth_response->setStatusCode(401);
            $content = 'Not authorized to use SnapSearch.';
            $code = 'error';

        }else{

            if($this->user->authorized([
                'roles' => 'admin'
            ])){

                $query = $this->Invoices_model->read($id);

            }else{

                $query = $this->Invoices_model->read($id, $this->user['id']);
            
            }

            if($query){
                
                $disposition = $this->auth_response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'invoice.pdf'
                );

                $this->auth_response->headers->set('Content-Type', 'application/pdf');
                $this->auth_response->headers->set('Content-Disposition', $disposition);

                $content = $query;
                $code = 'success';
            
            }else{

                $this->auth_response->setStatusCode(404);
                $content = current($this->Invoices_model->get_errors());
                $code = key($this->Invoices_model->get_errors());
            
            }

        }

        $this->auth_response->sendHeaders();

        if($code == 'success'){

            echo $content;

        }else{

            $output = array(
                'content'   => $content,
                'code'      => $code,
            );
            
            Template::compose(false, $output, 'json');

        }

    }

}