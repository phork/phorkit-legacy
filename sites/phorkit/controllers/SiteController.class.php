<?php
	require_once('php/core/CoreController.class.php');
	
	/**
	 * SiteController.class.php
	 * 
	 * This is the base controller for the public site which
	 * consists of several different separately cacheable
	 * nodes defined in the $arrNodeOrder property.
	 *
	 * In addition to the regular templates there are themed
	 * templates. Themed templates are used to override the
	 * standard templates if a design calls for a different look.
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
	class SiteController extends CoreController {
		
		protected $blnLoggedIn;
		protected $intUserId;
		protected $intUserRoles;
		protected $strUsername;
		
		protected $strThemeDir;
		protected $strThemeCssDir;
		protected $strThemeJsDir;
		
		protected $arrNodeOrder = array('header', 'nav', 'errors', 'alerts', 'content', 'footer');
				
		
		/**
		 * Sets up the common page variables to be used
		 * across all node templates.
		 * 
		 * @access public
		 */
		public function __construct() {
			AppConfig::get('NodeCacheEnabled', false) || $this->setNoCache(true);
			parent::__construct();
			
			AppLoader::includeUtility('ApiHelper');
			AppLoader::includeUtility('Form');
			AppLoader::includeUtility('Date');
			
			if ($objUserLogin = AppRegistry::get('UserLogin', false)) {
				if ($this->blnLoggedIn = $objUserLogin->isLoggedIn()) {
					$objUserRecord = $objUserLogin->getUserRecord();
					
					$this->intUserId = $objUserLogin->getUserId();
					$this->intUserRoles = $objUserRecord->get('roles');
					$this->strUsername = $objUserRecord->get('username');
					
					if (!is_null($objUserRecord->get('timezone'))) {
						Date::setClientOffset($objUserRecord->get('timezone'));
					}
				}
			}
			
			$this->assignPageVar('blnLoggedIn', $this->blnLoggedIn);
			$this->assignPageVar('intUserId', $this->intUserId);
			$this->assignPageVar('strUsername', $this->strUsername);
			
			$this->assignPageVar('strBaseUrl', AppConfig::get('BaseUrl'));
			$this->assignPageVar('strPageTitle', $strSiteTitle = AppConfig::get('SiteTitle'));
			$this->assignPageVar('strSiteTitle', $strSiteTitle);
			$this->assignPageVar('strTheme', $strTheme = AppConfig::get('Theme'));
			
			$this->strThemeDir = ($strTheme ? "themes/{$strTheme}/" : '');
			$this->strThemeCssDir = '/css/' . $this->strThemeDir;
			$this->strThemeJsDir = '/js/' . $this->strThemeDir;
		}
		
		
		/**
		 * This is called from the bootstrap and it handles
		 * any necessary processing before calling the display
		 * method. Generally this sets up the type of content
		 * to display based on the URL. The bootstrap determines
		 * whether an overlay or insert method should be used,
		 * in which case only certain nodes are displayed.
		 *
		 * @access public
		 */
		public function run() {
			if (!($this->strContent = AppRegistry::get('Url')->getSegment(1))) {
				$this->strContent = 'Index';
			}
			
			if (AppConfig::get('Overlay')) {
				$this->strContent .= 'Overlay';
				$this->setNodeList(array('errors', 'content'));
			} else if (AppConfig::get('Insert')) {
				$this->strContent .= 'Insert';
				$this->setNodeList(array('content'));
			}
			$this->display();
		}
		
				
		/**
		 * Returns the template path for the page templates.
		 * If a theme has an overriding template that path is
		 * returned, otherwise it returns the common path.
		 *
		 * @access protected
		 * @param string $strTemplate The name of the template
		 * @return string The path to the template
		 */
		protected function getTemplatePath($strTemplate) {
			if ($this->strThemeDir && file_exists($strThemeTemplateDir = $this->strTemplateDir . $this->strThemeDir . $strTemplate . '.phtml')) {
				return $strThemeTemplateDir;
			} else {
				return $this->strTemplateDir . $strTemplate . '.phtml';
			}
		}
		
		
		/**
		 * If a user is required to be logged in for a page
		 * calling this will redirect them to the login page.
		 *
		 * @access protected
		 */
		protected function requireLogin() {
			$objDisplay = AppDisplay::getInstance();
			if (AppConfig::get('Overlay')) {
				$objDisplay->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/login/?overlay=1');
			} else {
				CoreAlert::alert(AppLanguage::translate('You must be logged in for that.'), true);
				$objDisplay->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/login/');
			}
		}
		
		
		/**
		 * Generates a token to be used to validate that
		 * a request hasn't been spoofed. To be used with
		 * some GET requests.
		 *
		 * @access public
		 * @return string The token string
		 */
		public function generateTokenString() {
			AppLoader::includeUtility('Token');
			return AppConfig::get('TokenField') . '=' . Token::initToken();
		}
		
		
		/*****************************************/
		/**     INCLUDE METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Includes the pagination template.
		 *
		 * @access public
		 * @param integer $intPage The current page number
		 * @param integer $intPerPage The number of records per page
		 * @param integer $intTotalItems The number of records to paginate
		 * @param string $strLabel The type of record to display as "X [records]"
		 * @param boolean $blnCollapse Whether to not show pagination if only one page
		 */
		public function includePagination($intPage, $intPerPage, $intTotalItems, $strLabel = null, $blnCollapse = false) {
			AppLoader::includeUtility('SiteUrl');
			$strPaginateUrl = SiteUrl::getPaginateUrlTemplate();
			
			AppLoader::includeUtility('Pagination');
			$objPagination = new Pagination($intPage, $intTotalItems, $intPerPage);
			
			if (!$blnCollapse || !($objPagination->getTotalPages() == 1 && $objPagination->getCurrentPage() == 1)) {
				$this->includeTemplateFile($this->getTemplatePath('common/pagination'), array(
					'objPagination'		=> $objPagination,
					'strPaginateUrl'	=> $strPaginateUrl,
					'strLabel'			=> $strLabel
				));
			}
		}
		
		
		/**
		 * Includes a common template that doesn't warrant its
		 * own specific include method.
		 *
		 * @access public
		 * @param string $strFile The template file relative to the common dir
		 * @param array $arrPageVars The variables to pass on to the template
		 */
		public function includeCommon($strFile, $arrPageVars = array()) {
			$this->includeTemplateFile($this->getTemplatePath('common/' . $strFile), $arrPageVars);
		}
		
		
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Displays the navigation template.
		 *
		 * @access protected
		 */
		protected function displayNav() {
			$this->displayNode('nav', $this->getTemplatePath('common/nav'));
		}
		
		
		/**
		 * Displays the index page and passes the file
		 * data to it.
		 *
		 * @access protected
		 */
		protected function displayIndex() {
			$this->displayNode('content', $strTemplatePath = $this->getTemplatePath('index'), array(
				'strControllerPath'	=> __FILE__,
				'strController'		=> __CLASS__,
				'strDisplayMethod'	=> $this->strMethodPrefix . $this->strContent,
				'strTemplatePath'	=> $strTemplatePath
			));
		}
				
		
		/**
		 * Handles the logout page which just redirects
		 * to the current page with the logout flag set.
		 *
		 * @access protected
		 */
		protected function displayLogout() {
			AppDisplay::getInstance()->appendHeader('location: ' . AppRegistry::get('Url')->getCurrentUrl(false, false) . '?' . AppConfig::get('LogoutFlag') . '=1');
			exit;
		}
		
		
		/**
		 * Permanently redirects the user to a new location
		 * determined by the routed URL. The route should be
		 * in the format /site/redirect/[controller]/[method]/status=301/
		 * where method defaults to index if it's left out
		 * and the status is optional.
		 *
		 * @access protected
		 */
		protected function displayRedirect() {
			$objUrl = AppRegistry::get('Url');
			if ($arrSegments = array_slice($objUrl->getSegments(), 2)) {
				$strLocation = implode('/', $arrSegments) . '/';
			}
			
			if (isset($strLocation)) {
				$objDisplay = AppDisplay::getInstance();
				if ($objUrl->getFilter('status') == 301) {
					$objDisplay->setStatusCode(301);
				}
				$objDisplay->appendHeader('Location: ' . AppConfig::get('BaseUrl') . '/' . $strLocation);
			} else {
				$this->error(404);
			}
		}
	}