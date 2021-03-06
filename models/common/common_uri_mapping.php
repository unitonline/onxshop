<?php
/**
 * class common_uri_mapping
 *
 * Copyright (c) 2009-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */
 
class common_uri_mapping extends Onxshop_Model {

	/**
	 * @access private
	 */
	var $id;
	/**
	 * 
	 * @access private
	 */
	var $node_id;
	/**
	 * @access private
	 */
	var $public_uri;
	
	var $type;
	
	var $_hashMap = array(
		'id'=>array('label' => '', 'validation'=>'int', 'required'=>true), 
		'node_id'=>array('label' => '', 'validation'=>'int', 'required'=>true),
		'public_uri'=>array('label' => 'Public URI', 'validation'=>'string', 'required'=>true),
		'type'=>array('label' => 'Type', 'validation'=>'string', 'required'=>true)
		);
	
	/**
	 * init configuration
	 */
	 
	static function initConfiguration() {
	
		if (array_key_exists('common_uri_mapping', $GLOBALS['onxshop_conf'])) $conf = $GLOBALS['onxshop_conf']['common_uri_mapping'];
		else $conf = array();
	
		require_once('models/common/common_node.php');
		$node_conf = common_node::initConfiguration();

		/**
		 * default settings
		 */
		 
		if (!array_key_exists('homepage_id', $conf)) $conf['homepage_id'] = $node_conf['id_map-homepage'];
		if (!array_key_exists('404_id', $conf)) $conf['404_id'] = $node_conf['id_map-404'];
		
		if (!array_key_exists('seo', $conf)) $conf['seo'] = true;
		if (!array_key_exists('rewrite_home', $conf)) $conf['rewrite_home'] = true;
		if (!array_key_exists('delimiter', $conf)) $conf['delimiter'] = '/';
		if (!array_key_exists('append', $conf)) $conf['append'] = '';
		if (!array_key_exists('hash', $conf)) $conf['hash'] = false;
		if (!array_key_exists('and_string', $conf)) $conf['and_string'] = I18N_AND;

		return $conf;
	}
	
	/**
	 * Constructor
	 * 
	 */
		
	function __construct($update = 0) {
	
		$this->_class_name = get_class($this);
		$this->generic();
		
		if (!is_numeric($update)) $update = 0;
		$this->_rewrite_table = $this->getGenericURITable($update);
	}


	/**
	 * get list
	 */
	 
	public function getList() {
	
		$records = $this->listing('', 'public_uri ASC');
		
		return $records;
		
	}
	
	/**
	 * get detailed list
	 */
	 
	public function getDetailList() {
	
		$sql = 'SELECT * FROM common_uri_mapping mapping
		LEFT OUTER JOIN common_node node ON (node.id = mapping.node_id)
		ORDER BY public_uri ASC';
		
		$records = $this->executeSql($sql);
		
		return $records;
		
	}
	
	/**
	 * system links to public translation
	 */
	 
	function system_uri2public_uri($html) {
	
		// first to the nice
		$html = $this->to_cms_url($html);
		
		// second to the SEO nice
		if ($this->conf['seo'] == true) $html = $this->to_seo_url($html);
		
		return $html;
	}
	
	/**
	 * translation into cms urls
	 */
	 
	function to_cms_url($html) {
	
		$html = preg_replace("/href=[\"\'](?!JavaScript)(?!http)\/index.php\?request=" . addcslashes(ONXSHOP_DEFAULT_LAYOUT, '/') . ".page~id=([^\~]*)~[\"\']/i", "href=\"/page/\\1\"", $html);
		//fix home URI
		if ($this->conf['rewrite_home']) $html = preg_replace("/href=[\"\'](?!JavaScript)(?!http)\/page\/".$this->conf['homepage_id'].'"/', "href=\"/\"", $html);
		
		return $html;
	}
	
	/**
	 * translation into seo urls
	 */
	 
	function to_seo_url($html) {
	
		//$html = preg_replace_callback("/([(href)(value)(action)]{1})=[\"\'](?!JavaScript)(?!http)\/page\/([0-9]*)[\"\']/i", array($this, '_replace'), $html);
		//$html = preg_replace_callback("/(href|value|action)=[\"\'](?!JavaScript)(?!http)\/page\/([0-9]*)(\?[^\"\'.]*)[\"\']/i", array($this, '_replace'), $html);
		$html = preg_replace_callback("/(href|value|action)=([\"\'])(?!JavaScript)(?!http)\/page\/([0-9]*)/i", array($this, '_replace'), $html);
		
		return $html;
	}
	
