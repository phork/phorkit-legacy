<?php
	require_once('php/core/CoreControllerLite.class.php');
	
	/**
	 * CallbackController.class.php
	 * 
	 * This controller handles all the callbacks from third
	 * party systems. It has special handling (defined in the
	 * bootstrap) to allow form posts without the token.
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
	class CallbackController extends CoreControllerLite {
	
		/**
		 * Handles any facebook callbacks including when
		 * a user deauthorizes their account.
		 *
		 * @access public
		 */
		public function displayFacebook() {
			switch (AppRegistry::get('Url')->getSegment(2)) {
				case 'disconnect':
					AppLoader::includeUtility('FacebookConnect');
					if ($objConnect = FacebookConnect::getConnectObject()) {
						if (($arrRequest = $objConnect->getSignedRequest()) && !empty($arrRequest['user_id'])) {
							$intFacebookId = $arrRequest['user_id'];
							
							AppLoader::includeModel('FacebookModel');
							$objFacebook = new FacebookModel();
							if ($objFacebook->loadByExternalId($intFacebookId) && $objFacebook->count() == 1) {
								print $objFacebook->destroy();
							}
						}
					}
					break;
			}
		}
	}