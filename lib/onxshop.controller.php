<?php
/**
 * Copyright (c) 2005-2011 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */

class Onxshop_Controller {

	/**
	 * request GET parameter.
	 * @access public
	 */
	var $request;

	/**
	 * All messages.
	 * @access public
	 */
	var $messages;

	/**
	 * Content after parsing.
	 * @access public
	 */
	var $content;

	var $modules;

	var $module;

 	var $GET;

	var $_module_html;

	var $_template_dir;

	var $_module_php;

	var $http_status;

	/**
	 * Construct
	 */
	 
	public function __construct($request, &$subOnxshop = false) {
		return $this->process($request, $subOnxshop);
	}
	
	/**
	 * process
	 *
	 * @param unknown_type $request
	 * @param unknown_type $subOnxshop
	 * @return Onxshop
	 */
	 
	public function process($request, &$subOnxshop = false) {
	
		msg("ONXSHOP_REQUEST: BEGIN $request", "ok", 2);
		// to think about - is this a good idea?
		$this->GET = $_GET;
		$this->setRequest($request);

		$module = $this->_explodeRequest($request);
		
		$this->_module_html = "{$module['view']}.html";
	
		$this->_template_dir = getTemplateDir($this->_module_html);
	
		
		
		$this->_module_php = ONXSHOP_PROJECT_DIR . "controllers/{$module['controller']}.php";
		if (!file_exists($this->_module_php)) $this->_module_php = ONXSHOP_DIR . "controllers/{$module['controller']}.php";
	
		if ($this->_template_dir != '') $this->_initTemplate($this->_module_html);
		else {
			/*
			msg("Template {$this->_module_html} Doesn't Exist", 'error', 2);
			$this->_template_dir = ONXSHOP_DIR . "templates/";
			$this->_initTemplate('blank.html');
			*/
			//exit;
		}
	
		//look for the Onxshop tags
		$this->parseContentTagsBefore();
	
		// main action controller
		// if some error comes from controller, save it into registry, this will not allow save cache in onxshop.bootstrap
		
		msg("mainAction html: " . $this->_template_dir . $this->_module_html, 'ok', 2);
		msg("mainAction php: " . $this->_module_php, 'ok', 2);
		
		if (!$this->mainAction()) {
			Zend_Registry::set('controller_error', $request);
			msg( "Error in $request", 'error', 1);
		}
		
		/**
		 * subconent
		 */
		
		if (is_object($subOnxshop)) { 
			$this->tpl->assign('SUB_CONTENT', $subOnxshop->getContent());
		}
	
		if ($this->_template_dir != '') {   
			//refresh variables after processing controller
			$this->_initTemplateVariables();
			$this->_parseTemplate();
		} else {
			msg("{$this->_module_html} " . _('does not exists.'), 'error', 2);
		}
		
		msg("ONXSHOP_REQUEST: END $request", "ok", 2);
		
		//if all went OK, return true
		return true;
		
	}
	
	/**
	 * mainAction
	 */

	public function mainAction() {
	
		//msg("no action for {$this->request}");

		return true;
		
	}
	
	/**
	 * set request
	 *
	 * @param unknown_type $request
	 */
	 
	public function setRequest($request) {

		if (preg_match('/[^a-z0-9\-\._\/\&\=\{\}\$\[\]|%@]&amp;+/i',$request)) {
			die('Invalid request: '.htmlspecialchars($request));
		} else {
			$this->request = $request;
		}
		
	}

    /**
     * get request
     *
     * @return unknown
     */
     
	function getRequest() {
	
		return $this->request;
		
	}

    /**
     * set title
     *
     * @param unknown_type $value
     * @return unknown
     */
     
	function setTitle($value) {
	
		$value = trim($value);
		if ($value != '') {
			$this->title = $value;
			if (Zend_Registry::isRegistered('browser_title')) {
				Zend_Registry::set('browser_title', Zend_Registry::get('browser_title') . ' - ' . $value);
			} else {
				Zend_Registry::set('browser_title', $value);
			}
			
			return true;
		} else {
			return false;
		}
		
	}
	
    /**
     * set description
     *
     * @param unknown_type $value
     * @return unknown
     */
     