	/**
	 * string to seo
	 */
	 
	function stringToSeoUrl($string) {

		$id = preg_replace("/\/page\//", '', $string);
		
		if ($id == $this->conf['homepage_id']) $seo_url = "/";
		else $seo_url = $this->_rewrite_table[$id];
		
		if ($seo_url != '') return $seo_url;
		else return $string;
	}
	
	/**
	 * translate
	 */
	 
	function translate($string) {

		if (is_array($this->_rewrite_table)) $u = array_search($string, $this->_rewrite_table);
		//workaround for translate of already translated :), eg /page/121

		if ($u == '') $u = $string;
		else $u = "/page/$u";
		
		return $u;
	}
	
	/**
	 * get node id from seo url
	 */
	
	function getNodeIdFromSeoUri($seo_uri) {
		
		if (trim($seo_uri) == '') return false;
		
		if ($seo_uri == '/') return $this->conf['homepage_id'];
		
		$result = $this->listing("public_uri = '$seo_uri'");
		
		if (count($result) > 0) return $result['0']['node_id'];
		else return false;
	}
	
	/**
	 * replace
	 */
	 
	function _replace($matches) {
	
		if (is_array($this->_rewrite_table)) {
		
			if (array_key_exists($matches[3],  $this->_rewrite_table)) {
				$replace_string = $this->_rewrite_table[$matches[3]];
			} else {
				$replace_string = "/page/{$matches[3]}";
			}
		
			return "{$matches[1]}={$matches[2]}{$replace_string}";
		
		} else {
		
			msg('common_uri_mapping:_replace: rewrite table is not an array', 'error', 2);
			return "{$matches[1]}=\"/page/{$matches[3]}\"";
		
		}
	}
	
	/**
	 * get generic uri table
	 */
	 
	function getGenericURITable($update = 0) {
	
		if ($update == 1) {
			//delete old one
			$this->deleteURIMapping();
			
			//creating rewrite table
			$rewrite_table = $this->generateURITable();

			//insert in the DB
			foreach ($rewrite_table as $key=>$val) {
				$item['node_id'] = $key;
				$item['public_uri'] = $val;
				$item['type'] = 'generic';
				$this->insert($item);
			}
			msg("URI table has been generated");
		} else {
			//get from database
			$rt = $this->listing("type = 'generic'");
			foreach ($rt as $r) {
				$rewrite_table[$r['node_id']] = $r['public_uri'];
			}
		}
		return $rewrite_table;
	}

	/**
	 * generate uri table
	 */
	 
	function generateURITable() {
	
		require_once('models/common/common_node.php');
		$Node = new common_node();
		
		$nodes = $Node->listing("node_group = 'page'");
		$rewrite_table = array();

		foreach ($nodes as $p) {
			$rewrite_table[$p['id']] = $this->generateSingleURIFullPath($p);
		}
		
		return $rewrite_table;
		
	}
	
	/** 
	 * generate single
	 */
	 
	function generateSingleURIFullPath($node_data) {
	
			require_once('models/common/common_node.php');
			$Node = new common_node();
		
			$fp = $Node->getFullPathDetail($node_data['id']);
			
			$fullpath = "";
			
			foreach ($fp as $f) {
				if ($f['node_group'] == 'page') {
				
					if ($f['uri_title'] != '') $title = $this->cleanTitle($f['uri_title']);
					else $title = $this->cleanTitle($f['title']);
					
					// for blog/news pages prepend date
					if ($f['node_controller'] == 'news') {
						$title = preg_replace("/-/", "/", substr($f['created'], 0, 10)) . "/$title";
					}
					
					if ($fullpath == '') $fullpath =  $title . $fullpath;
					else $fullpath =  $title . str_replace('/', $this->conf['delimiter'], $fullpath);
					
					if ($this->conf['hash'] == true) $fullpath = '/' . sprintf('%08X', crc32($fullpath));
					else $fullpath = '/' . $fullpath;
				}
				if (!preg_match("/" . $this->conf['append'] . "$/", $fullpath)) $fullpath  = $fullpath . $this->conf['append'];
			}
		
			return $fullpath;
	}
	
