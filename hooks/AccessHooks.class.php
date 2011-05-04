<?php
	require_once('php/core/CoreObject.class.php');
	
	/**
	 * AccessHooks.class.php
	 * 
	 * A collection of hooks to handle logging a user
	 * in and out.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage hooks
	 */
	class AccessHooks extends CoreObject {
		
		protected $strLoginUrl;
	
	
		/**
		 * Handles logging the user in via cookie or form
		 * post, and logs the user out as necessary. If the
		 * login URL is set the redirection after login will
		 * try not to skip the URL and go back to the previous
		 * URL. Catches any exceptions thrown because this can
		 * be called at any time and shouldn't be considered
		 * fatal.
		 *
		 * @access public
		 * @param string $strLoginUrl The URL to the login page
		 */
		public function handleAccess($strLoginUrl = null) {
			try {
				$this->strLoginUrl = $strLoginUrl;
				
				AppLoader::includeUtility('UserLoginPlus');
				AppRegistry::register('UserLogin', $objUserLogin = new UserLoginPlus());
				
				//the API calls use their own login system
				if (AppConfig::get('ApiCall', false)) {
					return;
				}
				
				//check the user's access and handle automatic login via cookie
				if (!$objUserLogin->isLoggedIn()) {
					if ($objUserLogin->hasCookie() && $objUserLogin->handleCookieLogin()) {
						$this->afterLogin(false);
					}
				}
				
				//handle any login requests
				if (!empty($_POST[AppConfig::get('LoginFlag')])) {
					$strLoginUsernameField = AppConfig::get('LoginUsernameField');
					$strLoginPasswordField = AppConfig::get('LoginPasswordField');
					
					if (!empty($_POST[$strLoginUsernameField]) && !empty($_POST[$strLoginPasswordField])) {
						if ($objUserLogin->handleFormLogin($_POST[$strLoginUsernameField], $_POST[$strLoginPasswordField])) {
							$this->afterLogin(true);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing username and/or password'));
					}
				}
				
				//handle a possible facebook connect login
				if (!$objUserLogin->isLoggedIn()) {
					if ($objUserLogin->handleFacebookLogin()) {
						$this->afterLogin(true);
					}
				}
				
				//handle a possible twitter connect login
				if (!$objUserLogin->isLoggedIn()) {
					if ($objUserLogin->handleTwitterLogin()) {
						$this->afterLogin(true);
					}
				}
				
				//handle a logout request
				if (AppRegistry::get('Url')->getVariable(AppConfig::get('LogoutFlag'))) {
					$arrSessionBackup = $_SESSION;
					$objUserLogin->handleLogout();
					$this->afterLogout($arrSessionBackup);
				}
			} catch (Exception $objException) {
				trigger_error($objException->getMessage());
			}
		}
		
		
		/**
		 * Handles anything that needs doing after logging in
		 * like setting additional session data or redirecting
		 * the user. Uses the trackHistory() hook to redirect
		 * the user to where they came from. Also sets up a
		 * welcome message. If a login page URL has been set
		 * and the redirect URL is the login page it will go
		 * back a page to redirect.
		 *
		 * @access protected
		 * @param boolean $blnActiveLogin This is set if a user actively logged in via POST, rather than cookie
		 */
		protected function afterLogin($blnActiveLogin = false) {		
			if (($objUserRecord = AppRegistry::get('UserLogin')->getUserRecord()) && ($intUserId = $objUserRecord->get('__id'))) {
				if ($blnActiveLogin) {
					CoreAlert::alert(AppLanguage::translate('Welcome back, %s!', $objUserRecord->get('displayname')), true);
					if ($strHistorySessionName = AppConfig::get('HistorySessionName')) {
						if (!empty($_SESSION[$strHistorySessionName])) {
							$arrHistoryUrls = $_SESSION[$strHistorySessionName];
							while ($strRedirectUrl = array_pop($arrHistoryUrls)) {
								if ($strRedirectUrl != $this->strLoginUrl) {
									break;
								}
							}
						}
					}
				} else {
					$strRedirectUrl = AppRegistry::get('Url')->getCurrentUrl(false);
				}
				
				if (AppLoader::includeModel('UserLogModel')) {
					$objUserLog = new UserLogModel();
					$objUserLog->import(array(
						'userid'		=> $intUserId,
						'site'			=> AppConfig::get('SiteTitle'),
						'method'		=> AppRegistry::get('UserLogin')->getLoginMethod(),
						'ipaddr'		=> $_SERVER['REMOTE_ADDR']
					));
					$objUserLog->save();
				}
			} else {
				CoreAlert::alert(AppLanguage::translate('There was a problem completing the login process.'), true);
			}
			
			if (!AppConfig::get('Overlay', false)) {
				AppDisplay::getInstance()->appendHeader('location: ' . (!empty($strRedirectUrl) ? $strRedirectUrl : '/'));
				exit;
			}
		}
		
		
		/**
		 * Handles anything that needs doing after logging out
		 * like redirecting the user. Uses the trackHistory() 
		 * hook to redirect the user to where they came from.
		 *
		 * @access protected
		 * @param array $arrSessionBackup The session data before the user was logged out
		 */
		protected function afterLogout($arrSessionBackup) {			
			if ($strHistorySessionName = AppConfig::get('HistorySessionName')) {
				if (!empty($arrSessionBackup[$strHistorySessionName])) {
					$strRedirectUrl = end($arrSessionBackup[$strHistorySessionName]);
				}
			}
			
			AppDisplay::getInstance()->appendHeader('location: ' . (!empty($strRedirectUrl) ? $strRedirectUrl : '/'));
			exit;
		}
	}