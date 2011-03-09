<?php
	/**
	 * EventHelper.class.php
	 *
	 * Creates and retrieves event data based on a set of
	 * pre-defined rules.
	 *
	 * <code>
	 * AppEvent::register($this->strEventKey . '.post-save', array('EventHelper', 'saveCallback'), array('friendConnection'));
	 * </code>
	 *
	 * Copyright 2006-2011, Phork Labs. (http://www.phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage utilities
	 */
	class EventHelper {
	
		/**
		 * This is the standard, shared post-save callback
		 * used by models to add an event. This wraps the
		 * event methods here and only executes if the 
		 * blnNewRecord and the blnSuccess flags are set.
		 *
		 * @access public
		 * @return boolean True on success
		 * @static
		 */
		static public function saveCallback() {
			$arrFunctionArgs = func_get_args();
			if (!empty($arrFunctionArgs[3]) && !empty($arrFunctionArgs[5])) {
				if (($strAction = $arrFunctionArgs[0]) && (method_exists(__CLASS__, $strAction))) {
					return call_user_func(array(__CLASS__, $strAction), $arrFunctionArgs[1]->current());
				} else {
					throw new CoreException('Invalid event type');
				}
			}
		}
		
		
		/**
		 * Adds an event to the events table.
		 *
		 * @access protected
		 * @param integer $intUserId The user ID to add the event for
		 * @param integer $intTypeId The ID of the event type
		 * @param string $strType The type of event
		 * @param string $strTypeGroup A string for grouping events together
		 * @param array $arrMetaData The meta data related to the event
		 * @return boolean True on success
		 * @static
		 */
		static protected function addEvent($intUserId, $intTypeId, $strType, $strTypeGroup, $arrMetaData) {
			AppLoader::includeModel('UserEventModel');
			$objUserEvent = new UserEventModel(array('Validate' => true));
			$objUserEvent->import(array(
				'userid' 	=> $intUserId,
				'typeid' 	=> $intTypeId,
				'type' 		=> $strType,
				'typegroup' => $strTypeGroup,
				'raw' 		=> $arrMetaData
			));
			return $objUserEvent->save();
		}
		
		
		/*****************************************/
		/**     CUSTOM EVENTS                   **/
		/*****************************************/
		
		
		/**
		 * Adds an event when a user becomes a friend of 
		 * another user.
		 *
		 * @access public
		 * @param object $objUserConnectionRecord The user connection record to create the event from
		 * @return boolean True on success
		 * @static
		 */
		static public function friendConnection($objUserConnectionRecord) {
			if (!($objUserRecord = $objUserConnectionRecord->get('connection'))) {
				AppLoader::includeModel('UserModel');
				$objUser = new UserModel();
				if (!$objUser->loadById($objUserConnectionRecord->get('connectionid')) || !($objUserRecord = $objUser->first())) {
					return false;
				}
			}
		
			return self::addEvent(
				$objUserConnectionRecord->get('userid'),
				$objUserConnectionRecord->get('__id'),
				'friend:connected',
				$objUserConnectionRecord->get('userid'),
				array(
					'userid' => $objUserRecord->get('__id'),
					'username' => $objUserRecord->get('username')
				)
			);
		}
		
		
		/**
		 * Adds an event when a user starts following another
		 * user.
		 *
		 * @access public
		 * @param object $objUserConnectionRecord The user connection record to create the event from
		 * @return boolean True on success
		 * @static
		 */
		static public function followConnection($objUserConnectionRecord) {
			if (!($objUserRecord = $objUserConnectionRecord->get('connection'))) {
				AppLoader::includeModel('UserModel');
				$objUser = new UserModel();
				if (!$objUser->loadById($objUserConnectionRecord->get('connectionid')) || !($objUserRecord = $objUser->first())) {
					return false;
				}
			}
		
			return self::addEvent(
				$objUserConnectionRecord->get('userid'),
				$objUserConnectionRecord->get('__id'),
				'follow:connected',
				$objUserConnectionRecord->get('userid'),
				array(
					'userid' => $objUserRecord->get('__id'),
					'username' => $objUserRecord->get('username')
				)
			);
		}
		
		
		/**
		 * Adds a custom user status event.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to add the status for
		 * @param string $strStatus The status to add
		 * @return boolean True on success
		 * @static
		 */
		static public function userStatus($intUserId, $strStatus) {
			return self::addEvent(
				$intUserId,
				$intUserId,
				'user:status',
				$intUserId,
				array(
					'status' => $strStatus
				)
			);
		}
	}