	/**
	 * update single
	 */
	 
	function updateSingle($node_data) {
	
		if ($node_data['node_group'] != 'page') return false;
		
		$list = $this->listing("node_id = {$node_data['id']} AND type = 'generic'");
		
		if (!is_array($list[0]) || !is_numeric($list[0]['id'])) return false;
		
		$item = $this->detail($list[0]['id']);
		
		$old_uri = $item['public_uri'];
		$item['public_uri'] = $this->generateSingleURIFullPath($node_data);
		
		if ($old_uri != $item['public_uri']) {
		
			if ($this->update($item)) {
		
				$sql = "UPDATE common_uri_mapping SET public_uri = regexp_replace(public_uri, '{$old_uri}/', '{$item['public_uri']}/') WHERE id != {$item['id']};";
				$this->executeSql($sql);
				
				return true;
		
			} else {
		
				return false;
		
			}
		
		} else {
		
			return true;
		
		}
	}
	
	/**
	 * insert new path
	 */
	 
	function insertNewPath($node_data) {
	
		$fullpath = $this->generateSingleURIFullPath($node_data);
		
		$item['node_id'] = $node_data['id'];
		$item['public_uri'] = $fullpath;
		$item['type'] = 'generic';
		
		if ($id = $this->insert($item)) return $id;
		else return false;
	}
	
	/**
	 * clean title
	 */
	
	function cleanTitle($title) {
	
		$title = str_replace('/', '-', trim($title));
		$title = $this->recodeUTF8ToAscii($title);
		$title = strtolower($title);
		$title = preg_replace("/\s/", "-", $title);
		$title = preg_replace("/&[^([a-zA-Z;)]/", $this->conf['and_string'] . '-', $title);
		$title = preg_replace("/[^\w-\/\.]/", '', $title);
		$title = preg_replace("/\-{2,}/", '-', $title);
		$title = trim($title, '-');	
		
		return $title;
	}
	
	/**
	 * get request
	 */
	 
	function getRequest($node_id) {	
		
		require_once('models/common/common_node.php');
		$Node = new common_node();
		
		if ($Node->detail($node_id)) {
			$append = ".node~id=$node_id~";
			if ($node_id == $this->conf['404_id']) $append = "{$append}.sys/404";
		} else {
			$append = ".node~id=" . $this->conf['404_id'] . "~.sys/404";
		}
		
		if ($_GET['fe_edit'] == 1) {
			$request = ONXSHOP_DEFAULT_TYPE . "~id=$node_id~.bo/fe_edit~id=$node_id~." . ONXSHOP_MAIN_TEMPLATE . "~id=$node_id~$append";
		} else {
			$request = ONXSHOP_DEFAULT_LAYOUT . "~id=$node_id~" . "$append";
		}
		return $request;
	}

	/**
	 * delete mapping
	 */
	 
	function deleteURIMapping($type = 'generic') {
	
		switch ($type) {
			case 'all':
				$this->deleteAll();
				break;
			case 'generic':
				$this->deleteAll("type = 'generic'");
				break;
		}
	}
	
	/**
	 * list redirect uri
	 */
	 
	function listRedirectURIRecords() {
		
		$records = $this->listing("type = '301'");
		
		if (is_array($records)) {
			return $records;
		} else {
			return false;
		}
	}
	
	/**
	 * get redirect uri
	 */
	 
	function getRedirectURI($uri) {
		
		$records = $this->listing("type = '301' AND public_uri = '$uri'");

		if (is_array($records) && count($records) > 0) return $records[0];
		else return false;
	}

	/**
	 * duplicated function from common_file
	 */
	
	function recodeUTF8ToAscii($string) {
	
		$string = trim($string);

		//recode to ASCII
		if (function_exists("recode_string")) {
			$string = recode_string("utf-8..flat", $string);
		} else {
			//$string = iconv("UTF-8", "ASCII//TRANSLIT", $string);
			$string = mb_convert_encoding($string, "HTML-ENTITIES", "UTF-8");
			$string = preg_replace('/\&(.)[^;]*;/', "\\1", $string);
		}
	
		return $string;
	}
	
	
}
