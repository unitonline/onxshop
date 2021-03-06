<?php
/**
 * class common_configuration
 *
 * Copyright (c) 2009-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */
 
class common_configuration extends Onxshop_Model {

	/**
	 * NOT NULL PRIMARY KEY
	 * @access private
	 */
	var $id;
	/**
	 * NOT NULL REFERENCES common_node(id) ON UPDATE CASCADE ON DELETE CASCADE
	 * @access private
	 */
	var $node_id;
	/**
	 * @access private
	 */
	var $object;
	/**
	 * @access private
	 */
	var $property;
	/**
	 * @access private
	 */
	var $value;
	/**
	 * @access private
	 */
	var $description;


	var $_hashMap = array(
		'id'=>array('label' => 'ID', 'validation'=>'int', 'required'=>true), 
		'node_id'=>array('label' => '', 'validation'=>'int', 'required'=>true),
		'object'=>array('label' => '', 'validation'=>'string', 'required'=>true),
		'property'=>array('label' => '', 'validation'=>'string', 'required'=>true),
		'value'=>array('label' => '', 'validation'=>'string', 'required'=>false),
		'description'=>array('label' => '', 'validation'=>'string', 'required'=>false)
	);
	
	/**
	 * get configuration
	 */
	
	function getConfiguration($node_id = 0) {
	
		if (!is_numeric($node_id)) {
			msg("common_configuration.getConfiguration: node_id is not numeric", 'error');
			$node_id = 0;
		}
	
		$conf = array();
		
		$cs = $this->listing("node_id = {$node_id}");
			
		if (is_array($cs)) {
			foreach ($cs as $c) {
				$conf[$c['object']][$c['property']] = $c['value'];
			}
		}
		
		/**
		 * default core values (only for root node)
		 */
		
		if ($node_id == 0) {
		
			$conf = $this->getDefaultCoreValues($conf);

		}
			
		return $conf;
	}
	
	/**
	 * get default core values
	 */
	 
	public function getDefaultCoreValues($conf) {
		
		if (!array_key_exists('title', $conf['global'])) $conf['global']['title'] = 'White Label';
		if (!array_key_exists('html_title_suffix', $conf['global'])) $conf['global']['html_title_suffix'] = 'White Label';
		if (!array_key_exists('locale', $conf['global'])) $conf['global']['locale'] = 'en_GB.UTF-8';
		if (!array_key_exists('default_currency', $conf['global'])) $conf['global']['default_currency'] = 'GBP';
		if (!array_key_exists('admin_email', $conf['global'])) $conf['global']['admin_email'] = 'norbert@laposa.co.uk';
		if (!array_key_exists('author_content', $conf['global'])) $conf['global']['author_content'] = 'White Label, http://www.example.com/';
		if (!array_key_exists('copyright', $conf['global'])) {
			$year = date('Y', time());
			$title = $conf['global']['title'];
			$conf['global']['copyright'] = "&copy; $year <span>$title</span>";
		}
		if (!array_key_exists('credit', $conf['global'])) $conf['global']['credit'] = '<a href="http://www.onxshop.com" title="eCommerce solution"><span>Powered by Onxshop</span></a>';
		if (!array_key_exists('google_analytics', $conf['global'])) $conf['global']['google_analytics'] = '';
		if (!array_key_exists('css', $conf['global'])) $conf['global']['css'] = '';
		 
		define('GLOBAL_DEFAULT_CURRENCY', $conf['global']['default_currency']);
		
		/**
		 * default site template constants
		 */
		//used only in node/site/default.html
		if (!array_key_exists('extra_head', $conf['global'])) $conf['global']['extra_head'] = '';
		if (!array_key_exists('extra_body_top', $conf['global'])) $conf['global']['extra_body_top'] = '';
		if (!array_key_exists('extra_body_bottom', $conf['global'])) $conf['global']['extra_body_bottom'] = '';
		//used only in node/site/default.php
		if (!array_key_exists('display_content_side', $conf['global'])) $conf['global']['display_content_side'] = 1;
		if (!array_key_exists('display_content_foot', $conf['global'])) $conf['global']['display_content_foot'] = 0;
		
		/**
		 * default page constants (used in site as well)
		 */
		if (!array_key_exists('display_secondary_navigation', $conf['global'])) $conf['global']['display_secondary_navigation'] = 0;
		
		
		/**
		 * default node constants (valid for content,layout and page)
		 */
		if (!array_key_exists('display_title', $conf['global'])) $conf['global']['display_title'] = 1;
		
		
		/**
		 * product template constants
		 */
		if (!array_key_exists('product_list_per_page', $conf['global'])) $conf['global']['product_list_per_page'] = 24; //should be dividable by 4, 3 and 2
		if (!array_key_exists('product_detail_image_width', $conf['global'])) $conf['global']['product_detail_image_width'] = 350;
		if (!array_key_exists('product_list_image_width', $conf['global'])) $conf['global']['product_list_image_width'] = 175;
		//can be: gallery_smooth, gallery, simple_list
		if (!array_key_exists('product_image_gallery', $conf['global'])) $conf['global']['product_image_gallery'] = 'gallery_smooth';
		
		/**
		 * set default values if empty
		 */
		
		if ($conf['global']['admin_email_name'] == '') $conf['global']['admin_email_name'] = 'Webmaster';
	
		return $conf;
	}
	 
	
	/**
	 * TODO: keep id up to 1000 as reserved for system
	 */

	function saveConfig($object, $property, $value, $node_id = 0) {
		
		//check
		if (!is_numeric($node_id)) {
			msg('common_configuration->saveConfig(): node_id is not numeric', 'error');
			return false;
		}
		
		//trim
		$object = trim($object);
		$property = trim($property);
		$value = trim($value);
		
		//try to find current value
		$cs = $this->listing("node_id = $node_id AND object = '$object' AND property = '$property'");
		if (count($cs) > 0) $conf_old = $cs[0];
		else $conf_old = array();

		
		$conf_new = $conf_old;
		
		//set values
		$conf_new['node_id'] = $node_id;
		$conf_new['object'] = $object;
		$conf_new['property'] = $property;
		$conf_new['value'] = $value;
		$conf_new['description'] = '';

		//compare and save only if values changed
		if ($this->checkIfValuesChanged($conf_old, $conf_new)) {
			
			//save (update or insert)
			if ($this->save($conf_new)) return true;
			else return false;
		} else {
		
			return false;
		}
	}
	
	/**
	 * compare values
	 */
	 
	public function checkIfValuesChanged($old, $new) {

		if ($old['node_id'] != $new['node_id'] ||
			$old['object'] != $new['object'] ||
			$old['property'] != $new['property'] ||
			$old['value'] != $new['value'] ||
			$old['description'] != $new['description']) {
		
			return true;
		} else {
			
			return false;
		}
	}

}
