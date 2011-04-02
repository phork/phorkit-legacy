<?php
	require_once('php/core/CoreStatic.class.php');
	
	/**
	 * FacebookConnect.class.php
	 * 
	 * Handles connecting a Facebook account to a user's
	 * Phorkit account.
	 *
	 * Requirements:
	 * https://github.com/facebook/php-sdk/
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
	class FacebookConnect extends CoreStatic {
	
		/**
		 * Attempts to load the Facebook Connect object from
		 * the registry and if it doesn't exist will create
		 * and register it.
		 *
		 * @access public
		 * @return object The connect object
		 * @static
		 */
		static public function getConnectObject() {
			if (!($objConnect = AppRegistry::get('FacebookConnect', false))) {
				$arrConfig = AppConfig::load('facebook');
				AppLoader::includeExtension('facebook/', 'facebook', true);
				AppRegistry::register('FacebookConnect', $objConnect = new Facebook(array(
					'appId'		=> $arrConfig['FacebookAppId'],
					'secret'	=> $arrConfig['FacebookAppSecret'],
					'cookie'	=> false
				)));
			}
			return $objConnect;
		}
		
		
		/**
		 * Returns an object to use with API calls. This is
		 * essentially the connect object but with the session
		 * set either from the session or restored from the
		 * database.
		 *
		 * @access public
		 * @return object The API object
		 */
		static public function getApiObject() {
			$objConnect = self::getConnectObject();
			if (!$objConnect->getSession()) {
				if ($objUserLogin = AppRegistry::get('UserLogin', false)) {
					if ($intUserId = $objUserLogin->getUserId()) {
						AppLoader::includeModel('FacebookModel');
						$objFacebook = new FacebookModel();
						if ($objFacebook->loadByUserId($intUserId) && $objFacebookRecord = $objFacebook->first()) {
							$arrSession = array(
								'uid'			=> $objFacebookRecord->get('externalid'),
								'session_key'	=> $objFacebookRecord->get('sessionkey'),
								'secret'		=> $objFacebookRecord->get('secret'),
								'access_token'	=> $objFacebookRecord->get('token')
							);
							
							ksort($arrSession);
							$strSession = '';
							foreach ($arrSession as $strKey=>$mxdVal) {
								$strSession .= $strKey . '=' . $mxdVal;
							}
							$arrSession['sig'] = md5($strSession . AppConfig::get('FacebookAppSecret'));
							$objConnect->setSession($arrSession);
						}
					}
				}
			}
			if ($objConnect->getSession()) {
				return $objConnect;
			}
		}
		
	
		/**
		 * Connects the user's Facebook account with their
		 * phork account. If the user isn't logged in the
		 * next step should be them logging in or registering
		 * a phork account.
		 *
		 * @access public
		 * @return object The facebook record on success
		 * @static
		 */
		static public function handleConnection() {
			try {
				$objConnect = self::getConnectObject();
				$objUserLogin = AppRegistry::get('UserLogin', false);
				if ($objUserLogin->isLoggedIn()) {
					$intUserId = $objUserLogin->getUserId();
				}
			
				if ($arrSession = $objConnect->getSession()) {
					if ($intExternalId = $objConnect->getUser()) {
						AppLoader::includeModel('FacebookModel');
						$objFacebook = new FacebookModel(array('Relations' => true, 'RelationsAutoLoad' => true));
						if ($objFacebook->loadByExternalId($intExternalId) && $objFacebookRecord = $objFacebook->current()) {
							$objFacebookRecord->set('sessionkey', $arrSession['session_key']);
							$objFacebookRecord->set('secret', $arrSession['secret']);
							$objFacebookRecord->set('token', $arrSession['access_token']);
						} else {
							if ($arrFacebookUser = $objConnect->api('/me')) {
								$objFacebook->import(array(
									'externalid'	=> $intExternalId,
									'sessionkey'	=> $arrSession['session_key'],
									'secret'		=> $arrSession['secret'],
									'token'			=> $arrSession['access_token'],
									'firstname'		=> !empty($arrFacebookUser['first_name']) ? $arrFacebookUser['first_name'] : null,
									'lastname'		=> !empty($arrFacebookUser['last_name']) ? $arrFacebookUser['last_name'] : null,
									'email'			=> !empty($arrFacebookUser['email']) ? $arrFacebookUser['email'] : "facebook.{$intExternalId}@place.holder",
									'location'		=> !empty($arrFacebookUser['location']['name']) ? $arrFacebookUser['location']['name'] : null,
									'gender'		=> !empty($arrFacebookUser['gender']) ? $arrFacebookUser['gender'] : null,
									'url'			=> !empty($arrFacebookUser['link']) ? $arrFacebookUser['link'] : null
								));
								$objFacebookRecord = $objFacebook->current();
							} else {
								trigger_error(AppLanguage::translate('There was an error loading your account information'));
							}
						}
						
						if ($objFacebookRecord) {
							if (!$objFacebookRecord->get('userid') && !empty($intUserId)) {
								$objFacebookRecord->set('userid', $intUserId);
							}
							
							if ($objFacebook->save()) {
								return $objFacebookRecord;
							} else {
								trigger_error(AppLanguage::translate('There was an error saving your account information'));
							}
						}
					} else {
						trigger_error(AppLanguage::translate('Missing Facebook ID'));
					}
				} else {
					trigger_error(AppLanguage::translate('Invalid Facebook session'));
				}
			} catch (FacebookApiException $objException) {
				trigger_error(AppLanguage::translate('There was a Facebook error: %s', (string) $objException));
			}
		}
		
		
		/**
		 * Deletes the Facebook account from the database.
		 *
		 * @access public
		 * @return boolean True on success
		 * @static
		 */
		static public function deactivateAccount() {
			if ($intFacebookId = AppRegistry::get('UserLogin')->getFacebookId()) {
				AppLoader::includeModel('FacebookModel');
				$objFacebook = new FacebookModel();
				$objFacebook->import(array(
					'__id' => $intFacebookId
				));
				return $objFacebook->destroy();
			}
			
			trigger_error(AppLanguage::translate('There was an error deactivating your Facebook account'));
			return false;
		}
		
		
		/**
		 * Posts a status update to Facebook using the oAuth
		 * login credentials.
		 *
		 * @access public
		 * @param string $strStatus The status to post
		 * @param string $strUrl The URL to append to the end of the post, if any
		 * @return boolean True on success
		 * @static
		 */
		static public function postStatus($strStatus, $strUrl = null) {
			$arrAttachment = array(
				'message' => $strStatus,
				'link' => $strUrl ? $strUrl : AppConfig::get('SiteUrl') . AppConfig::get('BaseUrl')
			);
			
			try {
				if ($objApi = self::getApiObject()) {
					if ($objApi->api('/me/feed', 'POST', $arrAttachment)) {
						return true;
					}
				}
			} catch(Exception $objException) {}
			
			if (isset($objException)) {
				if ($objException->getType() == 'OAuthException') {
					$arrFunctionArgs = func_get_args();
					self::grantAndPost(__FUNCTION__, $arrFunctionArgs, 'share');
				} else {
					preg_match('/\(#([0-9]+)\)/', $strError = "$objException", $arrMatches);
					switch ($intErrorCode = !empty($arrMatches[1]) ? $arrMatches[1] : 0) {
						case 102:
						case 200:
						case 450:
						case 452:
							$arrFunctionArgs = func_get_args();
							self::grantAndPost(__FUNCTION__, $arrFunctionArgs, 'share');
							break;
							
						default:
							trigger_error(AppLanguage::translate('There was an error posting to Facebook (%s)', $strError));
							break;
					}
				}
			} else {
				$arrFunctionArgs = func_get_args();
				self::grantAndPost(__FUNCTION__, $arrFunctionArgs, 'share');
				
				trigger_error(AppLanguage::translate('There was an error posting to Facebook'));
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
		 * @param string $strAdditionalPerms The additional permissions to grant if the base perms aren't enough
		 * @static
		 */
		static protected function grantAndPost($strFunction, $arrFunctionArgs, $strAdditionalPerms = null) {
			trigger_error(AppLanguage::translate('Please <a href="%s">grant us access</a> to post to Facebook', AppConfig::get('BaseUrl') . '/account/connect/facebook/' . ($strAdditionalPerms ? "{$strAdditionalPerms}/" : '')));
			$_SESSION['share']['facebook'][] = array($strFunction, $arrFunctionArgs, $strAdditionalPerms);	
		}
		
		
		/**
		 * Finds a user's Facebook friends on this site.
		 *
		 * @access public
		 * @return object The local users who are friends on Facebook
		 * @static
		 */
		static public function findFriends() {
			try {
				if ($objApi = self::getApiObject()) {
					AppLoader::includeModel('UserModel');
					$objUser = new UserModel();
					
					if (($arrFriends = $objApi->api('/me/friends')) && !empty($arrFriends['data'])) {
						$intCount = count($arrFriends['data']);
						$arrIds = array();
						
						for ($i = 0; $i < $intCount; $i++) {
							$arrIds[] = (int) $arrFriends['data'][$i]['id'];
							
							if ((!($i % 30) || $i == $intCount - 1) && !empty($arrIds)) {
								$objUser->loadByFacebookId($arrIds);
								$arrIds = array();
							}
						}
					}
					
					return $objUser;
				} else {
					trigger_error(AppLanguage::translate('Please <a href="%s">grant us access</a> to access your Facebook friends', AppConfig::get('BaseUrl') . '/account/connect/facebook/'));
				}
			} catch (Exception $objException) {
				trigger_error(AppLanguage::translate('There was an error loading your Facebook friends'));
			}
		}
	}