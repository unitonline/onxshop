<?php
/** 
 * Copyright (c) 2006-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 * 
 */

class Onxshop_Controller_Component_Image extends Onxshop_Controller {

	/**
	 * main action
	 */
	 
	public function mainAction() {
	
		$image_list = $this->mainImageAction();
		
		return true;
	}
	
	/**
	 * get image path
	 */
	 
	public function getImagePath() {
		
		/**
		 * check requested width
		 */
		 
		if (is_numeric($this->GET['width'])) $width = $this->GET['width'];
		else $width = 100;
		
		/**
		 * check requested height
		 */
		 
		if (is_numeric($this->GET['height']) && $this->GET['height'] > 0) $height = $this->GET['height'];
		else $height = 0;
		
		/**
		 * set path
		 */
		 
		if ($width == 0) $image_path = "/image/";
		else if ($height > 0) $image_path = "/thumbnail/{$width}x{$height}/";
		else $image_path = "/thumbnail/{$width}/";
		
		/**
		 * return path string
		 */
		 
		return $image_path;
	}
	
	/**
	 * main image action
	 */
	
	public function mainImageAction() {

		/**
		 * setting variables
		 */
	
		if ($this->GET['relation']) $relation = preg_replace('/[^a-zA-Z_-]/', '', $this->GET['relation']);
		else $relation = '';
		
		$img_path = $this->getImagePath();
		
		if ($this->GET['role']) $role = preg_replace('/[^a-zA-Z_-]/', '', $this->GET['role']);
		else $role = false;
		
		$role = $role ? "role='{$role}' AND ":'';
		
		if (is_numeric($this->GET['node_id'])) $node_id = $this->GET['node_id'];
		else {
			msg('image: node_id is not numeric', 'error');
			return false;
		}
		
		if ($this->GET['limit']) $limit = $this->GET['limit'];
		else $limit = "";
		
		
		/**
		 * creating image object
		 */
		 
		$Image = $this->createImageObject($relation);
		
		
		/**
		 * get list of images
		 */
		
		$image_list = $Image->listing("$role node_id=$node_id", "priority DESC, id ASC", $limit);
		
		foreach ($image_list as $k=>$item) {
			$item['path'] = $image_list[$k]['path'] = $img_path;
			$item['first_id'] = $image_list[$k]['first_id'] = $image_list[0]['id'];
			$this->tpl->assign('ITEM', $item);
			$this->tpl->parse('content.item');
		}
		
		/**
		 * save image count in registry for use in image_gallery
		 */
		
		//TODO
		
		/**
		 * return list of images found
		 */

		return $image_list;
	}
	
	
	/**
	 * Create image object
	 * 
	 */
	
	function createImageObject($relation) {
	
		switch ($relation) {
			case 'product':
				require_once('models/ecommerce/ecommerce_product_image.php');
				$Image = new ecommerce_product_image();
			break;
			case 'product_variety':
				require_once('models/ecommerce/ecommerce_product_variety_image.php');
				$Image = new ecommerce_product_variety_image();
			break;
			case 'taxonomy':
				require_once('models/common/common_taxonomy_label_image.php');
				$Image = new common_taxonomy_label_image();
			break;
			case 'node':
			default:
				require_once('models/common/common_image.php');
				$Image = new common_image();
			break;
		}
		
		return $Image;
	}
}
