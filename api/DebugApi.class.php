<?php
	require_once('SiteApi.class.php');
	
	/**
	 * DebugApi.class.php
	 * 
	 * This class handles all the debugging API calls.
	 * Generally these calls come directly from the JS
	 * debugging library.
	 *
	 * The following calls require authentication.
	 *
	 * /api/debug/session.json											(GET: return the debug data from the session)
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage api
	 */
	class DebugApi extends SiteApi {
	
		/**
		 * Maps the API method to a method within this class
		 * and returns the response. If no method is mapped
		 * then it attempts to use the core handler.
		 *
		 * @access protected
		 */
		protected function handle() {
			$arrHandlers = array(
				'session'		=> 'GetSession'
			);
			
			$strSegment = str_replace('.' . $this->strFormat, '', $this->objUrl->getSegment(2));
			if (!empty($arrHandlers[$strSegment])) {
				$strMethod = $this->strMethodPrefix . $arrHandlers[$strSegment];
				$this->$strMethod();
			} else {
				parent::handle();
			}
		}
		
		
		/*****************************************/
		/**     ACTION METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Returns the debugging data found in the user's
		 * session and clears it out.
		 *
		 * @access protected
		 */
		protected function handleGetSession() {
			if ($this->verifyRequest('GET')) {
				if (AppConfig::get('DebugDisplay', false)) {
					$strDebugSession = AppConfig::get('DebugSessionName');
					
					$this->blnSuccess = true;
					$this->arrResult = array(
						'items' => isset($_SESSION[$strDebugSession]) ? $_SESSION[$strDebugSession] : array()
					);
					
					$_SESSION[$strDebugSession] = array();
				} else {
					trigger_error(AppLanguage::translate('Debugging via the display is not available'));
					$this->error(400);
				}
			} else {
				$this->error(400);
			}
		}
	}