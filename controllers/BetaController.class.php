<?php
	require_once('php/core/CoreControllerLite.class.php');
	require_once('php/utilities/Form.class.php');
	
	/**
	 * BetaController.class.php
	 * 
	 * This controller handles the private beta sections
	 * of the public site. All users who aren't logged in 
	 * should use this controller.
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
	class BetaController extends CoreControllerLite {
		
		protected $strThemeDir;
		protected $strThemeCssDir;
		protected $strThemeJsDir;
		
		
		/**
		 * Sets up the common page variables to be used
		 * across all node templates.
		 * 
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			
			$this->assignPageVar('strPageTitle', $strSiteTitle = AppConfig::get('SiteTitle'));
			$this->assignPageVar('strSiteTitle', $strSiteTitle);
			$this->assignPageVar('strTheme', $strTheme = AppConfig::get('Theme'));
			
			$this->strThemeDir = ($strTheme ? "themes/{$strTheme}/" : '');
			$this->strThemeCssDir = '/css/' . ($this->strThemeDir ? $this->strThemeDir : null);
			$this->strThemeJsDir = '/js/' . ($this->strThemeDir ? $this->strThemeDir : null);
		}
		
		
		/**
		 * Pulls all the templates together and builds the
		 * page. Generally this should be called from run().
		 * Regardless of the URL, this will always display
		 * the standard beta index page.
		 *
		 * @access protected
		 */
		protected function display() {
			$objUrl = AppRegistry::get('Url');
			$strActionType = 'signup';
			
			if ($objUrl->getMethod() == 'POST' && $objUrl->getVariable('action') == $strActionType) {
				if (!empty($_POST['promo'])) {
					if (!($blnValidCode = in_array($_POST['promo'], AppConfig::get('BetaPromoCodes')))) {
						AppLoader::includeModel('PromoModel');
						$objPromo = new PromoModel();
						if ($objPromo->loadByCodeAndType($_POST['promo'], 'beta')) {
							if ($objPromoRecord = $objPromo->current()) {
								if (!$objPromoRecord->get('claimed')) {
									$blnValidCode = true;
								} else {
									trigger_error(AppLanguage::translate('That promo code has already been claimed'));
								}
							} else {
								trigger_error(AppLanguage::translate('Invalid promo code'));
							}
						} else {
							trigger_error(AppLanguage::translate('There was an error verifying the promo code'));
						}
					}
				} else {
					trigger_error(AppLanguage::translate('A promo code is required to register during the private beta'));
				}
				
				if (!empty($blnValidCode)) {
					$objUser = new UserModel(array('Validate' => true));
					$objUser->import(array(
						'username'	=> !empty($_POST['username']) ? $_POST['username'] : null,
						'email'		=> !empty($_POST['email']) ? $_POST['email'] : null,
					));
					$objUser->current()->set('password_plaintext', !empty($_POST['password']) ? $_POST['password'] : null);
					$objUser->current()->set('password_plaintext_again', !empty($_POST['password_again']) ? $_POST['password_again'] : null);
					
					if ($objUser->save()) {
						if (isset($objPromoRecord)) {
							$objPromoRecord->set('userid', $objUser->current()->get('__id'));
							$objPromoRecord->set('claimed', date(AppRegistry::get('Database')->getDatetimeFormat()));
							$objPromo->save();
						}
						
						if ($objUserLogin = AppRegistry::get('UserLogin', false)) {
							$objUserLogin->handleFormLogin($objUser->current()->get('username'), $objUser->current()->get('password_plaintext'));
						}
						CoreAlert::alert(AppLanguage::translate('Welcome to %s, %s!', AppConfig::get('SiteTitle'), $objUser->current()->get('displayname')), true);	
						AppDisplay::getInstance()->appendHeader('location: ' . AppConfig::get('BaseUrl') . '/');
						exit;
					} else {
						AppRegistry::get('Error')->error(AppLanguage::translate('There was an error signing up. Please try again'), true);
					}
				}
			}
			
			if ($this->validateFile($strTemplate = $this->strTemplateDir . $this->strThemeDir . 'beta.phtml')) {
				AppDisplay::getInstance()->appendTemplate('content', $strTemplate, array_merge($this->arrPageVars, array(
					'strCssUrl'			=> AppConfig::get('CssUrl'),
					'strJsUrl'			=> AppConfig::get('JsUrl'),
					'strSubmitUrl'		=> AppRegistry::get('Url')->getCurrentUrl(),
					'strTokenField'		=> AppConfig::get('TokenField'),
					'strUsernameField'	=> AppConfig::get('LoginUsernameField'),
					'strPasswordField'	=> AppConfig::get('LoginPasswordField'),
					'strLoginFlag'		=> AppConfig::get('LoginFlag'),
					'strActionType'		=> $strActionType,
					'blnSignupForm'		=> !empty($_GET['promo']) || (!empty($_POST['action']) && $_POST['action'] == $strActionType) || (!empty($_GET['form']) && $_GET['form'] == 'signup'),
					'blnLoginForm'		=> (!empty($_POST) && empty($_POST['action'])) || (!empty($_GET['form']) && $_GET['form'] == 'login'),
					'arrErrors'			=> AppRegistry::get('Error')->getErrors()
				)));
			}
		}
	}