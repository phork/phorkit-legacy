<?php
	require_once('SiteController.class.php');
	
	/**
	 * UserController.class.php
	 * 
	 * This controller handles all the user profile pages.
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
	class UserController extends SiteController {
		
		protected $intPageUserId;
		protected $arrUserRecord;
		protected $arrRelationship;
		
		
		/**
		 * Sets up the common page variables to be used
		 * across all node templates.
		 * 
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			AppLoader::includeUtility('ActionHelper');
			
			if ($strUsername = AppRegistry::get('Url')->getFilter('user')) {
				if ($arrUserRecord = ApiHelper::getResultNode("/api/users/filter/by=username/{$strUsername}.json", 'users', true)) {
					$this->intPageUserId = $arrUserRecord['id'];
					$this->arrUserRecord = $arrUserRecord;
				}
			}
			
			if (!$this->intPageUserId) {
				$this->error(404);
				exit;
			}
			
			if ($this->blnLoggedIn) {
				if (!($blnSelf = ($this->intPageUserId == $this->intUserId))) {
					$arrRelationship = ApiHelper::getResultNode("/api/users/relationship/by=id/{$this->intPageUserId}.json", 'relationships', true);
			
					if (!empty($arrRelationship['user']['blocked'])) {
						trigger_error(AppLanguage::translate("You're blocking %s and can't see their profile", $strUsername));
						return $this->error();
					}
					if (!empty($arrRelationship['connection']['blocked'])) {
						trigger_error(AppLanguage::translate('You have been blocked by %s', $strUsername));
						return $this->error();
					}
				}
			} else {
				$blnSelf = false;
			}
			
			$this->assignPageVar('arrUser', $arrUserRecord);
			$this->assignPageVar('blnUserIsSelf', $blnSelf);
			$this->assignPageVar('blnUserIsFollowed', isset($arrRelationship) && $arrRelationship['user']['follow'] == 'approved');
			$this->assignPageVar('blnUserIsFriend', isset($arrRelationship) && $arrRelationship['user']['friend'] == 'approved');
			$this->assignPageVar('blnUserIsFriendPending', isset($arrRelationship) && $arrRelationship['connection']['friend'] == 'pending');
			
			$this->assignPageVar('strBodyClass', 'user');
			$this->assignPageVar('arrStylesheets', array(
				AppConfig::get('CssUrl') . $this->strThemeCssDir . 'user.css'
			));
			$this->appendPageVar('arrJavascript', array(
				AppConfig::get('JsUrl') . $this->strThemeJsDir . 'user.js'
			));
		}
		
		
		/*****************************************/
		/**     INCLUDE METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Includes the user info and the connection buttons. 
		 *
		 * @access public
		 * @param array $arrUser The user record data
		 */
		public function includeUserInfo($arrUser, $arrParams = array()) {
			$this->includeTemplateFile($this->getTemplatePath('user/common/userinfo'), array_merge($arrParams, array(
				'arrUser' => $arrUser
			)));
		}
		
		
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Displays the user profile page and a subset of user
		 * connections and events.
		 *
		 * @access protected
		 */
		protected function displayProfile() {
			AppLoader::includeUtility('ApiHelper');
			
			if ($this->getPageVar('blnUserIsSelf')) {
				if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['action'])) {
					switch ($_POST['action']) {
						case 'status':
							list($blnSuccess, $arrResult) = ApiHelper::post('/api/events/add/status.json', $_POST);
							if ($blnSuccess) {
								CoreAlert::alert(AppLanguage::translate('Your status was posted successfully.'));
							}
							break;
					}
				}
				
				list(, $arrPending) = ApiHelper::get('/api/users/pending/friends.json');
			}
			
			list(, $arrFriends) = ApiHelper::get('/api/users/include=extended/approved/friends/' . $this->arrUserRecord['username'] . '.json');
			list(, $arrFollowing) = ApiHelper::get('/api/users/include=extended/approved/following/' . $this->arrUserRecord['username'] . '.json');
			list(, $arrFollowers) = ApiHelper::get('/api/users/include=extended/approved/followers/' . $this->arrUserRecord['username'] . '.json');
			list(, $arrEvents) = ApiHelper::get('/api/events/filter/by=userid/' . $this->intPageUserId . '.json');
			
			$this->assignPageVar('strPageTitle', $this->arrUserRecord['username'] . ' on ' . AppConfig::get('SiteTitle'));
			$this->displayNode('content', $this->getTemplatePath('user/profile'), array(
				'arrPending'		=> isset($arrPending) ? $arrPending['connections'] : null,
				'arrFriends'		=> isset($arrFriends) ? $arrFriends['connections'] : null,
				'intNumFriends'		=> isset($arrFriends) ? $arrFriends['total'] : null,
				'arrFollowing'		=> isset($arrFollowing) ? $arrFollowing['connections'] : null,
				'intNumFollowing'	=> isset($arrFollowing) ? $arrFollowing['total'] : null,
				'arrFollowers'		=> isset($arrFollowers) ? $arrFollowers['connections'] : null,
				'intNumFollowers'	=> isset($arrFollowers) ? $arrFollowers['total'] : null,
				'arrEvents'			=> isset($arrEvents) ? $arrEvents['events'] : null,
				'strSubmitUrl'		=> AppRegistry::get('Url')->getCurrentUrl(),
				'strTokenField'		=> AppConfig::get('TokenField')
			));
		}
	}