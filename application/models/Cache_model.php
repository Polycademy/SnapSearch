<?php

class Cache_model extends CI_Model{

    protected $errors;

    public function __construct(){

        parent::__construct();

        $this->load->library('form_validation', false, 'validator');

    }

    public function read_all(){

    }

    public function get_errors(){

        return $this->errors;

    }

}