	function setDescription($value) {
	
		$value = trim($value);
		if ($value != '') {
			$this->title = $value;
			if (Zend_Registry::isRegistered('description')) {
				Zend_Registry::set('description', Zend_Registry::get('description') . ' - ' . $value);
			} else {
				Zend_Registry::set('description', $value);
			}
			
			return true;
		} else {
			return false;
		}
		
	}
	
    /**
     * set keywords
     *
     * @param unknown_type $value
     * @return unknown
     */
     
	function setKeywords($value) {
	
		$value = trim($value);
		if ($value != '') {
			$this->title = $value;
			if (Zend_Registry::isRegistered('keywords')) {
				Zend_Registry::set('keywords', Zend_Registry::get('keywords') . ', ' . $value);
			} else {
				Zend_Registry::set('keywords', $value);
			}
			
			return true;
		} else {
			return false;
		}
		
	}

    /**
     * get title
     *
     * @return unknown
     */
     
	function getTitle() {
	
		return $this->title;
		
	}
	
    /**
     * set head
     *
     * @param unknown_type $head
     * @return unknown
     */
     
	function setHead($value) {
	
		$value = trim($value);
		if ($value != '') {
			$this->head = $value;
			$value = "<!--HEAD block of {$this->_module_html} -->\n" . $value;
			if (Zend_Registry::isRegistered('head')) {
				//because we are processing childs first, do a reverse order
				$value = $value . "\n" . Zend_Registry::get('head');
			}
			Zend_Registry::set('head', $value);
			return true;
		} else {
			return false;
		}
		
	}
	
	/**
     * set head once
     *
     * @param unknown_type $head
     * @return unknown
     */
     
	function setHeadOnce($value) {
	
		$name = 'head_' + $this->_module_html;
		
		if (!Zend_Registry::isRegistered($name)) $this->setHead($value);
		
		Zend_Registry::set($name, true);
				
	}

    /**
     * get head
     *
     * @return unknown
     */
     
	function getHead() {
	
		return $this->head;
		
	}

    /**
     * set content
     *
     * @param unknown_type $content
     * @return unknown
     */
     
	function setContent($content) {
	
		$this->content = $content;
		return true;
		
	}

    /**
     * get content
     *
     * @return string
     */
     
	function getContent() {
	
		return $this->content;
		
	}


    /**
     * Parse Content Tags
     */
     
	function parseContentTags() {
		
		$content = $this->tpl->filecontents;
		
		if ($matches = $this->findTags($content)) {
		
			//contentx is used for layout mapping
			$contentx['matches'] = $matches;
			
			foreach ($matches[2] as $key=>$xrequest) {
			
				preg_match_all('/GET\.([^\&~:]*)[\&]*/', $xrequest, $m);
				
				foreach ($m[0] as $k=>$v) {
					$xrequest = str_replace("{$v}", $this->GET[$m[1][$k]], $xrequest);
				}
				
				$_nxSite = new nSite($xrequest);
				
				if ($layout == 1) {
					$container = $matches[1][$key];		
					$contentx['content'][$container] .= $_nxSite->getContent();
					$contentx['node_type'][$container] = $_nxSite->temp['node_type'];
				} else {
					//because of stupid parseContentTagsAfter(), we have to check if it isn't assigned already
					if ($this->tpl->vars["ONXSHOP_REQUEST_{$matches[1][$key]}"] == '') {
						$this->tpl->assign("ONXSHOP_REQUEST_{$matches[1][$key]}", $_nxSite->getContent());
					}
				}
			}
		}
		
		return $contentx;
		
	}

	/**
	 * Parse content tags before module
	 */
	 
	function parseContentTagsBefore() {
	
		$this->parseContentTagsBeforeHook();
		$this->parseContentTags();
		
	}
	
	/**
	 * hook before content tags parsed
	 */
	 
	function parseContentTagsBeforeHook() {
	
		return true;
		
	}
	
    
    /**
     * find onxshop request tags
     *
     * @param unknown_type $content
     * @return unknown
     */
     
	function findTags($content) {
	
		preg_match_all('/\{ONXSHOP_REQUEST_([^\}]*) #([^\}]*)\}/', $content, $matches);
		if (count($matches[0]) > 0) {
			return $matches;
		} else {
			return false;
		}
		
	}

