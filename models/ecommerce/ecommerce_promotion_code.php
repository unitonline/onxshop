<?php
/**
 * class ecommerce_promotion_code
 *
 * Copyright (c) 2009-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */
 
class ecommerce_promotion_code extends Onxshop_Model {

	/**
	 * @access private
	 */
	public $id;

	/**
	 * REFERENCES ecommerce_promotion ON UPDATE CASCADE ON DELETE RESTRICT
	 * @access private
	 */
	public $promotion_id;

	/**
	 * @access private
	 */
	public $code;

	/**
	 * REFERENCES ecommerce_order ON UPDATE CASCADE ON DELETE RESTRICT
	 * @access private
	 */
	public $order_id;

	public $_hashMap = array(
		'id'=>array('label' => '', 'validation'=>'int', 'required'=>true), 
		'promotion_id'=>array('label' => '', 'validation'=>'int', 'required'=>true),
		'code'=>array('label' => '', 'validation'=>'string', 'required'=>true),
		'order_id'=>array('label' => '', 'validation'=>'int', 'required'=>true)
		);

	/**
	 * insert promotion code (apply promotion code)
	 */
	 
	public function insertPromotionCode($code, $order_id) {
	
		require_once('models/ecommerce/ecommerce_promotion.php');
		$Promotion = new ecommerce_promotion();
		
		if ($compaign_data = $Promotion->checkCodeMatch($code)) {
		
			$data = array();
			$data['promotion_id'] = $compaign_data['id'];
			$data['code'] = $code;
			$data['order_id'] = $order_id;

			if ($inserted_code_id = $this->insert($data)) {
				return $inserted_code_id;
			}
		}
		
		return false;
	}
	
	/**
	 * get usage
	 */
	 
	public function getUsageOfSingleCode($code, $customer_id = false) {
	
		$code = addslashes($code);
	
		if (is_numeric($customer_id)) {
			$sql = "
			SELECT * FROM ecommerce_promotion_code p 
			LEFT OUTER JOIN ecommerce_order o ON (o.id = p.order_id)
			LEFT OUTER JOIN ecommerce_basket b ON (b.id = o.basket_id)
			WHERE p.code = '$code' AND b.customer_id = $customer_id";
		} else {
	    	$sql = "SELECT * FROM ecommerce_promotion_code p WHERE p.code = '$code'";
    	}
		
		if ($records = $this->executeSql($sql)) {
			return $records;
		} else {
			return false;
		}
	}

	/**
	 * find code for an order
	 */
	 
	public function getPromotionCodeForOrder($order_id) {
	
		if (!is_numeric($order_id)) return false;
		
		$list = $this->listing("order_id = $order_id");
		
		if (is_array($list)) {
			if (count($list) > 0) {
				return $list[0]['code'];
			} else {
				return '';
			}
		} else return false;
	}
	

}
