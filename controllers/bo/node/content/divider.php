<?php
/**
 * Copyright (c) 2006-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 */

require_once('controllers/bo/node/default.php');

class Onxshop_Controller_Bo_Node_Content_Divider extends Onxshop_Controller_Bo_Node_Default {

	/**
	 * post action
	 */

	function post() {
		$this->tpl->assign("SELECTED_{$this->component_data['type']}", "selected='selected'");
	}
}
