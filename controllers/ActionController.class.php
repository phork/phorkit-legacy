<?php
	require_once('SiteController.class.php');
	
	/**
	 * ActionController.class.php
	 * 
	 * This controller handles running GET, POST, PUT 
	 * and DELETE requests based on a GET URL structure.
	 * This is used in conjuction with the ActionHelper
	 * utility to generate safe URLs so any API call can
	 * be made directly from a link. The URL is considered
	 * safe because it includes a time-sensitive token
	 * which is used to verify that the request was not
	 * spoofed in a cross site request forgery attempt.
	 *
	 * The API calls should return an array of alerts
	 * stating what the action was and its result.
	 *
	 * <code>
	 *	$strUrl = ActionHelper::generateUrl(
	 *		$strBaseUrl . '/action/method=put/result=refresh/users/approve/friend/' . $strUsername . '/'
	 *	);
	 * </code>
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
	class ActionController extends CoreControllerLite {
		
		protected $strMethodPrefix = 'handle';
		protected $blnUseToken = false;
		protected $strAction;
		
		protected $blnSuccess;
		protected $arrResult;
		protected $intStatusCode;
		
		protected $arrRequired;
		
		
		/**
		 * Includes the helper utilities.
		 *
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			
			AppLoader::includeUtility('ActionHelper');
			AppLoader::includeUtility('ApiHelper');
		}
		
		
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
			$objUrl = AppRegistry::get('Url');
			
			$strApiUrl = str_replace('/action/', '/api/', $objUrl->getUrl());
			if (substr($strApiUrl, -1) == '/') {
				$strApiUrl = substr($strApiUrl, 0, -1);
			}
			$strApiUrl .= '.json';
			
			if ($strMethod = strtolower($objUrl->getFilter('method'))) {
				$strApiUrl = str_replace("/method={$strMethod}", '', $strApiUrl);
			}
			
			if ($strResult = strtolower($objUrl->getFilter('result'))) {
				$strApiUrl = str_replace("/result={$strResult}", '', $strApiUrl);
			}
			
			if ($this->verifyRequest() && ActionHelper::verifyUrl()) {
				switch ($strMethod) {
					case 'get':
						$arrResult = ApiHelper::get($strApiUrl . (!empty($_GET) ? '?' . http_build_query($_GET) : null), false);
						break;
						
					case 'post':
						$arrResult = ApiHelper::post($strApiUrl, $_GET, false);
						break;
						
					case 'put':
						$arrResult = ApiHelper::put($strApiUrl, false);
						break;
						
					case 'delete':
						$arrResult = ApiHelper::delete($strApiUrl, false);
						break;
				}
				
				if (!empty($arrResult)) {
					list(
						$this->blnSuccess, 
						$this->arrResult, 
						$this->intStatusCode
					) = $arrResult;
				}
			} else {
				trigger_error(AppLanguage::translate('Invalid token'));
				$this->error();
			}
			
			$this->strContent = $strResult ? $strResult : 'refresh';
			$this->display();
		}
		
		
		/**
		 * Verifies requests by making sure that any required
		 * fields exist. Also verifies the request token if the
		 * token verification is in use. Tokens should not be
		 * used with page caching.
		 *
		 * @access protected
		 * @return boolean True if valid
		 */
		protected function verifyRequest() {
			if ($arrVars = AppRegistry::get('Url')->getVariables()) {
				if (!empty($this->arrRequired)) {
					foreach ($this->arrRequired as $strRequired) {
						if (!array_key_exists($strRequired, $arrVars)) {
							$blnFailed = true;
							break;
						}
					}
				}
				
				if ($this->blnUseToken) {
					AppLoader::includeUtility('Token');
					$blnFailed = !Token::verifyRequest();
				}
			}
			return empty($blnFailed);
		}
		
		
		/*****************************************/
		/**     HANDLER METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Sets up a sticky alert with the alerts from the API call
		 * and redirects the user back to the page that the request
		 * came from.
		 *
		 * @access protected
		 */
		protected function handleRefresh() {
			if (!empty($this->arrResult['errors'])) {
				CoreAlert::alert(implode(' ', $this->arrResult['errors']), true);
			}
			
			if (!empty($this->arrResult['alerts'])) {
				CoreAlert::alert(implode(' ', array_reverse($this->arrResult['alerts'])), true);
			} else {
				CoreAlert::alert(AppLanguage::translate('Oops! There was a problem with your request.'), true);
			}
			
			if ($strHistorySessionName = AppConfig::get('HistorySessionName')) {
				if (!empty($_SESSION[$strHistorySessionName])) {
					$strRedirectUrl = end($_SESSION[$strHistorySessionName]);
				}
			}
			AppDisplay::getInstance()->appendHeader('location: ' . (isset($strRedirectUrl) ? $strRedirectUrl : '/'));	
		}
	}