    /**
     * find containers
     *
     * @param unknown_type $content
     * @return unknown
     */
     
	function findContainerTags($content) {
	
		//{CONTAINER.0.content.content #RTE} 
		preg_match_all('/\{CONTAINER\.([0-9]*)\.([a-zA-Z]*).[^\}]* #([^\}]*)\}/', $content, $matches);
		if (count($matches[0]) > 0) {
			return $matches;
		} else {
			return false;
		}
		
	}



    /**
     * final output
     *
     * @param unknown_type $return
     * @return unknown
     */
     
	function finalOutput() {
	
		$output = $this->getContent();
		
		if (ONXSHOP_COMPRESS_OUTPUT == 1) {
			$output = preg_replace("/[\r\n ]{1,}/"," ",$output);
			$output = preg_replace("/[\t ]{2,}/"," ",$output);
		}

		return $output;
		
		//hack
		$_SESSION['active_pages'] = array();
		
	}


	/**
	 * @return array
	 */
	 
	function _explodeRequest($request) {
	
		/**
		 * Add global GET parameters to $this->GET
		 */
		$request = str_replace('&amp;', '&', $request);
		$request = explode('&', $request);
		//print_r($request);
		for ($i=1; $i<count($request); $i++) {
			$param = explode('=', $request[$i]);
			// NOT USE GLOBAL??? yes :)
			// this rewrite GET with local GET
			$this->GET[$param[0]] = $param[1];
			//$_GET[$param[0]] = $param[1];
		}

		$module = $request[0];
	
		$vc = explode('@', $module);
		//print_r($vc);
		if (count($vc) > 0) {
			$m['controller'] = $vc[0];
			if (isset($vc[1])) $m['view'] = $vc[1];
			else $m['view'] = $vc[0];
		} else {
			$m['controller'] = $module;
			$m['view'] = $module;
		}


		/**
		 * valid syntax controller@view~param:value~
		 * TODO: allow controller~param:value~@view~param:value~
		 */
		if(preg_match('/([^\~]*)\~([^\~]*)\~/i', $m['view'], $match)) {

			parse_str(preg_replace('/:/', '&', $match[2]), $parsed_GET);
			$this->GET = array_merge($this->GET, $parsed_GET);
			if(preg_match('/(.*)@([^~]*)/', $match[1], $module_override)) {
				$m['controller'] = $module_override[1];
				$m['view'] = $module_override[2];
			
			} else {
				$m['controller'] = $m['view'] = $match[1];
			}
	
		}
		
		return $m;
		
	}

    /**
     * parse template
     *
     */
     
	function _parseTemplate() {
	
		$this->_parseMessages();
		if ($title  = $this->_parseTitle()) $this->setTitle($title);
		if ($description  = $this->_parseDescription()) $this->setDescription($description);
		if ($keywords  = $this->_parseKeywords()) $this->setKeywords($keywords);
		if ($head = $this->_parseHead()) $this->setHead($head);
		if ($head = $this->_parseHeadOnce()) $this->setHeadOnce($head);
		if ($content = $this->_parseContent()) $this->setContent($content);
	}
	
    /**
     * parse title
     * only if title block is present
     */
     
	function _parseTitle() {
	
		if (array_key_exists('title', $this->tpl->blocks)) {
			$this->tpl->parse('title');
			return $this->tpl->text('title');
		} else {
			return false;
		}
		
	}
	
    /**
     * parse description
     * only if description block is present
     */
     
	function _parseDescription() {
	
		if (array_key_exists('description', $this->tpl->blocks)) {
			$this->tpl->parse('description');
			return $this->tpl->text('description');
		} else {
			return false;
		}
		
	}
	
    /**
     * parse keywords
     * only if title block is present
     */
     
	function _parseKeywords() {
	
		if (array_key_exists('keywords', $this->tpl->blocks)) {
			$this->tpl->parse('keywords');
			return $this->tpl->text('keywords');
		} else {
			return false;
		}
		
	}
	
    /**
     * parse head
     * only if head block is present
     */
     
	function _parseHead() {
	
		if (array_key_exists('head', $this->tpl->blocks)) {
			$this->tpl->parse('head');
			return $this->tpl->text('head');
		} else {
			return false;
		}
		
	}
	
