<?php
/** 
 * Copyright (c) 2006-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 */

class Onxshop_Controller_Component_Ecommerce_Product_Related extends Onxshop_Controller {
	
	/**
	 * main action
	 */
	 
	public function mainAction() {
	
		/**
		 * create objects
		 */
		 
		require_once('models/common/common_node.php');
		require_once('models/ecommerce/ecommerce_product_to_product.php');
		
		$Node = new common_node();
		$PtP = new ecommerce_product_to_product();
		
		/**
		 * Set variables
		 */
		 
		if (is_numeric($this->GET['product_node_id'])) {
			$product_node_data = $Node->detail($this->GET['product_node_id']);
			$product_id = $product_node_data['content'];
		} else if (is_numeric($this->GET['id']) ) {
			$product_id = $this->GET['id'];
		}
		
		if (is_numeric($this->GET['limit'])) {
			$limit = $this->GET['limit'];
		} else {
			$limit = 5;
		}
		
		/**
		 * relation type
		 */
		 
		switch ($this->GET['type']) {
		
			case 'static':
				$type = 'static';
				$this->tpl->assign("TITLE", I18N_RELATED_PRODUCTS_STATIC);
			break;
			case 'dynamic':
			default:
				$type = 'dynamic';
				$this->tpl->assign("TITLE", I18N_RELATED_PRODUCTS_DYNAMIC);
			break;
		}
		
		/**
		 * template
		 */
		
		switch ($this->GET['product_list_template']) {
		
			case '2columns':
				$product_list_template = 'product_list_2columns';
			break;
			case '4columns':
				$product_list_template = 'product_list_4columns';
			break;
			case '3columns':
			default:
				$product_list_template = 'product_list_3columns';
			break;
		}
		
		/**
		 * listing
		 */
		 
		if (is_numeric($product_id)) {
		
			$related = $PtP->getRelatedProduct($product_id, $limit, $type);
		
			/**
			* Pass product_id_list to product_list controller
			*/
			
			if (is_array($related) && count($related) > 0) {

				/**
				 * prepare HTTP query for product_list component
		 		 */
		 	 
				$related_list['product_id_list'] = $related;
				$query = http_build_query($related_list, '', ':');
				
				/**
				 * call controller
				 */
				 
				$_nSite = new nSite("component/ecommerce/{$product_list_template}~{$query}~");
				$this->tpl->assign('ITEMS', $_nSite->getContent());
				$this->tpl->parse('content.product_related');
			}
		}

		return true;
	}
}
