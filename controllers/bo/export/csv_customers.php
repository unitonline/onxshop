<?php
/** 
 * Copyright (c) 2008-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 * 
 */

class Onxshop_Controller_Bo_Export_CSV_Customers extends Onxshop_Controller {

	/**
	 * main action
	 */
	 
	public function mainAction() {
		
		set_time_limit(0);
		
		require_once('models/client/client_customer.php');
		
		$Customer = new client_customer();
		
		/**
		 * Get the list
		 */
		
		$records = $Customer->getClientList(0, $_SESSION['customer-filter']);
		
		if (is_array($records)) {
		
				/**
				 * parse records
				 */
				$header = 0;
				
				foreach ($records as $record) {
					/**
					 * Create a header
					 */
					if ($header == 0) {
						foreach ($record as $key=>$val) {
							$column['name'] = $key;
							//$column['type'] = $col->type;
					
							$this->tpl->assign('COLUMN', $column);
							$this->tpl->parse('content.th');
						}
						$header = 1;
					}
		        
					foreach ($record as $key=>$val) {
						if (!is_numeric($val)) {
							$val = addslashes($val);
							$val = '"' . $val . '"';
							$val = preg_replace("/[\n\r]/", '', $val);
						}
						
						$this->tpl->assign('value', $val);
						$this->tpl->parse('content.item.attribute');
					}
			
					$this->tpl->parse('content.item');
				}
		
			
			//set the headers for the output
		    /*
		    UTF16 for excel
		    header( "Content-type: application/vnd.ms-excel; charset=UTF-16LE" );
		    header('Content-Disposition: attachment; filename="export.csv"');
		    echo chr(255).chr(254).mb_convert_encoding( $vypis_csv, 'UTF-16LE', 'UTF-8′);*/
			header('Content-type: text/csv; charset=UTF-8');
			header('Content-Disposition: attachment; filename="customers-'.date('Y\-m\-d\_Hi').'.csv"');
			header("Cache-Control: cache, must-revalidate");
			header("Pragma: public");
		} else {
			echo "no records"; exit;
		}

		return true;
	}
}
