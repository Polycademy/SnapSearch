<?php

/**
 * Usage Model manages the usage history of the API from a user.
 * It's not used to calculate charges, but to allow the user to see how they are using the API.
 */
class Usage_model extends CI_Model{

	protected $errors;

	public function __construct(){

		parent::__construct();

		$this->load->library('form_validation', false, 'validator');

	}

	public function create($input_data){

		$data = elements(array(
			'userId',
			'date',
			'usage',
			'requests',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'date',
				'label'	=> 'Date',
				'rules'	=> 'required|valid_date',
			),
			array(
				'field'	=> 'usage',
				'label'	=> 'Usage',
				'rules'	=> 'required|integer',
			),
			array(
				'field'	=> 'requests',
				'label'	=> 'Requests',
				'rules'	=> 'required|integer',
			)
		));

		$validation_errors = [];

		if($this->validator->run() ==  false){
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		$this->validator->reset_validation();

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

		$query = $this->db->insert('usage_rates', $data);

		if(!$query){

			$this->errors = array(
				'system_error'	=> 'Problem inserting data to usage rates table.',
			);

			return false;

		}

		return $this->db->insert_id();

	}

	public function read_all($offset = false, $limit = false, $user_id = false){

		$offset = ($offset) ? (int) $offset : 0;
		$limit = ($limit) ? (int) $limit : false;

		$this->db->select('*');
		$this->db->from('usage_rates');
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
					'id'		=> $row->id,
					'userId'	=> $row->userId,
					'date'		=> $row->date,
					'usage'		=> $row->usage,
					'requests'	=> $row->requests,
				);

			}

			return $data;

		}else{

			$this->errors = array(
				'error' => 'No usage rates to be found.'
			);
			
			return false;

		}

	}

	public function update($id, $input_data){

		$data = elements(array(
			'userId',
			'date',
			'usage',
			'requests',
		), $input_data, null, true);

		$this->validator->set_data($data);

		$this->validator->set_rules(array(
			array(
				'field'	=> 'userId',
				'label'	=> 'User ID',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'date',
				'label'	=> 'Date',
				'rules'	=> 'valid_date',
			),
			array(
				'field'	=> 'usage',
				'label'	=> 'Usage',
				'rules'	=> 'integer',
			),
			array(
				'field'	=> 'requests',
				'label'	=> 'Requests',
				'rules'	=> 'integer',
			)
		));

		$validation_errors = [];

		if($this->validator->run() ==  false){
			$validation_errors = array_merge($validation_errors, $this->validator->error_array());
		}

		$this->validator->reset_validation();

		if(!empty($validation_errors)){

			$this->errors = array(
				'validation_error'	=> $validation_errors
			);
			return false;

		}

		$this->db->update('usage_rates', $data, array('id' => $id));

		if($this->db->affected_rows() > 0){

			return true;
		
		}else{
			
			$this->errors = array(
				'error'	=> 'Usage rate doesn\'t need to update.',
			);
			return false;
		
		}

	}

	public function delete($id){

		$this->db->delete('usage_rates', array('id' => $id));

		if($this->db->affected_rows() > 0){

			return true;

		}else{

			$this->errors = array(
				'error'	=> 'No usage rate to delete.',
			);
			
			return false;

		}
		
	}

	public function get_errors(){

		return $this->errors;

	}
	
}