	/**
     * parse head once
     * only if head_once block is present
     */
     
	function _parseHeadOnce() {
	
		if (array_key_exists('head_once', $this->tpl->blocks)) {
			$this->tpl->parse('head_once');
			return $this->tpl->text('head_once');
		} else {
			return false;
		}
		
	}
	
	
    /**
     * parse content
     * only if head block is present
     */
     
	function _parseContent() {
	
		if (array_key_exists('content', $this->tpl->blocks)) {
			$this->tpl->parse('content');
			return $this->tpl->text('content');
		} else {
			return false;
		}
		
	}
	
   /**
     * parse messages
     * display and remove messages from session if message block is present
     */
     
	function _parseMessages() {

		if ($_SESSION['messages']) {
			if (array_key_exists('content.messages', $this->tpl->blocks)) {
				$messages = $_SESSION['messages'];
	
				$this->tpl->assign('MESSAGES', $messages);
				$this->tpl->parse('content.messages');

				$_SESSION['messages'] = '';
			}
		}
		
	}



    /**
     * init template
     *
     * @param unknown_type $template_file
     */
     
	function _initTemplate($template_file) {
	
		// base template for engine
		$this->tpl = new XTemplate ($template_file, $this->_template_dir);
		// set base variables
		$this->_initTemplateVariables();
		
	}

    /**
     * Initialize global template variables
     *
     */
     
	function _initTemplateVariables() {
	
		if ($_SERVER['HTTPS']) $protocol = 'https';
		else $protocol = 'http';
		
		$this->tpl->assign('PROTOCOL', $protocol);
		$this->tpl->assign('BASE_URI', "$protocol://{$_SERVER['SERVER_NAME']}");
		$uri = "$protocol://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
		$this->tpl->assign('URI', $uri);
		if (ONXSHOP_CUSTOMER_USE_SSL) $this->tpl->assign('URI_SAFE', "https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
		else $this->tpl->assign('URI_SAFE', $uri);
		$this->tpl->assign('REQUEST_URI', "$protocol://{$_SERVER['SERVER_NAME']}{$_SERVER['SCRIPT_NAME']}?request={$_GET['request']}");
		$this->tpl->assign('_SERVER', $_SERVER);
		$this->tpl->assign('CONFIGURATION', $GLOBALS['onxshop_conf']);
		
		$this->tpl->assign('REGISTRY', $this->_getRegistryAsArray());
		
		$this->tpl->assign('_SESSION', $_SESSION);
		$this->tpl->assign('_POST', $_POST);
		$this->tpl->assign('_GET', $_GET);
		$this->tpl->assign('GET', $this->GET);
		$this->tpl->assign('TIME', time());
		
	}

	/**
	 * get registry as array
	 * it's better for Xtemplate
	 */
	 
	function _getRegistryAsArray() {
	
		$r = Zend_Registry::getInstance();
		$registry = array();
		foreach ($r as $index => $value) {
			$registry[$index] = $value;
		}
		return $registry;
		
	}

	
} // end of Onxshop

/**
 * compatibility nSite class
 */
 
class nSite {

	/**
	 * Construct
	 */
	 
	public function __construct($request, &$subOnxshop = false) {
		
		$classname = $this->_prepareCallBack($request);
		//Yes, we can do this in PHP :)
		$this->Onxshop = new $classname($request, $subOnxshop);
		return $this->Onxshop;
	}
	
	/**
	 * get content
	 */
	
	public function getContent() {
	
		return $this->Onxshop->getContent();
		
	}
	
	
	/**
	 * prepare CallBack function
	 */
	
	private function _prepareCallBack($request) {
	
		$file = preg_replace("/([A-Za-z0-9_\/]*).*/", "\\1", $request);
		if (file_exists(ONXSHOP_DIR . "controllers/{$file}.php") || file_exists(ONXSHOP_PROJECT_DIR . "controllers/{$file}.php")) {
			require_once("controllers/{$file}.php");
			$classname = "Onxshop_Controller_" . preg_replace("/\//", "_", $file);
		} else {
			$classname = "Onxshop_Controller";
		}
		
		return $classname;
		
	}
	
}
