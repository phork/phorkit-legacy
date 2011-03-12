<?php
	require_once('php/utilities/UserLogin.class.php');
	
	/**
	 * UserLoginConnect.class.php
	 * 
	 * Determines whether a user is logged in or not and
	 * handles the log in and log out process. This has
	 * additional handling to connect to external services
	 * like Facebook and Twitter and to log all log ins.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage utilities
	 */
	class UserLoginPlus extends UserLogin {
	
		protected $strFacebookSessionName;
		protected $strTwitterSessionName;
		
		const LOGIN_METHOD_FACEBOOK = 'facebook';
		const LOGIN_METHOD_TWITTER = 'twitter';
		
		
		/**
		 * The constructor includes the user model for when
		 * when the user record is unserialized.
		 *
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			
			$this->strFacebookSessionName = AppConfig::get('FacebookSessionName');
			$this->strTwitterSessionName = AppConfig::get('TwitterSessionName');
		}
		
		
		/*****************************************/
		/**      LOGIN METHODS                  **/
		/*****************************************/
		
		
		/**
		 * When all login data has been verified this logs
		 * the user in by setting the necessary cookie and
		 * session data. Has additional handling to connect
		 * up any external accounts.
		 *
		 * @access public
		 * @param object $objUser The user model containing the user data of the user to login
		 * @return boolean True
		 */
		public function handleLogin($objUser) {
			$intUserId = $objUser->first()->get('__id');	
			$this->connectAccounts($intUserId);
			
			return parent::handleLogin($objUser);
		}
		
		
		/**
		 * Logs the user in using Facebook Connect. If the
		 * user was logged in successfully but their account
		 * isn't tied to an internal account this will redirect
		 * them to the page to join the accounts. The redirect
		 * will only happen if cookie support is turned off
		 * otherwise this will force them to connect their
		 * account infinitely until it's done.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function handleFacebookLogin() {
			if ($this->strFacebookSessionName) {
				if (($strUrl = AppRegistry::get('Url')->getUrl()) == '/account/connect/facebook/') {
					if (!empty($_GET['denied'])) {
						AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/signup/');
						exit;
					}
					
					$this->strLoginMethod = self::LOGIN_METHOD_FACEBOOK;
					AppLoader::includeUtility('FacebookConnect');
					if ($objRecord = FacebookConnect::handleConnection()) {
						$_SESSION[$this->strFacebookSessionName] = $objRecord->get('__id');
						if ($intUserId = $objRecord->get('userid')) {
							if (($objUserIterator = $objRecord->get('user')) && ($objUserRecord = $objUserIterator->first())) {
								$objUser = clone $this->getUserModel(true);
								$objUser->append($objUserRecord);
							
								return $this->handleLogin($objUser);
							} else {
								trigger_error(AppLanguage::translate('There was an error loading your account information'));
							}
						} else {
							AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/facebook/');
							exit;
						}
					}
				}
			}
			return false;
		}
		
		
		/**
		 * Logs the user in using Twitter Connect. If the
		 * user was logged in successfully but their account
		 * isn't tied to an internal account this will redirect
		 * them to the page to join the accounts.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function handleTwitterLogin() {
			if ($this->strTwitterSessionName) {
				if (($strUrl = AppRegistry::get('Url')->getUrl()) == '/account/connect/twitter/') {
					if (!empty($_GET['denied'])) {
						AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/signup/');
						exit;
					}
					
					$this->strLoginMethod = self::LOGIN_METHOD_TWITTER;
					AppLoader::includeUtility('TwitterConnect');
					if ($objRecord = TwitterConnect::handleConnection()) {
						$_SESSION[$this->strTwitterSessionName] = $objRecord->get('__id');
						if ($intUserId = $objRecord->get('userid')) {
							if (($objUserIterator = $objRecord->get('user')) && ($objUserRecord = $objUserIterator->first())) {
								$objUser = clone $this->getUserModel(true);
								$objUser->append($objUserRecord);
							
								return $this->handleLogin($objUser);
							} else {
								trigger_error(AppLanguage::translate('There was an error loading your account information'));
							}
						} else {
							AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/twitter/');
							exit;
						}
					}
				}
			}
			return false;
		}
		
		
		/**
		 * Connects the user's account with their Facebook
		 * and/or Twitter accounts if they have a Facebook
		 * or Twitter ID in their session.
		 *
		 * @access protected
		 * @param integer $intUserId The user ID to connect the account with
		 * @return boolean True on success
		 */
		protected function connectAccounts($intUserId) {
			if (!empty($_SESSION[$this->strFacebookSessionName])) {
				AppLoader::includeModel('FacebookModel');
				$objFacebook = new FacebookModel();
				if ($objFacebook->loadById($_SESSION[$this->strFacebookSessionName]) && $objFacebookRecord = $objFacebook->first()) {
					if (!$objFacebookRecord->get('userid')) {
						$objFacebookRecord->set('userid', $intUserId);
						return $objFacebook->save();
					}
				}
			}
			
			if (!empty($_SESSION[$this->strTwitterSessionName])) {
				AppLoader::includeModel('TwitterModel');
				$objTwitter = new TwitterModel();
				if ($objTwitter->loadById($_SESSION[$this->strTwitterSessionName]) && $objTwitterRecord = $objTwitter->first()) {
					if (!$objTwitterRecord->get('userid')) {
						$objTwitterRecord->set('userid', $intUserId);
						return $objTwitter->save();
					}
				}
			}
		}
		
		
		/*****************************************/
		/**      GET & SET METHODS              **/
		/*****************************************/
		
		
		/**
		 * Returns the ID from the user's Facebook Connect
		 * record from the session. This is not their Facebook
		 * ID, but the primary key in the facebook table.
		 *
		 * @access public
		 * @return integer The facebook ID
		 */
		public function getFacebookId() {
			if (empty($_SESSION[$this->strFacebookSessionName]) && $intUserId = $this->getUserId()) {
				AppLoader::includeModel('FacebookModel');
				$objFacebook = new FacebookModel();
				if ($objFacebook->loadByUserId($intUserId) && $objFacebookRecord = $objFacebook->first()) {
					$_SESSION[$this->strFacebookSessionName] = $objFacebookRecord->get('__id');
				}
			}
		
			if (!empty($_SESSION[$this->strFacebookSessionName])) {
				return $_SESSION[$this->strFacebookSessionName];
			}
		}
		
		
		/**
		 * Clears the user's Facebook ID if it's been deleted.
		 *
		 * @access public
		 */
		public function clearFacebookId() {
			$_SESSION[$this->strFacebookSessionName] = null;
		}
		
		
		/**
		 * Returns the ID from the user's Twitter Connect
		 * record from the session. This is not their Twitter
		 * ID, but the primary key in the twitter table.
		 *
		 * @access public
		 * @return integer The twitter ID
		 */
		public function getTwitterId() {
			if (empty($_SESSION[$this->strTwitterSessionName]) && $intUserId = $this->getUserId()) {
				AppLoader::includeModel('TwitterModel');
				$objTwitter = new TwitterModel();
				if ($objTwitter->loadByUserId($intUserId) && $objTwitterRecord = $objTwitter->first()) {
					$_SESSION[$this->strTwitterSessionName] = $objTwitterRecord->get('__id');
				}
			}
			
			if (!empty($_SESSION[$this->strTwitterSessionName])) {
				return $_SESSION[$this->strTwitterSessionName];
			}
		}
		
		
		/**
		 * Clears the user's Twitter ID if it's been deleted.
		 *
		 * @access public
		 */
		public function clearTwitterId() {
			$_SESSION[$this->strTwitterSessionName] = null;
		}
	}