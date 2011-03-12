<?php
	require_once('SiteController.class.php');
	
	/**
	 * MiscController.class.php
	 * 
	 * This controller handles all the miscellaneous pages
	 * of the site that are, for the most part, static.
	 * 
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage controllers
	 */
	class MiscController extends SiteController {
		
		/**
		 * Sets up the common page variables to be used
		 * across all node templates.
		 * 
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			
			$this->assignPageVar('strBodyClass', 'misc');
			$this->assignPageVar('arrStylesheets', array(
				AppConfig::get('CssUrl') . $this->strThemeCssDir . 'misc.css'
			));
		}
		
		
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Displays the about us page.
		 *
		 * @access protected
		 */
		protected function displayAbout() {
			$this->assignPageVar('strHeaderTitle', $strTitle = 'About ' . AppConfig::get('SiteTitle'));
			$this->assignPageVar('strPageTitle', $strTitle);
			$this->displayNode('content', $this->getTemplatePath('misc/about'));
		}
		
		
		/**
		 * Displays the contact us page.
		 *
		 * @access protected
		 */
		protected function displayContact() {
			$this->assignPageVar('strHeaderTitle', 'Contact ' . AppConfig::get('SiteTitle'));
			$this->assignPageVar('strPageTitle', 'Contact Us');	
			$this->displayNode('content', $this->getTemplatePath('misc/contact'));
		}
		
		
		/**
		 * Displays the FAQ and help page.
		 *
		 * @access protected
		 */
		protected function displayHelp() {
			$this->assignPageVar('strHeaderTitle', AppConfig::get('SiteTitle') . ' FAQ and Help');
			$this->assignPageVar('strPageTitle', 'FAQ and Help');	
			$this->displayNode('content', $this->getTemplatePath('misc/help'));
		}
		
		
		/**
		 * Displays the terms and conditions page.
		 *
		 * @access protected
		 */
		protected function displayTerms() {
			$this->assignPageVar('strHeaderTitle', AppConfig::get('SiteTitle') . ' Terms and Conditions');
			$this->assignPageVar('strPageTitle', 'Terms and Conditions');	
			$this->displayNode('content', $this->getTemplatePath('misc/terms'));
		}
		
		
		/**
		 * Displays the privacy policy page.
		 *
		 * @access protected
		 */
		protected function displayPrivacy() {
			$this->assignPageVar('strHeaderTitle', AppConfig::get('SiteTitle') . ' Privacy Policy');
			$this->assignPageVar('strPageTitle', 'Privacy Policy');	
			$this->displayNode('content', $this->getTemplatePath('misc/privacy'));
		}
	}