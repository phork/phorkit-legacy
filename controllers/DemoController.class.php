<?php
	require_once('SiteController.class.php');
	
	/**
	 * DemoController.class.php
	 * 
	 * This controller handles all the demonstration pages.
	 * 
	 * Copyright 2006-2011, Phork Labs. (http://www.phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage controllers
	 */
	class DemoController extends SiteController {
		
		/**
		 * Sets up the common page variables to be used
		 * across all node templates, including the styles
		 * and javascript.
		 * 
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			
			$this->assignPageVar('strBodyClass', 'demo');
			$this->assignPageVar('arrStylesheets', array(
				AppConfig::get('CssUrl') . $this->strThemeCssDir . 'demo.css'
			));	
			$this->assignPageVar('arrJavascript', array(
				AppConfig::get('JsUrl') . $this->strThemeJsDir . 'demo.js',
				AppConfig::get('JsUrl') . $this->strThemeJsDir . 'user.js'
			));
		}
		
		
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Displays the main demo page.
		 *
		 * @access protected
		 */
		protected function displayIndex() {
			list($blnResult, $arrUsers) = ApiHelper::get('/api/users/include=extended/filter/by=id/' . AppConfig::get('SystemBotUserId') . '.json');
		
			$this->displayNode('content', $this->getTemplatePath('demo/index'), array(
				'strApiUrl'	=> AppConfig::get('ApiUrl'),
				'arrUser'	=> !empty($arrUsers['users']) ? $arrUsers['users'][0] : null
			));
		}
	}