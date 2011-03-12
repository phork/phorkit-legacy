<?php
	require_once('php/core/CoreObject.class.php');
	
	/**
	 * TwitterConnect.class.php
	 * 
	 * Handles connecting a Twitter account to a user's
	 * Phorkit account.
	 *
	 * Requirements:
	 * Zend_Oauth_Consumer
	 * Zend_Oauth_Token_Access
	 * Zend_Service_Twitter
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
	class TwitterConnect extends CoreObject {
		
		/**
		 * Returns the oAuth config options.
		 *
		 * @access public
		 * @return array The array of oAuth config options
		 * @static
		 */
		static protected function getOauthConfig() {
			if (!AppConfig::get('TwitterConsumerKey', false)) {
				AppConfig::load('twitter');
			}	
		
			return array(
				'callbackUrl'		=> AppConfig::get('SiteUrl') . AppConfig::get('BaseUrl') . '/account/connect/twitter/',
				'siteUrl'			=> 'http://twitter.com/oauth',
			    'requestTokenUrl'	=> 'http://twitter.com/oauth/request_token',
			    'authorizeUrl'		=> 'http://twitter.com/oauth/authorize',
			    'accessTokenUrl'	=> 'http://twitter.com/oauth/access_token',
			    'consumerKey'		=> AppConfig::get('TwitterConsumerKey'),
			    'consumerSecret'	=> AppConfig::get('TwitterConsumerSecret')
			);
		}
		
		
		/**
		 * Attempts to load the Twitter Connect object from
		 * the registry and if it doesn't exist will create
		 * and register it.
		 *
		 * @access public
		 * @return object The connect object
		 * @static
		 */
		static public function getConnectObject() {
			if (!($objConnect = AppRegistry::get('TwitterConnect', false))) {
				AppLoader::includeExtension('zend/', 'ZendLoader');
				ZendLoader::includeClass('Zend_Oauth_Consumer');
				AppRegistry::register('TwitterConnect', $objConnect = new Zend_Oauth_Consumer(self::getOauthConfig()));
			}
			return $objConnect;
		}
		
		
		/**
		 * Returns an object to use with API calls. This is
		 * not the same as the connect object, which is only
		 * used for connections.
		 *
		 * @access public
		 * @return object The API object
		 */
		static public function getApiObject() {
			AppLoader::includeExtension('zend/', 'ZendLoader');
			ZendLoader::includeClass('Zend_Oauth_Token_Access');
			
			if (empty($_SESSION['_tat'])) {
				if ($objUserLogin = AppRegistry::get('UserLogin', false)) {
					if ($intUserId = $objUserLogin->getUserId()) {
						AppLoader::includeModel('TwitterModel');
						$objTwitter = new TwitterModel();
						if ($objTwitter->loadByUserId($intUserId) && $objTwitterRecord = $objTwitter->first()) {
							$objAccessToken = new Zend_Oauth_Token_Access();
							$objAccessToken->setParams(array(
								'oauth_token' => $objTwitterRecord->get('token'),
								'oauth_token_secret' => $objTwitterRecord->get('secret'),
								'user_id' => $objTwitterRecord->get('external_id'),
								'screen_name' => $objTwitterRecord->get('username')
							));
							$_SESSION['_tat'] = serialize($objAccessToken);
						}
					}
				}
			} else {
				$objAccessToken = unserialize($_SESSION['_tat']);
			}
			
			if (isset($objAccessToken) && $objAccessToken->isValid()) {
				ZendLoader::includeClass('Zend_Service_Twitter');
				AppLoader::includeExtension('twitter/', 'TwitterServicePlus');
				$objApi = new TwitterServicePlus();
				$objApi->initOAuth($objAccessToken, self::getOauthConfig());
				
				return $objApi;
			}
		}
		
	
		/**
		 * Connects the user's Twitter account with their
		 * phork account. If the user isn't logged in the
		 * next step should be them logging in or registering
		 * a phork account.
		 *
		 * @access public
		 * @return object The twitter record on success
		 * @static
		 */
		static public function handleConnection() {
			try {
				$objConnect = self::getConnectObject();
				$objUserLogin = AppRegistry::get('UserLogin', false);
				if ($objUserLogin->isLoggedIn()) {
					$intUserId = $objUserLogin->getUserId();
				}
			
				if (!empty($_GET) && isset($_SESSION['_trt'])) {
					if (!empty($_GET['denied'])) {
						return;
					}
				
					$objAccessToken = $objConnect->getAccessToken($_GET, unserialize($_SESSION['_trt']));
					$_SESSION['_tat'] = serialize($objAccessToken);
					unset($_SESSION['_trt']);
				
					if ($intExternalId = $objAccessToken->getParam('user_id')) {
						$strUsername = $objAccessToken->getParam('screen_name');
						$strSecret = $objAccessToken->getParam('oauth_token_secret');
						$strToken = $objAccessToken->getParam('oauth_token');
						
						AppLoader::includeModel('TwitterModel');
						$objTwitter = new TwitterModel(array('Relations' => true, 'RelationsAutoLoad' => true));
						if ($objTwitter->loadByExternalId($intExternalId) && $objTwitterRecord = $objTwitter->first()) {
							$objTwitterRecord->set('secret', $strSecret);
							$objTwitterRecord->set('token', $strToken);
						} else {
							if ($objApi = self::getApiObject()) {
								$objResponse = $objApi->account->verifyCredentials();
								if ($objResponse->id) {
									$objTwitter->import(array(
										'externalid'	=> $intExternalId,
										'secret'		=> $strSecret,
										'token'			=> $strToken,
										'username'		=> $strUsername,
										'displayname'	=> $objResponse->name,
										'email'			=> "twitter.{$intExternalId}@place.holder",
										'location'		=> $objResponse->location,
										'url'			=> $objResponse->url,
										'blurb'			=> $objResponse->description,
										'avatar'		=> $objResponse->profile_image_url
									));
									$objTwitterRecord = $objTwitter->current();
								} else {
									trigger_error(AppLanguage::translate('There was an error loading your account information'));
								}
							} else {
								trigger_error(AppLanguage::translate('Missing or invalid Twitter session'));
							}
						}
						
						if ($objTwitterRecord) {
							if (!$objTwitterRecord->get('userid') && !empty($intUserId)) {
								$objTwitterRecord->set('userid', $intUserId);
							}
							
							if ($objTwitter->save()) {
								return $objTwitterRecord;
							} else {
								trigger_error(AppLanguage::translate('There was an error saving your account information'));
							}
						}
					} else {
						trigger_error(AppLanguage::translate('Missing Twitter ID'));
					}
				} else {
					trigger_error(AppLanguage::translate('Invalid Twitter session'));
				}
			} catch (TwitterApiException $objException) {
				trigger_error(AppLanguage::translate('There was a Twitter error: %s', (string) $objException));
			}
		}
		
		
		/**
		 * Deletes the Twitter account from the database.
		 *
		 * @access public
		 * @return boolean True on success
		 * @static
		 */
		static public function deactivateAccount() {
			if ($intTwitterId = AppRegistry::get('UserLogin')->getTwitterId()) {
				AppLoader::includeModel('TwitterModel');
				$objTwitter = new TwitterModel();
				$objTwitter->import(array(
					'__id'		=> $intTwitterId
				));
				return $objTwitter->destroy();
			}
			
			trigger_error(AppLanguage::translate('There was an error deactivating your Twitter account'));
			return false;
		}
		
		
		/**
		 * Posts a status update to Twitter using the oAuth
		 * login credentials.
		 *
		 * @access public
		 * @param string $strStatus The status to post
		 * @param string $strUrl The URL to append to the end of the post, if any
		 * @return boolean True on success
		 * @static
		 */
		static public function postStatus($strStatus, $strUrl = null) {
			if ($strUrl && ($intLength = (strlen($strStatus) + strlen($strUrl) + 1)) > 140) {
				$strStatus = substr($strStatus, 0, 139 - $intLength - 3) . '...';
			}
			
			try {
				if ($objApi = self::getApiObject()) {
					$objResponse = $objApi->status->update($strStatus . ($strUrl ? " {$strUrl}" : ''));
					if ($objResponse->id) {
						return true;
					}
				}
			} catch (Exception $objException) {}
			
			if (is_object($objApi)) {
				$intStatus = $objApi->getLocalHttpClient()->getLastResponse()->getStatus();
				if ($intStatus == 401) {
					self::grantAndPost(__FUNCTION__, array($strStatus, $strUrl));
				} else {
					trigger_error(AppLanguage::translate('There was an error posting to Twitter (%d returned)', $intStatus));
				}
			} else {
				self::grantAndPost(__FUNCTION__, array($strStatus, $strUrl));
				trigger_error(AppLanguage::translate('There was an error posting to Twitter'));
			}
			return false;
		}
		
		
		/**
		 * Requests that the user grant access before a post
		 * can be made. Backs up the data to post so once access
		 * has been granted the post can continue.
		 *
		 * @access protected
		 * @param string $strFunction The function to call to finish the post
		 * @param array $arrFunctionArgs The arguments to post to the post function
		 * @static
		 */
		static protected function grantAndPost($strFunction, $arrFunctionArgs) {
			trigger_error(AppLanguage::translate('Please <a href="%s">grant us access</a> to post to Twitter', AppConfig::get('BaseUrl') . '/account/connect/twitter/'));
			$_SESSION['share']['twitter'][] = array($strFunction, $arrFunctionArgs);	
		}
		
		
		/**
		 * Finds a user's Twitter friends on this site.
		 *
		 * @access public
		 * @return object The local users who are friends on Twitter
		 * @static
		 */
		static public function findFriends() {
			try {
				if ($objApi = self::getApiObject()) {
					AppLoader::includeModel('UserModel');
					$objUser = new UserModel();
					
					do {
						$objResult = $objApi->getFriendIds($objApi->getUsername(), isset($intCursor) ? $intCursor : -1);
						$intCursor = $objResult->next_cursor;
						$intCount = count($objResult->id);
						$arrIds = array();
						
						for ($i = 0; $i < $intCount; $i++) {
							$arrIds[] = (int) $objResult->ids->id[$i];
							
							if ((!($i % 30) || $i == $intCount - 1) && !empty($arrIds)) {
								$objUser->loadByTwitterId($arrIds);
								$arrIds = array();
							}
						}
					} while (!empty($intCursor));
					
					return $objUser;
				} else {
					trigger_error(AppLanguage::translate('Please <a href="%s">grant us access</a> to access your Twitter friends', AppConfig::get('BaseUrl') . '/account/connect/twitter/'));
				}
			} catch (Exception $objException) {
				trigger_error(AppLanguage::translate('There was an error loading your Twitter friends'));
			}
		}
	}