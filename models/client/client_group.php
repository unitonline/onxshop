<?php
/**
 * class client_group
 * 
 * Copyright (c) 2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */
 
class client_group extends Onxshop_Model {

	/**
	 * primary key (serial)
	 */
	public $id;
	
	/**
	 * group title
	 */
	public $name;
	
	/**
	 * group description
	 */
	public $description;
	
	/**
	 * serialized filter
	 */
	public $search_filter;
	
	/**
	 * serialized reserved
	 */
	public $other_data;
	
	/**
	 * meta data 
	 */
	public $_hashMap = array(
		'id'=>array('label' => '', 'validation'=>'int', 'required'=>true), 
		'name'=>array('label' => '', 'validation'=>'string', 'required'=>true),
		'description'=>array('label' => '', 'validation'=>'string', 'required'=>false),
		'search_filter'=>array('label' => '', 'validation'=>'serialized', 'required'=>false),
		'other_data'=>array('label' => '', 'validation'=>'serialized', 'required'=>false)
		);
		
	/**
	 * init configuration
	 */
	 
	static function initConfiguration() {
	
		if (array_key_exists('client_group', $GLOBALS['onxshop_conf'])) $conf = $GLOBALS['onxshop_conf']['client_group'];
		else $conf = array();

		return $conf;
	}
		
	/**
	 * get company detail
	 */
	 
	public function getDetail($id) {
	
		if (!is_numeric($id)) return false;
		
		$data = $this->detail($id);
		
		$data['search_filter'] = unserialize($data['search_filter']);
		if ($data['other_data']) $data['other_data'] = unserialize($data['other_data']);
					
		return $data;
	}
	
	/**
	 * list available groups
	 */
	
	public function listGroups() {
	
		$list = $this->listing();
		
		$final_list = array();
		
		foreach ($list as $item) {
		
			$item['search_filter'] = unserialize($item['search_filter']);
			if ($item['other_data']) $item['other_data'] = unserialize($item['other_data']);
			
			$final_list[] = $item;
		
		}
		
		return $final_list;
	}
	
	/**
	 * save group
	 */
	 
	public function saveGroup($data) {
		
		if (!is_array($data)) return false;
		
		$data['search_filter'] = serialize($data['search_filter']);
		if (array_key_exists('other_data', $data)) $data['other_data'] = serialize($data['other_data']);
		
		return $this->save($data);
		
	}
}