<?php
/**
 * class client_address
 * 
 * Copyright (c) 2009-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */
 
class client_address extends Onxshop_Model {

	/**
	 * @access private
	 */
	var $id;
	/**
	 * REFERENCES country(id) ON UPDATE CASCADE ON DELETE CASCADE
	 * @access private
	 */
	var $country_id;
	/**
	 * REFERENCES customer(id) ON UPDATE CASCADE ON DELETE CASCADE
	 * @access private
	 */
	var $customer_id;
	/**
	 * @access private
	 */
	var $name;
	/**
	 * @access private
	 */
	var $line_1;
	/**
	 * @access private
	 */
	var $line_2;
	/**
	 * @access private
	 */
	var $line_3;
	/**
	 * @access private
	 */
	var $post_code;
	/**
	 * @access private
	 */
	var $city;
	/**
	 * @access private
	 */
	var $county;
	/**
	 * @access private
	 */
	var $telephone;
	/**
	 * @access private
	 */
	var $comment;
	/**
	 * @access private
	 */
	var $is_deleted;

	var $_hashMap = array(
		'id'=>array('label' => 'ID', 'validation'=>'int', 'required'=>true), 
		'country_id'=>array('label' => 'Country', 'validation'=>'int', 'required'=>true),
		'customer_id'=>array('label' => 'Customer', 'validation'=>'int', 'required'=>true),
		'name'=>array('label' => 'Name', 'validation'=>'string', 'required'=>true),
		'line_1'=>array('label' => 'Address line 1', 'validation'=>'string', 'required'=>true),
		'line_2'=>array('label' => 'Address line 2', 'validation'=>'string', 'required'=>false),
		'line_3'=>array('label' => 'Address line 3', 'validation'=>'string', 'required'=>false),
		'city'=>array('label' => 'City', 'validation'=>'string', 'required'=>true),
		'post_code'=>array('label' => 'Post code', 'validation'=>'string', 'required'=>true),
		'county'=>array('label' => 'County', 'validation'=>'string', 'required'=>false),
		'telephone'=>array('label' => 'Phone number', 'validation'=>'string', 'required'=>false),
		'comment'=>array('label' => 'Comment', 'validation'=>'string', 'required'=>false),
		'is_deleted'=>array('label' => 'Deleted?', 'validation'=>'boolean', 'required'=>false)
		);
		
	/**
	 * init configuration
	 */
	static function initConfiguration() {
		if (array_key_exists('client_address', $GLOBALS['onxshop_conf'])) $conf = $GLOBALS['onxshop_conf']['client_address'];
		else $conf = array();

		return $conf;
	}
	
	/**
	 * get detail
	 */

	function getDetail($id) {
	
		$address = $this->detail($id);
		
		require_once('models/international/international_country.php');
		if (is_numeric($address['country_id'])) {
			$Country = new international_country();
			$country_data = $Country->detail($address['country_id']);
			$address['country'] = $country_data;
		}
		
		return $address;
	}
	
	/**
	 * delete address
	 */
	
	public function deleteAddress($address_id) {
		
		if (!is_numeric($address_id)) return false;
		
		/**
		 * address detail
		 */
		 
		$address_detail = $this->detail($address_id);
		
		/**
		 * customer detail
		 */
		
		require_once('models/client/client_customer.php');
		$Customer = new client_customer();
		$Customer->setCacheable(false);
		$customer_detail = $Customer->getDetail($address_detail['customer_id']);
		
		/**
		 * check if address is not used
		 */
		 
		if ($customer_detail['invoices_address_id'] != $address_id && $customer_detail['delivery_address_id'] != $address_id) {
		
			$address_detail['is_deleted'] = true;
		
			if ($this->update($address_detail)) return true;
			else return false;
			
		} else {
		
			msg("Address (id=$address_id) is used as your active delivery or billing address", 'error');
			return false;
		}
	}
	
	
	/**
	 * check address is deleted
	 */
	 
	public function checkAddressIsDeleted($address_id) {
	
		if (!is_numeric($address_id)) return false;
		
		//TODO
		$this->listing("id = $address_id AND is_deleted IS NOT TRUE", "id DESC");
	}
}
