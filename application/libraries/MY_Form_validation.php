<?php

class MY_Form_validation extends CI_Form_validation{
	
	public function __construct($rules = array()){
	
		parent::__construct();
		
	}

	public function valid_date_duration($duration){

		$this->set_message('valid_date_duration', 'The %s field is not a valid ISO8601 date duration specification.');
		try{
			$duration = new DateInterval($duration);
			return true;
		}catch(Exception $e){
			return false;
		}

	}
	
	public function valid_date($date, $format){

		//default format matching the MYSQL datetime
		if(!$format){
			$format = 'Y-m-d H:i:s';
		}

		$this->set_message('valid_date', 'The %s field is not a valid date according to the format: ' . $format);
		$date_object = DateTime::createFromFormat($format, $date);
		return $date_object && $date_object->format($format) == $date;

	}

	public function valid_date_or_null ($date, $format) {

		if (is_null($date)) {
			return true;
		}

		return $this->valid_date($date, $format);

	}
	
	public function word_limit($str, $limit){

		$this->set_message('word_limit', 'The %s field has too many words.');
		if(str_word_count($str, 0) > $limit){
			return false;
		}		
		return true;
		
	}

	public function valid_json($str){

		$this->set_message('valid_json', 'The %s field is not valid JSON.');
		json_decode($str);
		return (json_last_error() == JSON_ERROR_NONE);

	}

	public function image_format($str){

		$this->set_message('image_format', 'The %s field is not a valid image format.');
		return (bool) preg_match('/^png|jpg|jpeg$/', $str);

	}

	public function string_boolean($str){

		$this->set_message('string_boolean', 'The %s field is not a valid string boolean.');
		return (bool) preg_match('/^true|false$/', $str);

	}

	public function boolean_style($value){

		//this will return null if the value is not of a boolean_style
		//if it is null, it fails, if it isn't null then it succeeds
		$this->set_message('boolean_style', 'The %s field does not evaluate to a boolean.');
		$bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		return !is_null($bool);

	}
	
}