<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\File;

class Cache_model extends CI_Model {

    protected $filesystem;
    protected $errors;

    public function __construct(){

        parent::__construct();

        $this->filesystem = new Filesystem (new LocalAdapter (FCPATH . '/snapshots/cache', true, 755));

        $this->load->library('form_validation', false, 'validator');

    }

    public function read($id, $user_id = false){

        $this->db->select('userId, parametersChecksum');
        $this->db->from('snapshots');
        $this->db->where('id', $id);
        if($user_id){
            $this->db->where('userId', $user_id);
        }

        $query = $this->db->get();

        if($query->num_rows() > 0){

            $row = $query->row();

            $snapshot_file = new File($row->parametersChecksum, $this->filesystem);

            if($snapshot_file->exists()){

                $snapshot_data = bzdecompress($snapshot_file->getContent());

                $response = json_decode($snapshot_data, true);
                $response['cache'] = true;

                return $response;

            }

        }

        $this->errors = [
            'error' => 'Cannot find snapshot.'
        ];
        
        return false;

    }

    public function delete($id, $user_id = false){

        $this->db->select('userId, parametersChecksum');
        $this->db->from('snapshots');
        $this->db->where('id', $id);
        if($user_id){
            $this->db->where('userId', $user_id);
        }

        $query = $this->db->get();

        if($query->num_rows() > 0){

            //delete it off the database
            if($user_id){
                $this->db->where('userId', $user_id);  
            }
            $this->db->delete('snapshots', ['id' => $id]);

            //delete it off the filesystem
            $row = $query->row();
            $snapshot_file = new File($row->parametersChecksum, $this->filesystem);

            if($snapshot_file->exists()){

                $snapshot_file->delete();
                return true;

            }

        }

        $this->errors = [
            'error' => 'Cannot find snapshot.'
        ];

        return false;

    }

    public function read_all($offset = false, $limit = false, $user_id = false){

        //typecasting is required for when we check if they are integers
        $offset = ($offset) ? (int) $offset : 0;
        $limit = ($limit) ? (int) $limit : false;

        if($offset < 0){
            $offset = 0;
        }

        if($limit < 0) {
            $limit = 0;
        }

        $this->db->select('*');
        $this->db->from('snapshots');
        if($user_id){
            $this->db->where('userId', $user_id);
        }
        if(is_integer($offset) AND is_integer($limit)){
            $this->db->limit($limit, $offset);
        }
        $this->db->order_by('date', 'DESC');

        $query = $this->db->get();

        if($query->num_rows() > 0){

            foreach($query->result() as $row){

                $data[] = [
                    'id'                    => $row->id,
                    'userId'                => $row->userId,
                    'url'                   => $row->url,
                    'date'                  => $row->date,
                    'parametersChecksum'    => $row->parametersChecksum,
                ];

            }

            return $data;

        }else{

            $this->errors = [
                'error' => 'No cached snapshots found.'
            ];

            return false;

        }

    }

    public function count($user_id = false){

        $this->db->select('COUNT(id) as count');
        $this->db->from('snapshots');
        if($user_id){
            $this->db->where('userId', $user_id);
        }

        $query = $this->db->get();

        if($query->num_rows() > 0){

            $row = $query->row();

            $data = $row->count;

            return $data;

        }else{

            $this->errors = [
                'error' => 'Could not count the cached snapshots.'
            ];

            return false;
        
        }

    }

    public function get_errors(){

        return $this->errors;

    }

}