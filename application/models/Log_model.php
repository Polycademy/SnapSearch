<?php

class Log_model extends CI_Model{

    protected $errors;

    public function __construct(){

        parent::__construct();

        $this->load->library('form_validation', false, 'validator');

    }

    public function create($input_data){

        $data = elements(array(
            'userId',
            'date',
            'type',
            'url',
            'responseTime',
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
                'label' => 'Date',
                'rules' => 'valid_date',
            ),
            array(
                'field' => 'type',
                'label' => 'Type',
                'rules' => 'required|alpha_numeric',
            ),
            array(
                'field' => 'url',
                'label' => 'URL',
                'rules' => 'required',
            ),
            array(
                'field' => 'responseTime',
                'label' => 'Response Time',
                'rules' => 'required|integer'
            )
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

        if (!isset($data['date']) OR empty($data['date'])) {
            $data['date'] = date('Y-m-d H:i:s');
        }

        //create the canonicalUrl
        $canonicalise = preg_match('~
            # protocol
            ^(?:(?:https?)://)
            # auth
            (?:\S+(?::\S*)?@)?
            # ip or domain
            (
                (?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))
                |
                (?:(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,}))
            )
            # port
            (?::\d{2,5})?
            # resource path
            (?:/\S*)?$
        ~xiu', $data['url'], $matches);

        if ($canonicalise) {
            $data['canonicalUrl'] = $matches[1];
        } else {
            $data['canonicalUrl'] = $data['url'];
        }

        $query = $this->db->insert('log', $data);

        if(!$query){

            $this->errors = array(
                'system_error'  => 'Problem inserting data to log table.',
            );

            return false;

        }

        return $this->db->insert_id();

    }

    public function read_all($offset = false, $limit = false, $user_id = false){

        $offset = ($offset) ? (int) $offset : 0;
        $limit = ($limit) ? (int) $limit : false;

        if($offset < 0){
            $offset = 0;
        }

        if($limit < 0) {
            $limit = 0;
        }

        $this->db->select('*');
        $this->db->from('log');
        $this->db->order_by('date', 'desc');
        if(is_integer($offset) AND is_integer($limit)){
            $this->db->limit($limit, $offset);
        }
        if($user_id){
            $this->db->where('userId', $user_id);
        }

        $query = $this->db->get();

        if($query->num_rows() > 0){

            foreach($query->result() as $row){

                $data[] = array(
                    'id'            => $row->id,
                    'userId'        => $row->userId,
                    'date'          => $row->date,
                    'type'          => $row->type,
                    'url'           => $row->url,
                    'canonicalUrl'  => $row->canonicalUrl,
                    'responseTime'  => $row->responseTime,
                );

            }

            return $data;

        }else{

            $this->errors = array(
                'error' => 'No logs to be found.'
            );
            
            return false;

        }

    }

    /**
     * Reads all logs by cut off date, then grouped by unique days.
     * This will show all the successful requests to the Robot API.
     * There will be cached and uncached requests, but it can be specified
     * by the type parameter. The type parameter can be 'cached' or 'uncached'
     * Useful for time series data!
     * 
     * @return array
     */
    public function read_by_date_group_by_day($user_id, $cut_off_date, $type = null){

        $this->validator->set_data([
            'user'          => $user_id,
            'date'          => $cut_off_date,
            'type'          => $type
        ]);

        $this->validator->set_rules(array(
            array(
                'field' => 'user',
                'label' => 'User ID',
                'rules' => 'required|integer',
            ),
            array(
                'field' => 'date',
                'label' => 'Cut off Date',
                'rules' => 'required|valid_date',
            ),
            array(
                'field' => 'type',
                'label' => 'Type',
                'rules' => 'alpha_numeric',
            )
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

        $cut_off_date = new DateTime($cut_off_date);

        //SELECT FROM_UNIXTIME(ROUND(AVG(UNIX_TIMESTAMP(date)))) as date, COUNT(id) as quantity FROM log WHERE date > $cut_off_date GROUP BY DATE(date) ORDER BY date desc
        $this->db->select('FROM_UNIXTIME(ROUND(AVG(UNIX_TIMESTAMP(date)))) as date, COUNT(id) as quantity', false);
        $this->db->from('log');
        $this->db->where('date >', $cut_off_date->format('Y-m-d H:i:s'));
        $this->db->where('userId', $user_id);
        if($type){
            $this->db->where('type', $type);
        }
        $this->db->group_by('DATE(date)');
        $this->db->order_by('date', 'DESC');

        $query = $this->db->get();

        if($query->num_rows() > 0){

            foreach($query->result() as $row){

                $data[] = array(
                    'date'      => $row->date,
                    'quantity'  => $row->quantity,
                );

            }

            return $data;

        }else{

            $this->errors = array(
                'error' => 'No logs to be found.'
            );
            
            return false;

        }

    }

    public function read_by_domain($user_id, $cut_off_date, $type){

        $this->validator->set_data([
            'user'          => $user_id,
            'date'          => $cut_off_date,
            'type'          => $type
        ]);

        $this->validator->set_rules(array(
            array(
                'field' => 'user',
                'label' => 'User ID',
                'rules' => 'required|integer',
            ),
            array(
                'field' => 'date',
                'label' => 'Cut off Date',
                'rules' => 'required|valid_date',
            ),
            array(
                'field' => 'type',
                'label' => 'Type',
                'rules' => 'alpha_numeric',
            )
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

        $cut_off_date = new DateTime($cut_off_date);

        // now that we have the canonicalUrl, we're going to just count by canonicalUrl
        // SELECT canonicalUrl AS url, count(1) AS count FROM log WHERE date > "2000-04-14 09:09:09" AND userId = 1 AND type = 'uncached' GROUP BY crc32(canonicalUrl) ORDER BY null;
        $this->db->select('canonicalUrl AS url, COUNT(1) AS count', false);
        $this->db->from('log');
        $this->db->where('date >', $cut_off_date->format('Y-m-d H:i:s'));
        $this->db->where('userId', $user_id);
        if($type){
            $this->db->where('type', $type);
        }
        $this->db->group_by('crc32(canonicalUrl)');
        $this->db->order_by('null', null, false); // needs to be false escaping, otherwise it tries escaping null, which makes MySQL think it's a column

        $query = $this->db->get();

        if($query->num_rows() > 0){

            foreach($query->result() as $row) {
                $data[$row->url] = $row->count;
            }

            return $data;

        }else{

            $this->errors = array(
                'error' => 'No logs to be found.'
            );
            
            return false;

        }

    }

    public function get_errors(){

        return $this->errors;

    }

}