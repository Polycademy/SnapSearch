<?php

/**
 * Pin Model contacts the Pin service API to CRUD customers.
 * There should not be any accompanying controller. This model should be used by the Billing Model
 */
class Pin_model extends CI_Model{

	public function __construct(){

	}

	public function create_customer(){

		//should return the customer code

	}

	public function update_customer(){


	}

	public function delete_customer(){


	}

	public function charge_customer(){

		//take customer code, and charge, and return charge code

	}

}