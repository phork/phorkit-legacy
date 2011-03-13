<?php
	require_once('SiteApi.class.php');
	
	/**
	 * EventsApi.class.php
	 * 
	 * This class handles all the event API calls. This can
	 * either be called via the ApiHelper class or by URL
	 * using the ApiController.
	 *
	 * /api/events/latest.json											(GET: latest events from everyone)
	 * /api/events/filter/by=userid/[userid].json						(GET: events by user ID)
	 * /api/events/filter/by=username/[username].json					(GET: events by username)
	 *
	 * The following calls require authentication.
	 *
	 * /api/events/my/connections.json									(GET: events by the user's connections)
	 * /api/events/my/combined.json										(GET: events by the user and their connections)
	 * /api/events/add/status.json										(POST: post a status event)
	 * /api/events/delete/[event id].json								(DELETE: delete an event by ID)
	 *
	 * The following values can be used for internal calls.
	 *
	 * /internal=nocache/												(don't load from or save to the cache)
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
	class EventsApi extends SiteApi {
	
		protected $intCacheExpire = 300;
		
	
		/**
		 * Maps the API method to a method within this class
		 * and returns the response. If no method is mapped
		 * then it attempts to use the core handler.
		 *
		 * @access protected
		 */
		protected function handle() {
			$arrHandlers = array(
				'latest'		=> 'GetLatest',
				'filter'		=> 'GetFiltered',
				'my'			=> 'GetMine',
				
				'add'			=> 'DoAdd',
				'delete'		=> 'DoDelete'
			);
			
			$strSegment = str_replace('.' . $this->strFormat, '', AppRegistry::get('Url')->getSegment(2));
			if (!empty($arrHandlers[$strSegment])) {
				$strMethod = $this->strMethodPrefix . $arrHandlers[$strSegment];
				$this->$strMethod();
			} else {
				parent::handle();
			}
		}
		
		
		/**
		 * Includes and instantiates a event model.
		 *
		 * @access public
		 * @return object The event model
		 */
		public function initModel() {
			AppLoader::includeModel('UserEventModel');
			$objUserEvent = new UserEventModel();
			return $objUserEvent;
		}
		
		
		/**
		 * Gets the result parameters from the URL and returns
		 * the data to be extracted by the display method.
		 *
		 * @access protected
		 * @return array The compacted data
		 */
		protected function getResultParams() {
			$objUrl = AppRegistry::get('Url');
			
			$intNumResults = !empty($this->arrParams['num']) ? $this->arrParams['num'] : 10;
			$intPage = !empty($this->arrParams['p']) ? $this->arrParams['p'] : 1;
			$arrFilters = array(
				'Conditions' => array(),
				'Limit' => $intNumResults, 
				'Offset' => ($intPage - 1) * $intNumResults
			);
			
			if ($this->blnInternal) {
				$arrInternal = explode(',', $objUrl->getFilter('internal'));
				if (in_array('nocache', $arrInternal)) {
					$this->blnNoCache = true;
				}
				if (in_array('banned', $arrInternal)) {
					$arrFilters['AutoFilterOff'] = true;
				}
			} else {
				$arrInternal = array();
			}
			
			return compact('arrFilters', 'arrInternal');
		}
		
		
		/**
		 * Verifies the parameters from the URL. There is
		 * currently nothing to verify.
		 *
		 * @access protected
		 * @return boolean True if valid
		 */
		protected function verifyParams() {
			return true;
		}
		
		
		/*****************************************/
		/**     HANDLER METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Get the latest items. Defaults to 10 results but
		 * is configurable. This does not return a full count.
		 *
		 * @access protected
		 */
		protected function handleGetLatest() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				extract($this->getResultParams());
				
				$objUserEvent = $this->initModel();
				if ($objUserEvent->loadLatest(false, $arrFilters, false)) {
					$this->blnSuccess = true;
					if ($objUserEvent->count()) {
						$this->arrResult = array(
							'events' => $this->formatEvents($objUserEvent),
							'total' => $objUserEvent->count()
						);
					} else {
						$this->arrResult = array(
							'events' => array(),
							'total' => 0
						);
					}
					
					$this->saveToCache($this->intCacheExpire);
				} else {
					trigger_error(AppLanguage::translate('There was an error loading the event data'));
					$this->error();
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Gets the filtered items. Defaults to 10 results but
		 * is configurable.
		 *
		 * @access protected
		 */
		protected function handleGetFiltered() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				extract($this->getResultParams());
				
				$objUrl = AppRegistry::get('Url');
				$strFilterBy = $objUrl->getFilter('by');
				$mxdFilter = str_replace('.' . $this->strFormat, '', $objUrl->getSegment(3));
				
				if ($strFilterBy == 'username') {
					AppLoader::includeUtility('DataHelper');
					$mxdFilter = DataHelper::getUserIdByUsername($mxdFilter);
					$strFilterBy = 'userid';
				}
				
				if ($strFilterBy == 'userid') {
					$blnCached = $this->loadFromCache($strNamespace = sprintf(AppConfig::get('UserEventNamespace'), $mxdFilter));
				} else {
					$blnCached = $this->loadFromCache();
				}
				
				if (empty($blnCached)) {
					$objUserEvent = $this->initModel();
					switch ($strFilterBy) {
						case 'userid':
							$blnResult = $objUserEvent->loadByUserId($mxdFilter, $arrFilters, false);
							break;
					}
					
					if ($blnResult) {
						$this->blnSuccess = true;
						if ($objUserEvent->count()) {
							$this->arrResult = array(
								'events' => $this->formatEvents($objUserEvent),
								'total' => $objUserEvent->count()
							);
						} else {
							$this->arrResult = array(
								'events' => array(),
								'total' => 0
							);
						}
						
						if (isset($blnCached)) { 
							if (isset($strNamespace)) {
								$this->saveToCache($this->intCacheExpire, $strNamespace);
							} else {
								$this->saveToCache($this->intCacheExpire);
							}
						}
					} else {
						trigger_error(AppLanguage::translate('There was an error loading the event data'));
						$this->error();
					}
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Gets the authenticated user's connections' events.
		 * Defaults to 10 results but is configurable.
		 *
		 * @access protected
		 */
		protected function handleGetMine() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					extract($this->getResultParams());
					
					$strFilterBy = str_replace('.' . $this->strFormat, '', AppRegistry::get('Url')->getSegment(3));
					switch ($strFilterBy) {
						case 'connections':
							$strMethod = 'loadConnectionsByUserId';
							break;
						
						case 'combined':
							$strMethod = 'loadCombinedByUserId';
							break;
					}
					
					if (!empty($strMethod)) {
						$objUserEvent = $this->initModel();
						if ($objUserEvent->$strMethod(AppRegistry::get('UserLogin')->getUserId(), $arrFilters)) {
							$this->blnSuccess = true;
							if ($objUserEvent->count()) {
								$this->arrResult = array(
									'events' => $this->formatEvents($objUserEvent),
									'total' => $objUserEvent->count()
								);
							} else {
								$this->arrResult = array(
									'events' => array(),
									'total' => 0
								);
							}
						} else {
							trigger_error(AppLanguage::translate('There was an error loading the event data'));
							$this->error();
						}
					} else {
						trigger_error(AppLanguage::translate('Missing or invalid filter type'));
						$this->error(401);
					}
				} else {
					trigger_error(AppLanguage::translate('Missing or invalid authentication'));
					$this->error(401);
				}
			} else {
				$this->error(400);
			}
		}
				
		
		/*****************************************/
		/**     ACTION METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Adds an event status.
		 *
		 * @access protected
		 */
		protected function handleDoAdd() {
			if ($this->verifyRequest('POST') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					AppLoader::includeUtility('Sanitizer');
					if (!($arrUnsanitary = Sanitizer::sanitizeArray($this->arrParams))) {
						if (!empty($this->arrParams['status'])) {
							AppLoader::includeUtility('EventHelper');
							if (EventHelper::userStatus(AppRegistry::get('UserLogin')->getUserId(), $this->arrParams['status'])) {
								CoreAlert::alert('The status was posted successfully.');
								$this->blnSuccess = true;
								$this->intStatusCode = 201;
							} else {
								trigger_error(AppLanguage::translate('There was an error posting the status'));
								$this->error();
							}
						} else {
							trigger_error(AppLanguage::translate('Missing status'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('The following value(s) contain illegal data: %s', implode(', ', array_map('htmlentities', $arrUnsanitary))));
						$this->error(400);
					}
				} else {
					trigger_error(AppLanguage::translate('Missing or invalid authentication'));
					$this->error(401);
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Deletes an event by ID.
		 *
		 * @access protected
		 */
		protected function handleDoDelete() {
			if ($this->verifyRequest('DELETE') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					if ($intEventId = str_replace('.' . $this->strFormat, '', AppRegistry::get('Url')->getSegment(3))) {
						$objUserEvent = $this->initModel();
						if ($objUserEvent->loadById($intEventId) && $objUserEvent->count()) {
							if ($objUserEvent->current()->get('userid') == AppRegistry::get('UserLogin')->getUserId()) {
								if ($objUserEvent->destroy()) {
									CoreAlert::alert('The event was deleted successfully.');
									$this->blnSuccess = true;
									$this->intStatusCode = 200;
								} else {
									trigger_error(AppLanguage::translate('There was an error deleting the event'));
									$this->error(400);
								}
							} else {
								trigger_error(AppLanguage::translate('Invalid event permissions'));
								$this->error(401);
							}
						} else {
							trigger_error(AppLanguage::translate('There was an error loading the event data'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing event ID'));
						$this->error(401);
					}
				} else {
					trigger_error(AppLanguage::translate('Missing or invalid authentication'));
					$this->error(401);
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/*****************************************/
		/**     FORMAT METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Formats the events into an array to be encoded.
		 *
		 * @access public
		 * @param object $objUserEvent The list of event records to format
		 * @param boolean $blnCount Whether to add the event count
		 * @return array The events in array format
		 */
		public function formatEvents($objUserEvent, $blnCount = false) {
			$arrEvents = array();
			$strUrlPrefix = AppConfig::get('SiteUrl') . AppConfig::get('BaseUrl');
			
			while(list(, $objUserEventRecord) = $objUserEvent->each()) {
				$strEvent = null;
				$arrMetaData = $objUserEventRecord->get('raw');
				
				switch ($objUserEventRecord->get('type')) {
					case 'friend:connected':
						if ($objUserEventRecord->get('tally') > 1) {
							$strEvent = sprintf('<a href="%s/user/%s/">%s</a> and %d others became friends with <a href="%s/user/%s/">%s</a>', 
								$strUrlPrefix,
								$objUserEventRecord->get('username'),
								$objUserEventRecord->get('username'),
								$objUserEventRecord->get('tally'),
								$strUrlPrefix,
								$arrMetaData['username'],
								$arrMetaData['username']
							);
						} else {
							$strEvent = sprintf('<a href="%s/user/%s/">%s</a> became friends with <a href="%s/user/%s/">%s</a>', 
								$strUrlPrefix,
								$objUserEventRecord->get('username'),
								$objUserEventRecord->get('username'),
								$strUrlPrefix,
								$arrMetaData['username'],
								$arrMetaData['username']
							);
						}
						break;
						
					case 'follow:connected':
						if ($objUserEventRecord->get('tally') > 1) {
							$strEvent = sprintf('<a href="%s/user/%s/">%s</a> and %d others started following <a href="%s/user/%s/">%s</a>', 
								$strUrlPrefix,
								$objUserEventRecord->get('username'),
								$objUserEventRecord->get('username'),
								$objUserEventRecord->get('tally'),
								$strUrlPrefix,
								$arrMetaData['username'],
								$arrMetaData['username']
							);
						} else {
							$strEvent = sprintf('<a href="%s/user/%s/">%s</a> started following <a href="%s/user/%s/">%s</a>', 
								$strUrlPrefix,
								$objUserEventRecord->get('username'),
								$objUserEventRecord->get('username'),
								$strUrlPrefix,
								$arrMetaData['username'],
								$arrMetaData['username']
							);
						}
						break;
						
					case 'user:status':
						$strEvent = sprintf('<a href="%s/user/%s/">%s</a> said <em>%s</em>', 
							$strUrlPrefix,
							$objUserEventRecord->get('username'),
							$objUserEventRecord->get('username'),
							$arrMetaData['status']
						);
						break;
				}
				
				if ($strEvent) {
					if ($objUserEventRecord->get('tally')) {
						$arrEvents[] = array(
							'event'		=> $strEvent,
							'avatars'	=> $this->formatAvatars($objUserEventRecord->get('avatar')),
							'count'		=> $objUserEventRecord->get('tally')
						);
					} else {
						$arrEvents[] = array(
							'id'		=> $objUserEventRecord->get('__id'),
							'userid'	=> $objUserEventRecord->get('userid'),
							'username'	=> $objUserEventRecord->get('username'),
							'avatars'	=> $this->formatAvatars($objUserEventRecord->get('avatar')),
							'event'		=> $strEvent,
							'created'	=> $objUserEventRecord->get('created')
						);
					}
				}
			}
			
			return $arrEvents;
		}
		
		
		/**
		 * Formats an XML node name. This is to prevent child
		 * nodes being named with a generic name.
		 *
		 * @access public
		 * @param string $strNode The name of the node to potentially format
		 * @param string $strParentNode The name of the parent node
		 * @return string The formatted node name
		 */
		public function getXmlNodeName($strNode, $strParentNode) {
			switch ($strParentNode) {
				case 'events':
					$strNode = substr($strParentNode, 0, -1);
					break;
			}
			return $strNode;
		}
	}