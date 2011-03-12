<?php
	/**
	 * TwitterServicePlus.class.php
	 * 
	 * Extension of Zend's Twitter client with additional
	 * support for oAuth login and to retrieve friend IDs.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage external
	 */
	class TwitterServicePlus extends Zend_Service_Twitter {
		
		protected $objAccessToken;
	
	
		/**
		 * Initializes the oAuth login rather than using
		 * a username and password.
		 *
		 * @access public
		 * @param object $objAccessToken The Zend_Oauth_Token_Access object 
		 * @param array $arrOauthConfig The array of oAuth config data
		 */
		public function initOAuth(Zend_Oauth_Token_Access $objAccessToken, array $arrOauthConfig) {
			self::setHttpClient($objAccessToken->getHttpClient($arrOauthConfig));
			self::__construct($objAccessToken->getParam('screen_name'), null);
			$this->objAccessToken = $objAccessToken;
			$this->_authInitialized = true;
		}
		
		
		/**
		 * Returns the access token object.
		 *
		 * @access public
		 * @return object The access token
		 */
		public function getAccessToken() {
			return $this->objAccessToken;
		}
		
		
		/*****************************************/
		/**     GET METHODS                     **/
		/*****************************************/
		
		
		/**
	     * Returns a list of user IDs of users who are friends
	     * with the user.
	     *
	     * @param integer|string $mxdUser Id or username of user for whom to fetch friends
	     * @param integer $intCursor The cursor to get the next page of users with
	     * @throws Zend_Http_Client_Exception if HTTP request fails or times out
	     * @return Zend_Rest_Client_Result
	     */
	    public function getFriendIds($mxdUser, $intCursor = -1) {
			$this->_init();
			$strPath = '/friends/ids/' . $mxdUser . '.xml';
			$objResponse = $this->_get($strPath, array('cursor' => $intCursor));
			return new Zend_Rest_Client_Result($objResponse->getBody());
	    }
	}