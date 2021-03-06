<?php
/** 
 * Copyright (c) 2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */

require_once('controllers/component/taxonomy.php');

class Onxshop_Controller_Component_Taxonomy_Label extends Onxshop_Controller_Component_Taxonomy {

	/**
	 * parse to template
	 */
	 
	function parseListToTemplate($list) {
		
		if (!is_array($list)) return false;
		
		if (is_numeric($this->GET['label'])) $label_id = $this->GET['label'];
		else return true;
		
		foreach ($list as $item) {
		
			if ($label_id == $item['parent']) {
				
				$_nSite = new nSite("component/image&amp;relation=taxonomy&amp;width=250&amp;nolink=1&amp;node_id={$item['id']}");
				$this->tpl->assign("IMAGE", $_nSite->getContent());
				
				$this->tpl->assign('ITEM', $item);
			
				$this->tpl->parse('content.taxonomy');
			
			}
		}
			
	}
	
}
		
