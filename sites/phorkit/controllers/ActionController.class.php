<?php
	require_once('SiteController.class.php');
	
	/**
	 * ActionController.class.php
	 * 
	 * This controller handles all the common GET and PUT
	 * actions and verifies the token before dispatching 
	 * the request to the API controller.
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
	class ActionController extends CoreControllerLite {
		
		protected $strMethodPrefix = 'handle';
		protected $blnUseToken = false;
		protected $strAction;
		
		
		/**
		 * This is called from the bootstrap. It verifies the
		 * data, calls the API method and handles the response.
		 * After the process has run the user is redirected
		 * back to where they made the request from. The action
		 * URL can contain /method=[get|post|put|delete]/ but
		 * only POST has special handling. The others use GET.
		 * Any GET params are converted to POST data when using
		 * POST.
		 *
		 * @access public
		 */
		public function run() {
			if ($strHistorySessionName = AppConfig::get('HistorySessionName')) {
				if (!empty($_SESSION[$strHistorySessionName])) {
					$strRedirectUrl = end($_SESSION[$strHistorySessionName]);
				}
			}
			
			AppLoader::includeUtility('ActionHelper');
			if ($this->verifyGet() && ActionHelper::verifyUrl()) {
				$objUrl = AppRegistry::get('Url');
				
				$strApiUrl = str_replace('/action/', '/api/', $objUrl->getUrl());
				if (substr($strApiUrl, -1) == '/') {
					$strApiUrl = substr($strApiUrl, 0, -1);
				}
				$strApiUrl .= '.json';
				
				if ($strMethod = strtolower($objUrl->getFilter('method'))) {
					$strApiUrl = str_replace("/method={$strMethod}", '', $strApiUrl);
				}
						
				if ($strMethod == 'post') {
					$mxdApiUrl = array($strApiUrl, $_GET);
				} else {
					$mxdApiUrl = $strApiUrl . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : null);
				}
				
				AppLoader::includeUtility('ApiHelper');
				if ($arrAlerts = ApiHelper::getResultNode($mxdApiUrl, 'alerts')) {
					CoreAlert::alert(implode(' ', array_reverse($arrAlerts)), true);
				} else {
					CoreAlert::alert(AppLanguage::translate('Oops! There was a problem with your request.'), true);
				}
			} else {
				trigger_error(AppLanguage::translate('Invalid token'));
				$this->error();
			}
			
			AppDisplay::getInstance()->appendHeader('location: ' . (isset($strRedirectUrl) ? $strRedirectUrl : '/'));
		}
		
		
		/**
		 * Verifies GETs by making sure that any required
		 * fields exist. Also verifies the request token
		 * which should be in the query string if the token
		 * verification is in use. Tokens should not be used
		 * with page caching.
		 *
		 * @access protected
		 * @return boolean True if valid
		 */
		protected function verifyGet($arrRequired = array()) {
			if (!empty($_GET)) {
				foreach ($arrRequired as $strRequired) {
					if (!array_key_exists($strRequired, $_GET)) {
						$blnFailed = true;
						break;
					}
				}
				
				if ($this->blnUseToken) {
					AppLoader::includeUtility('Token');
					$blnFailed = !Token::verifyRequest(true);
				}
						
				return empty($blnFailed);
			}
		}
	}