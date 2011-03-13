<?php
	require_once('SiteController.class.php');
	
	/**
	 * AccountController.class.php
	 * 
	 * This controller handles the account section of the 
	 * public site.
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
	class AccountController extends SiteController {
		
		/**
		 * Sets up the common page variables to be used
		 * across all node templates.
		 * 
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			
			$this->assignPageVar('blnFacebookConnect', AppConfig::get('FacebookConnect', false));
			$this->assignPageVar('blnTwitterConnect', AppConfig::get('TwitterConnect', false));
			
			$this->assignPageVar('strBodyClass', 'account');
			$this->assignPageVar('arrStylesheets', array(
				AppConfig::get('CssUrl') . $this->strThemeCssDir . 'account.css'
			));
			$this->assignPageVar('arrJavascript', array(
				AppConfig::get('JsUrl') . $this->strThemeJsDir . 'account.js'
			));
		}
		
		
		/**
		 * Sends the verification email to the user whose record
		 * is passed. If the reverify flag is set the old verification
		 * codes will be removed.
		 *
		 * @access protected
		 * @param object $objUserRecord The user record of the user to verify
		 * @param boolean $blnReverify Whether the account is being reverified
		 * @return boolean True on success
		 */
		protected function sendVerificationEmail($objUserRecord, $blnReverify = false) {
			AppLoader::includeModel('VerifyModel');
			$objVerify = new VerifyModel();
			
			if ($blnReverify) {
				if ($objVerify->loadByTypeAndId($objUserRecord->get('__id'), 'user') && $objVerify->count()) {
					$objVerify->deleteById(array_keys($objVerify->getAssociativeList('__id')));
				}
				$objVerify->clear();
			}
			
			$objVerify->import(array(
				'type'		=> 'user',
				'typeid'	=> $objUserRecord->get('__id'),
				'token'		=> $strToken = md5(time() . rand())
			));
			
			if ($objVerify->save()) {
				$strSubject = AppConfig::get('SiteTitle') . ' - Account Verification';
				$strBody = file_get_contents(AppLoader::getIncludePath(AppConfig::get('SiteDir') . 'emails/' . ($blnReverify ? 'reverify-account' : 'verify-account')  . '.txt'));
				$strBody = str_replace('[NAME]', $objUserRecord->get('displayname'), $strBody);
				$strBody = str_replace('[CODE]', $strToken, $strBody);
				$strBody = str_replace('[URL]', AppConfig::get('SiteUrl') . AppConfig::get('BaseUrl') . '/account/verify/?action=verify&code=' . $strToken, $strBody);
				
				AppLoader::includeUtility('Email');
				if (Email::sendTextEmail($objUserRecord->get('email'), $objUserRecord->get('displayname'), AppConfig::get('EmailNoRespond'), AppConfig::get('SiteTitle'), $strSubject, $strBody, 'verify')) {
					CoreAlert::alert(AppLanguage::translate('A verification email was sent to the email address we have on file.'), true);
					return true;
				}
			}
			
			AppRegistry::get('Error')->error(AppLanguage::translate('There was an error sending the verification email'), true);
			return false;
		}
		
		
		/**
		 * Creates all the resized avatars from the original image.
		 * Writes to a temp file and copies to the final location in
		 * case the final location isn't local.
		 *
		 * @access protected
		 * @param string $strFullPath The full path to the original file
		 * @param string $strFilePath The relative path to the new file with wildcard for the size names
		 * @param array $arrAvatarConfig The new avatar sizes and configuration
		 * @return boolean True on success
		 */
		protected function createAvatars($strFullPath, $strFilePath, $arrAvatarConfig) {
			AppLoader::includeUtility('ImageCreator');
			$objFileSystem = AppRegistry::get('FileSystem');
			$strFilesDirectory = $objFileSystem->getFilesDirectory();
			$strTempDirectory = $objFileSystem->getTempDirectory();
			
			foreach ($arrAvatarConfig as $strSize=>$arrValue) {
				if ($strTempFile = $objFileSystem->createTempFile()) {
					$strTempPath = $strTempDirectory . $strTempFile;
					if (ImageCreator::resize($strFilesDirectory . $strFullPath, $strTempPath, !empty($arrValue['Width']) ? $arrValue['Width'] : null, !empty($arrValue['Height']) ? $arrValue['Height'] : null, false)) {
						if (!$objFileSystem->copyFile($strTempFile, $strFilesDirectory . sprintf($strFilePath, $arrValue['Name']), true)) {
							$blnErrors = true;	
						}
						$objFileSystem->deleteFile($strTempPath);
					}
				}
			}
			
			return !empty($blnErrors);
		}
		
		
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Displays the login page. Passes the special
		 * login flag and the username and password fields
		 * to work in conjunction with the access hook
		 * and the login utility to handle the login.
		 *
		 * @access protected
		 */
		protected function displayLogin() {
			$this->assignPageVar('strPageTitle', 'Sign in to ' . strtolower(AppConfig::get('SiteTitle')));
			$this->displayNode('content', $this->getTemplatePath('account/login'), array(
				'strSubmitUrl'		=> AppRegistry::get('Url')->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strUsernameField'	=> AppConfig::get('LoginUsernameField'),
				'strPasswordField'	=> AppConfig::get('LoginPasswordField'),
				'strLoginFlag'		=> AppConfig::get('LoginFlag')
			));
		}
		
		
		/**
		 * Displays the login page. Passes the special
		 * login flag and the username and password fields
		 * to work in conjunction with the access hook
		 * and the login utility to handle the login.
		 * This is the overlay version of the form.
		 *
		 * @access protected
		 */
		protected function displayLoginOverlay() {
			if (!empty($_POST[AppConfig::get('LoginFlag')])) {
				AppLoader::includeUtility('JsonHelper');
				if ($this->blnLoggedIn) {
					AppDisplay::getInstance()->setStatusCode(200);
					$strJson = JsonHelper::encode(array(
						'success'	=> true
					));
				} else {
					AppDisplay::getInstance()->setStatusCode(400);
					$strJson = JsonHelper::encode(array(
						'success'	=> false,
						'errors'	=> array_reverse(AppRegistry::get('Error')->flushErrors())
					));
				}
				Token::reviveToken();
				AppDisplay::getInstance()->appendString('content', $strJson);
				exit;
			}
		
			$this->displayNode('content', $this->getTemplatePath('account/overlay/login'), array(
				'strSubmitUrl'		=> AppRegistry::get('Url')->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strUsernameField'	=> AppConfig::get('LoginUsernameField'),
				'strPasswordField'	=> AppConfig::get('LoginPasswordField'),
				'strLoginFlag'		=> AppConfig::get('LoginFlag')
			));
		}
		
		
		/**
		 * Displays the sign up page. This must use set() to
		 * set the posted password in order for the encrypted
		 * password to be generated.
		 *
		 * @access protected
		 */
		protected function displaySignup() {
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'signup';
			
			if (!empty($_POST['action']) && $_POST['action'] == $strActionType) {
				AppLoader::includeModel('UserModel');
				$objUser = new UserModel(array('Validate' => true));
				$objUser->import(array(
					'username'		=> !empty($_POST['username']) ? $_POST['username'] : null,
					'email'			=> !empty($_POST['email']) ? $_POST['email'] : null,
					'timezone'		=> Date::getSystemOffset() / 3600
				));
				$objUser->current()->set('password_plaintext', !empty($_POST['password']) ? $_POST['password'] : null);
				$objUser->current()->set('password_plaintext_again', !empty($_POST['password_again']) ? $_POST['password_again'] : null);
				
				$objValidation = $objUser->getHelper('validation');
				$objValidation->disableField('CountryId');
				
				if ($objUser->save()) {
					if ($objUserLogin = AppRegistry::get('UserLogin', false)) {
						$objUserLogin->handleFormLogin($objUser->current()->get('username'), $objUser->current()->get('password_plaintext'));
					}
					CoreAlert::alert(AppLanguage::translate('Welcome to %s, %s!', AppConfig::get('SiteTitle'), $objUser->current()->get('displayname')), true);
					AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/?signup=1');
					$this->sendVerificationEmail($objUser->current());
					exit;
				}
			}
			
			$this->assignPageVar('strPageTitle', 'Sign up for ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/signup'), array(
				'strSubmitUrl'		=> $objUrl->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strActionType'		=> $strActionType
			));
		}
		
		
		/**
		 * Displays the forgot password page and handles
		 * sending a temporary password.
		 *
		 * @access protected
		 */
		protected function displayForgotPassword() {
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'password';
			
			if (!empty($_POST['action']) && $_POST['action'] == $strActionType) {
				AppLoader::includeModel('UserModel');
				$objUser = new UserModel();
				if ($objUser->loadByEmail($_POST['email']) && $objUserRecord = $objUser->first()) {
					AppLoader::includeModel('UserPasswordModel');
					$objUserPassword = new UserPasswordModel(array('Validate' => true));
					$objUserPassword->import(array(
						'userid' => $objUserRecord->get('__id')
					));
					if ($objUserPassword->save()) {
						$strSubject = AppConfig::get('SiteTitle') . ' - Password Reset';
						$strBody = file_get_contents(AppLoader::getIncludePath(AppConfig::get('SiteDir') . 'emails/reset-password.txt'));
						$strBody = str_replace('[NAME]', $objUserRecord->get('displayname'), $strBody);
						$strBody = str_replace('[CODE]', $objUserPassword->current()->get('password'), $strBody);
						$strBody = str_replace('[URL]', AppConfig::get('SiteUrl') . AppConfig::get('BaseUrl') . '/account/recover/?code=' . $objUserPassword->current()->get('password') . '&email=' . urlencode($objUserRecord->get('email')), $strBody);
						
						AppLoader::includeUtility('Email');
						if (Email::sendTextEmail($objUserRecord->get('email'), $objUserRecord->get('displayname'), AppConfig::get('EmailNoRespond'), AppConfig::get('SiteTitle'), $strSubject, $strBody, 'password')) {
							CoreAlert::alert(AppLanguage::translate('An email has been sent to you with instructions on resetting your password.'));
						} else {
							AppRegistry::get('Error')->error(AppLanguage::translate('There was an error sending a temporary password'), true);
						}
					} else {
						trigger_error(AppLanguage::translate('There was an error saving a temporary password'));
					}
				} else {
					trigger_error(AppLanguage::translate('Unable to find a user with that email address'));
				}
			}
			
			$this->assignPageVar('strPageTitle', 'Forgot password on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/password'), array(
				'strSubmitUrl'		=> $objUrl->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strActionType'		=> $strActionType
			));
		}
		
		
		/**
		 * Displays the reset password page and handles 
		 * resetting the users password.
		 *
		 * @access protected
		 */
		protected function displayResetPassword() {
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'reset';
			
			if (!empty($_POST['action']) && $_POST['action'] == $strActionType) {
				AppLoader::includeModel('UserModel');
				$objUser = new UserModel(array('Validate' => true));
				if ($objUser->loadByEmail($_POST['email']) && $objUserRecord = $objUser->first()) {
					AppLoader::includeModel('UserPasswordModel');
					$objUserPassword = new UserPasswordModel();
					if ($objUserPassword->loadValidByUserIdAndPassword($objUserRecord->get('__id'), $_POST['code']) && $objUserPasswordRecord = $objUserPassword->current()) {
						if ($objUserRecord->get('__id') == $objUserPasswordRecord->get('userid')) {
							$objUserRecord->set('password_plaintext', $_POST['password']);
							$objUserRecord->set('password_plaintext_again', $_POST['password_again']);
							if ($objUser->save()) {
								$objUserPassword->destroy();
								CoreAlert::alert(AppLanguage::translate('Your password was reset successfully.'), true);
								AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/login/');
								exit;
							} else {
								AppRegistry::get('Error')->error(AppLanguage::translate('There was an error resetting your password'), true);
							}
						} else {
							trigger_error(AppLanguage::translate('Invalid reset code'));
						}
					} else {
						trigger_error(AppLanguage::translate('Invalid reset code. It may have expired'));
					}
				} else {
					trigger_error(AppLanguage::translate('There was an error loading your data'));
				}
			}
			
			$this->assignPageVar('strPageTitle', 'Reset password on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/reset'), array(
				'strSubmitUrl'		=> $objUrl->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strActionType'		=> $strActionType
			));
		}
		
		
		/*****************************************/
		/**     SETTINGS METHODS                **/
		/*****************************************/
		
		
		/**
		 * Displays the account settings page or the login page
		 * if the user isn't logged in.
		 *
		 * @access protected
		 */
		protected function displaySettings() {
			if (!$this->blnLoggedIn) {
				return $this->requireLogin();
			}
			
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'account';
			
			$arrTzConfig = AppConfig::load('timezones');
			$arrTimezones = $arrTzConfig['Timezones'];
			unset($arrTzConfig);
			
			AppLoader::includeModel('CountryModel');
			$objCountry = new CountryModel();
			if ($objCountry->load() && $objCountry->count()) {
				foreach (($arrCountries = $objCountry->getAssociativeList('__id')) as $intKey=>$objRecord) {
					$arrCountries[$intKey] = $objRecord->get('country');
				}
			}
				
			AppLoader::includeModel('UserModel');
			$objUser = new UserModel(array('Validate' => true));
			if ($objUser->loadById($this->intUserId) && $objUserRecord = $objUser->current()) {
				if (!empty($_POST['action']) && $_POST['action'] == $strActionType) {
					$objUserRecord->set('firstname', $_POST['firstname']);
					$objUserRecord->set('lastname', $_POST['lastname']);
					$objUserRecord->set('countryid', $_POST['country']);
					$objUserRecord->set('timezone', $_POST['timezone']);
					$objUserRecord->set('birthdate_year', $_POST['birthdate_year']);
					$objUserRecord->set('birthdate_month', $_POST['birthdate_month']);
					$objUserRecord->set('birthdate_day', $_POST['birthdate_day']);
					
					if (!empty($_POST['email'])) {
						if ($objUserRecord->get('email') != $_POST['email']) {
							if ($blnReverify = AppConfig::get('EmailReverify', false)) {
								$objUserRecord->set('verified', 0);
							}
						}
						$objUserRecord->set('email', $_POST['email']);
					}
					
					if (!$objUserRecord->get('password') || PasswordHelper::validatePassword($objUserRecord->get('password'), $_POST['current'])) {
						if (empty($_POST['password']) || $_POST['password'] == $_POST['confirm']) {
							if (!empty($_POST['password'])) {
								$objUserRecord->set('password_plaintext', $_POST['password']);
								$objUserRecord->set('password_plaintext_again', $_POST['confirm']);
							}
							
							if ($objUser->save()) {
								CoreAlert::alert(AppLanguage::translate('Your account was updated successfully.'));
								AppRegistry::get('UserLogin')->setUserRecord($objUserRecord);
								
								if (!empty($blnReverify)) {
									$this->sendVerificationEmail($objUserRecord, true);
								}
							} else {
								AppRegistry::get('Error')->error(AppLanguage::translate('There was an error updating your account'), true);
							}
						} else {
							trigger_error(AppLanguage::translate('Invalid password confirmation'));
						}
					} else {
						trigger_error(AppLanguage::translate('Invalid current password'));
					}
				}
				
				if (!$objUserRecord->get('timezone')) {
					$objUserRecord->set('timezone', '0.0');
				}
				
				if (preg_match('/(facebook|twitter)\.[0-9]+\@place\.holder/', $objUserRecord->get('email'))) {
					$objUserRecord->set('email', '');
				}
			} else {
				$this->error();
			}
			
			if ($this->getPageVar('blnFacebookConnect')) {
				AppLoader::includeModel('FacebookModel');
				$objFacebook = new FacebookModel();
				if ($objFacebook->loadByUserId($this->intUserId) && $objFacebookRecord = $objFacebook->first()) {
					$strFacebook = $objFacebookRecord->get('firstname') . ' ' . $objFacebookRecord->get('lastname');
				}
			}
			
			if ($this->getPageVar('blnTwitterConnect')) {
				AppLoader::includeModel('TwitterModel');
				$objTwitter = new TwitterModel();
				if ($objTwitter->loadByUserId($this->intUserId) && $objTwitterRecord = $objTwitter->first()) {
					$strTwitter = $objTwitterRecord->get('username');
				}
			}
			
			$this->assignPageVar('strPageTitle', 'Account settings on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/settings/index'), array(
				'arrUser'			=> (array) $objUserRecord,
				'arrCountries'		=> isset($arrCountries) ? $arrCountries : array(),
				'arrTimezones'		=> $arrTimezones,
				'strFacebook'		=> isset($strFacebook) ? $strFacebook : null,
				'strTwitter'		=> isset($strTwitter) ? $strTwitter : null,
				'strSubmitUrl'		=> $objUrl->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strActionType'		=> $strActionType
			));
		}
		
		
		/**
		 * Displays the profile settings page or the login page
		 * if the user isn't logged in. Also handles the avatar
		 * upload.
		 *
		 * @access protected
		 */
		protected function displayProfile() {
			if (!$this->blnLoggedIn) {
				return $this->requireLogin();
			}
			
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'profile';
			$intMaxFileSize = 409600;
			
			AppLoader::includeModel('UserModel');
			$objUser = new UserModel(array('Validate' => true));
			if ($objUser->loadById($this->intUserId) && $objUserRecord = $objUser->current()) {
				if (!empty($_POST['action']) && $_POST['action'] == $strActionType) {
					if (!($objFileSystem = AppRegistry::get('FileSystem', false))) {
						AppLoader::includeExtension('files/', $strFileSystem = AppConfig::get('FileSystem') . 'FileSystem');
						AppRegistry::register('FileSystem', $objFileSystem = new $strFileSystem());
					}
								
					AppLoader::includeUtility('FileHelper');
					if (($arrFiles = FileHelper::getUploadedFiles()) && !empty($arrFiles['avatar'])) {
						if ($arrFiles['avatar']['size'] <= $intMaxFileSize) {
							if ($strExt = FileHelper::isValidImage($arrFiles['avatar'], array('png', 'gif', 'jpg'))) {
								$arrAvatarConfig = AppConfig::get('Avatar');
								$strFullName = $arrAvatarConfig['Full']['Name'];
								unset($arrAvatarConfig['Full']);
										
								$strFileName = $this->intUserId . '-%s.' . $strExt;
								$strFilePath = $objFileSystem->getHashDirectory(AppConfig::get('PublicFilePath') . AppConfig::get('AvatarFilePath'), $strFileName, 5) . $strFileName;
								if (FileHelper::saveUploadedFile($arrFiles['avatar']['tmp_name'], $strFullPath = sprintf($strFilePath, $strFullName), true)) {
									$this->createAvatars($strFullPath, $strFilePath, $arrAvatarConfig);
									$objUserRecord->set('avatar', str_replace(AppConfig::get('PublicFilePath'), $objFileSystem->getPublicUrl(), $strFilePath));
								}
							} else {
								trigger_error(AppLanguage::translate('The avatar must be a PNG, GIF or JPG file'));
							}
						} else {
							trigger_error(AppLanguage::translate('The avatar must be less than %s', '400k'));
						}
					} else if (!empty($_POST['noavatar'])) {
						$arrAvatarConfig = AppConfig::get('Avatar');
						$strFullName = $arrAvatarConfig['Full']['Name'];
						unset($arrAvatarConfig['Full']);
						
						$strFilePath = str_replace($objFileSystem->getPublicUrl(), AppConfig::get('PublicFilePath'), $objUserRecord->get('avatar'));
						$objFileSystem->deleteFile(sprintf($strFilePath, $strFullName), true);
						foreach ($arrAvatarConfig as $strSize=>$arrValue) {
							$objFileSystem->deleteFile(sprintf($strFilePath, $arrValue['Name']), true);
						}
						
						$objUserRecord->set('avatar', null);
					}
					
					$objUserRecord->set('displayname', $_POST['displayname']);
					$objUserRecord->set('location', $_POST['location']);
					$objUserRecord->set('url', $_POST['url']);
					$objUserRecord->set('blurb', $_POST['blurb']);
					
					if (!AppRegistry::get('Error')->getErrorFlag() && $objUser->save()) {
						CoreAlert::alert(AppLanguage::translate('Your profile was updated successfully.'));
						AppRegistry::get('UserLogin')->setUserRecord($objUserRecord);
					} else {
						AppRegistry::get('Error')->error(AppLanguage::translate('There was an error updating your profile'), true);
					}
				}
			} else {
				$this->error();
			}
					
			$this->assignPageVar('strPageTitle', 'Profile settings on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/settings/profile'), array(
				'arrUser'			=> (array) $objUserRecord,
				'strSubmitUrl'		=> $objUrl->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strActionType'		=> $strActionType,
				'intMaxFileSize'	=> $intMaxFileSize
			));
		}
		
		
		/**
		 * Sends the user a verification email and redirects them
		 * back to the previous page. If the verification args are
		 * in the query string this will verify them.
		 *
		 * @access protected
		 */
		protected function displayVerify() {
			if (!$this->blnLoggedIn) {
				return $this->requireLogin();
			}
			
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'verify';
			
			AppLoader::includeModel('UserModel');
			$objUser = new UserModel();
			if ($objUser->loadById($this->intUserId) && $objUserRecord = $objUser->current()) {
				if (!empty($_GET['action'])) {
					switch ($_GET['action']) {
						case $strActionType:
							$arrFilters = array(
								'Conditions' => array(
									array(
										'Column' => 'token',
										'Value'	=> $_GET['code']
									)
								)
							);
							
							AppLoader::includeModel('VerifyModel');
							$objVerify = new VerifyModel();
							if ($objVerify->loadByTypeAndId($this->intUserId, 'user', $arrFilters, false) && $objVerifyRecord = $objVerify->current()) {
								if (!$objVerifyRecord->get('verified')) {
									$objUserRecord->set('verified', 1);
									if ($objUser->save()) {
										$objVerifyRecord->set('verified', date(AppRegistry::get('Database')->getDatetimeFormat()));
										$objVerify->save();
										
										CoreAlert::alert(AppLanguage::translate('Your account was verified successfully.'), true);
										AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/');
										exit;
									} else {
										AppRegistry::get('Error')->error(AppLanguage::translate('There was an error verifying your account'), true);
									}
								} else {
									trigger_error(AppLanguage::translate('This verification code has already been used'));
								}
							} else {
								trigger_error(AppLanguage::translate('There was an error loading the verification data'));
							}
							break;
							
						case 'send':
							if ($this->sendVerificationEmail($objUserRecord)) {
								AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/');
								exit;
							}
							break;
					}
				}
			} else {
				trigger_error(AppLanguage::translate('There was an error loading your account information'));
			}
			
			$this->assignPageVar('strPageTitle', 'Verify account on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/verify'), array(
				'strSubmitUrl'		=> $objUrl->getCurrentUrl(),
				'strActionType'		=> $strActionType
			));
		}
		
		
		/*****************************************/
		/**     CONNECT METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Displays the page to finish the Facebook Connect
		 * process that ties the accounts together.
		 *
		 * @access protected
		 */
		protected function displayFacebook() {
			if ($this->blnLoggedIn) {
				AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/profile/');
				exit;
			}
		
			if (!($objUserLogin = AppRegistry::get('UserLogin', false)) || !($intFacebookId = $objUserLogin->getFacebookId())) {
				if ($objFacebookConnect = AppRegistry::get('Facebook', false)) {
					$strRedirect = $objFacebookConnect->getLoginUrl(array(
						'req_perms' => AppConfig::get('FacebookBasePerms', false)
					));
				} else {
					trigger_error(AppLanguage::translate('There was an error connecting with Facebook'));
					$strRedirect = AppConfig::get('BaseUrl') . '/account/signup/';
				}
			}
			
			AppLoader::includeModel('FacebookModel');
			$objFacebook = new FacebookModel();
			if (!$objFacebook->loadById($intFacebookId) || !($objFacebookRecord = $objFacebook->first())) {
				trigger_error(AppLanguage::translate('There was an error loading your Facebook data'));
				$strRedirect = AppConfig::get('BaseUrl') . '/account/signup/';
			}
			
			if (isset($strRedirect)) {
				AppDisplay::getInstance()->appendHeader('location: ' . $strRedirect);
				exit;
			}
		
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'connect';
			
			if (!empty($_POST['action']) && $_POST['action'] == $strActionType) {
				AppLoader::includeModel('UserModel');
				$objUser = new UserModel(array('Validate' => true));
				
				$objValidation = $objUser->getHelper('validation');
				$objValidation->disableField('CountryId');
				$objValidation->disableField('PasswordAgain');
				
				$objUser->import(array(
					'username'		=> !empty($_POST['username']) ? $_POST['username'] : null,
					'displayname'	=> !empty($_POST['username']) ? $_POST['username'] : null,
					'email'			=> !empty($_POST['email']) ? $_POST['email'] : null,
					'firstname'		=> $objFacebookRecord->get('firstname'),
					'lastname'		=> $objFacebookRecord->get('lastname')
				));
				
				if ($objUser->save()) {
					if (!empty($_POST['import'])) {
						if (!($objFileSystem = AppRegistry::get('FileSystem', false))) {
							AppLoader::includeExtension('files/', $strFileSystem = AppConfig::get('FileSystem') . 'FileSystem');
							AppRegistry::register('FileSystem', $objFileSystem = new $strFileSystem());
						}
						
						if ($strImageContents = file_get_contents('https://graph.facebook.com/' . $objFacebookRecord->get('externalid') . '/picture?type=large')) {
							if (!strstr(substr($strImageContents, 0, 50), 'error')) {
								$arrAvatarConfig = AppConfig::get('Avatar');
								$strFullName = $arrAvatarConfig['Full']['Name'];
								unset($arrAvatarConfig['Full']);
								
								$strFileName = $objUser->first()->get('__id') . '-%s.jpg';
								$strFilePath = $objFileSystem->getHashDirectory(AppConfig::get('PublicFilePath') . AppConfig::get('AvatarFilePath'), $strFileName, 5) . $strFileName;
								$strFullPath = sprintf($strFilePath, $strFullName);
								
								if ($objFileSystem->createFile($strFullPath, $strImageContents)) {
									$this->createAvatars($strFullPath, $strFilePath, $arrAvatarConfig);				
									$objUserRecord->set('avatar', str_replace(AppConfig::get('PublicFilePath'), $objFileSystem->getPublicUrl(), $strFilePath));
									$objUser->save();
								}
							}
						}
						unset($strImageContents);
					}
					$this->sendVerificationEmail($objUser->first());
				
					$objFacebookRecord->set('userid', $objUser->first()->get('__id'));
					if ($objFacebook->save()) {
						$objUserLogin->handleLogin($objUser);
						
						CoreAlert::alert(AppLanguage::translate('Welcome to %s, %s!', AppConfig::get('SiteTitle'), $objUser->current()->get('displayname')), true);
						AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/');
						exit;
					}
				}
			}
			
			$this->assignPageVar('strPageTitle', 'Facebook connect on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/facebook'), array(
				'strFacebookName'	=> $objFacebookRecord->get('firstname'),
				'strFacebookImage'	=> 'https://graph.facebook.com/' . $objFacebookRecord->get('externalid') . '/picture',
				'strSubmitUrl'		=> $objUrl->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strActionType'		=> $strActionType
			));
		}
		
		
		/**
		 * Displays the page to finish the Twitter Connect
		 * process that ties the accounts together.
		 *
		 * @access protected
		 */
		protected function displayTwitter() {
			if ($this->blnLoggedIn) {
				AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/profile/');
				exit;
			}
		
			if (!($objUserLogin = AppRegistry::get('UserLogin', false)) || !($intTwitterId = $objUserLogin->getTwitterId())) {
				if ($objTwitterConnect = AppRegistry::get('Twitter', false)) {
					$strRedirect = $objTwitterConnect->getRedirectUrl();
				} else {
					trigger_error(AppLanguage::translate('There was an error connecting with Twitter'));
					$strRedirect = AppConfig::get('BaseUrl') . '/account/signup/';
				}
			}
			
			AppLoader::includeModel('TwitterModel');
			$objTwitter = new TwitterModel();
			if (!$objTwitter->loadById($intTwitterId) || !($objTwitterRecord = $objTwitter->first())) {
				trigger_error(AppLanguage::translate('There was an error loading your Twitter data'));
				$strRedirect = AppConfig::get('BaseUrl') . '/account/signup/';
			}
			
			if (isset($strRedirect)) {
				AppDisplay::getInstance()->appendHeader('location: ' . $strRedirect);
				exit;
			}
		
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'connect';
			
			if (!empty($_POST['action']) && $_POST['action'] == $strActionType) {
				AppLoader::includeModel('UserModel');
				$objUser = new UserModel(array('Validate' => true));
				
				$objValidation = $objUser->getHelper('validation');
				$objValidation->disableField('CountryId');
				$objValidation->disableField('PasswordAgain');
				
				$objUser->import(array(
					'username'		=> !empty($_POST['username']) ? $_POST['username'] : null,
					'displayname'	=> !empty($_POST['username']) ? $_POST['username'] : null,
					'email'			=> !empty($_POST['email']) ? $_POST['email'] : null
				));
				
				if ($objUser->save()) {
					if (!empty($_POST['import'])) {
						if (!($objFileSystem = AppRegistry::get('FileSystem', false))) {
							AppLoader::includeExtension('files/', $strFileSystem = AppConfig::get('FileSystem') . 'FileSystem');
							AppRegistry::register('FileSystem', $objFileSystem = new $strFileSystem());
						}
						
						if ($strImageContents = file_get_contents($objTwitterRecord->get('avatar'))) {
							if (!strstr(substr($strImageContents, 0, 50), 'error')) {
								$arrAvatarConfig = AppConfig::get('Avatar');
								$strFullName = $arrAvatarConfig['Full']['Name'];
								unset($arrAvatarConfig['Full']);
								
								$strFileName = $objUser->first()->get('__id') . '-%s.jpg';
								$strFilePath = $objFileSystem->getHashDirectory(AppConfig::get('PublicFilePath') . AppConfig::get('AvatarFilePath'), $strFileName, 5) . $strFileName;
								$strFullPath = sprintf($strFilePath, $strFullName);
								
								if ($objFileSystem->createFile($strFullPath, $strImageContents)) {
									$this->createAvatars($strFullPath, $strFilePath, $arrAvatarConfig);				
									$objUserRecord->set('avatar', str_replace(AppConfig::get('PublicFilePath'), $objFileSystem->getPublicUrl(), $strFilePath));
									$objUser->save();
								}
							}
						}
						unset($strImageContents);
					}
					$this->sendVerificationEmail($objUser->first());
				
					$objTwitterRecord->set('userid', $objUser->first()->get('__id'));
					if ($objTwitter->save()) {
						$objUserLogin->handleLogin($objUser);
						
						CoreAlert::alert(AppLanguage::translate('Welcome to %s, %s!', AppConfig::get('SiteTitle'), $objUser->current()->get('displayname')), true);
						AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/');
						exit;
					}
				}
			}
			
			$this->assignPageVar('strPageTitle', 'Twitter connect on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/twitter'), array(
				'strTwitterName'	=> $objTwitterRecord->get('username'),
				'strTwitterImage'	=> $objTwitterRecord->get('avatar'),
				'strSubmitUrl'		=> $objUrl->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strActionType'		=> $strActionType
			));
		}
		
		
		/**
		 * Redirects the user to one of the third parth oAuth
		 * URLs. The app should redirect back here on success.
		 * If the user isn't logged in the AccessHooks will
		 * call the UserLogin class to handle the connection.
		 * Otherwise this will handle connecting existing users.
		 *
		 * @access protected
		 */
		protected function displayConnect() {
			$objUrl = AppRegistry::get('Url');
			$strApplication = $objUrl->getSegment(2);
			$strAdditionalPerms = $objUrl->getSegment(3);
			
			switch ($strApplication) {
				case 'facebook':
					AppLoader::includeUtility('FacebookConnect');
					if ($objFacebookConnect = FacebookConnect::getConnectObject()) {
						if (!empty($_GET)) {
							if (empty($_GET['denied'])) {
								if ($objRecord = FacebookConnect::handleConnection()) {
									CoreAlert::alert(AppLanguage::translate('Your Facebook account was connected successfully.'), true);
									$strRedirectUrl = AppConfig::get('BaseUrl') . '/account/connected/facebook/' . ($strAdditionalPerms ? "{$strAdditionalPerms}/" : '');
								}
							} else {
								FacebookConnect::deactivateAccount();
							}
						} else {
							if ($strPerms = AppRegistry::get('Url')->getSegment(3)) {
								$strConfigPerms = 'Facebook' . ucfirst($strPerms) . 'Perms';
							} else {
								$strConfigPerms = 'FacebookBasePerms';
							}
							$strRedirectUrl = $objFacebookConnect->getLoginUrl(array(
								'cancel_url'	=> AppConfig::get('SiteUrl') . AppConfig::get('BaseUrl') . '/account/connect/facebook/?denied=1',
								'req_perms' 	=> AppConfig::get($strConfigPerms, false)
							));
						}
					}
					break;
					
				case 'twitter':
					AppLoader::includeUtility('TwitterConnect');
					if ($objTwitterConnect = TwitterConnect::getConnectObject()) {
						if (!empty($_GET)) {
							if (empty($_GET['denied'])) {
								if ($objRecord = TwitterConnect::handleConnection()) {
									CoreAlert::alert(AppLanguage::translate('Your Twitter account was connected successfully.'), true);
									$strRedirectUrl = AppConfig::get('BaseUrl') . '/account/connected/twitter/' . ($strAdditionalPerms ? "{$strAdditionalPerms}/" : '');
								}
							} else {
								TwitterConnect::deactivateAccount();
							}
						} else {
							$_SESSION['_trt'] = serialize($objTwitterConnect->getRequestToken());
							$strRedirectUrl = $objTwitterConnect->getRedirectUrl();
						}
					}
					break;
			}
			
			if (!empty($strRedirectUrl)) {
				AppDisplay::getInstance()->appendHeader('location: ' . $strRedirectUrl);
				exit;
			}
			
			if (AppRegistry::get('UserLogin')->isLoggedIn()) {
				if (!empty($_GET['denied'])) {
					trigger_error(AppLanguage::translate('Your accounts were not connected by request. If you change your mind you can come back later.'));
				} else {
					trigger_error(AppLanguage::translate('There was an error connecting your account. Please try again later.'));
				}
			} else {
				trigger_error(AppLanguage::translate('There was an error logging in. Please try again later or login via %s.', AppConfig::get('SiteTitle')));
			}
			
			$this->assignPageVar('strPageTitle', ucfirst($strApplication) . ' connect on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/connect'));
		}
		
		
		/**
		 * The success page to redirect to after a user has
		 * connected their accounts. If any failed posts have
		 * been saved in the session this will give them the
		 * change to retry the post.
		 *
		 * @access protected
		 */
		protected function displayConnected() {
			$objUrl = AppRegistry::get('Url');
			$strApplication = $objUrl->getSegment(2);
			$strAdditionalPerms = $objUrl->getSegment(3);
			$strActionType = 'signup';
			
			$arrRetry = array();
			if (!empty($_SESSION['share'][$strApplication])) {
				foreach ($_SESSION['share'][$strApplication] as $intKey=>$arrShare) {
					if (count($arrShare) == 2 || $arrShare[2] == $strAdditionalPerms) {
						$arrRetry[$intKey] = $arrShare;
					}
				}
			}
			
			if (!empty($_POST['action']) && $_POST['action'] == $strActionType) {
				$strConnectClass = ucfirst($strApplication) . 'Connect';
				AppLoader::includeUtility($strConnectClass);
				
				foreach ($_SESSION['share'][$strApplication] as $intKey=>$arrShare) {
					if (!empty($arrRetry[$intKey])) {
						if (!empty($_POST['retry'][$intKey]) && md5(serialize($arrRetry[$intKey])) == $_POST['retry'][$intKey]) {
							if (call_user_func_array(array($strConnectClass, $arrRetry[$intKey][0]), $arrRetry[$intKey][1])) {
								CoreAlert::alert(AppLanguage::translate('The following post was succesful: %s %s', $arrRetry[$intKey][1][0], $arrRetry[$intKey][1][1]));
								unset($_SESSION['share'][$strApplication][$intKey], $arrRetry[$intKey]);
							} else {
								trigger_error(AppLanguage::translate('There was an error posting the following: %s %s', $arrRetry[$intKey][1][0], $arrRetry[$intKey][1][1]));
							}
						} else {
							unset($_SESSION['share'][$strApplication][$intKey], $arrRetry[$intKey]);
						}
					}
				}
			} else if (empty($arrRetry)) {
				AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/');
				exit;
			}
			
			$this->assignPageVar('strPageTitle', ucfirst($strApplication) . ' connected on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('account/connected'), array(
				'strSubmitUrl'		=> AppRegistry::get('Url')->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField'),
				'strActionType'		=> $strActionType,
				'strApplication'	=> $strApplication,
				'arrRetry'			=> $arrRetry
			));
		}
		
		
		/**
		 * Disconnects a user by removing the account from
		 * the database. Also makes sure they have their account
		 * set up so they can still log in and out with their
		 * password.
		 *
		 * @access protected
		 */
		protected function displayDisconnect() {
			if (!$this->blnLoggedIn) {
				return $this->requireLogin();
			}
			
			$objUrl = AppRegistry::get('Url');
			$strApplication = $objUrl->getSegment(2);
			
			AppLoader::includeModel('UserModel');
			$objUser = new UserModel();
			if ($objUser->loadById($this->intUserId) && $objUserRecord = $objUser->current()) {
				if ($objUserRecord->get('password')) {
					switch ($strApplication) {
						case 'facebook':
							AppLoader::includeUtility('FacebookConnect');
							$blnDisconnected = FacebookConnect::deactivateAccount();
							AppRegistry::get('UserLogin')->clearFacebookId();
							break;
							
						case 'twitter':
							AppLoader::includeUtility('TwitterConnect');
							$blnDisconnected = TwitterConnect::deactivateAccount();
							AppRegistry::get('UserLogin')->clearTwitterId();
							break;
							
						default:
							trigger_error(AppLanguage::translate('Missing application'));
							$this->error();
					}
					
					if (!empty($blnDisconnected)) {
						CoreAlert::alert(AppLanguage::translate('Your %s account was disconnected successfully.', ucfirst($strApplication)), true);
						AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/');
						exit;
					}
				} else {
					CoreAlert::alert(AppLanguage::translate('You must have a password on file before you can disconnect your %s account.', ucfirst($strApplication)), true);
					AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/account/settings/');
					exit;
				}
			} else {
				$this->error();
			}
		}
	}