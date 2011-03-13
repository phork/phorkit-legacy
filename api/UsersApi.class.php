<?php
	require_once('SiteApi.class.php');
	
	/**
	 * UsersApi.class.php
	 * 
	 * This class handles all the users API calls. This can
	 * either be called via the ApiHelper class or by URL
	 * using the ApiController.
	 *
	 * /api/users/all.json												(GET: all users)
	 * /api/users/filter/by=id/[user id].json							(GET: user by ID)
	 * /api/users/filter/by=ids/[user id,user id].json					(GET: users by IDs)
	 * /api/users/filter/by=username/[username].json					(GET: user by username)
	 * /api/users/suggest.json?term=foo									(GET: auto complete suggestions)
	 *
	 * The following calls require authentication for external use
	 *
	 * /api/users/approved/friends/[username].json						(GET: gets the user's friends by username)
	 * /api/users/approved/following/[username].json					(GET: gets the users being followed by username)
	 * /api/users/approved/followers/[username].json					(GET: gets the users doing the following by username)
	 * /api/users/approved/blocked/[username].json						(GET: gets users blocked by the user by username)
	 *
	 * The following calls require authentication.
	 *
	 * /api/users/me.json												(GET: the currently logged in user data)
	 * /api/users/relationship/by=id/[user id].json						(GET: relationship by user ID) 
	 * /api/users/relationship/by=ids/[user id,user id].json			(GET: relationship by user IDs) 
	 * /api/users/relationship/by=username/[username].json				(GET: relationship by username) 
	 * /api/users/pending/friends.json									(GET: gets the user's pending friend requests)
	 * /api/users/connect/friend/[username].json						(PUT: friend a user; this is reciprocal)
	 * /api/users/connect/follow/[username].json						(PUT: follow a user; this is one way)
	 * /api/users/connect/block/[username].json							(PUT: block a user)
	 * /api/users/disconnect/friend/[username].json						(PUT: unfriend a user; this is reciprocal)
	 * /api/users/disconnect/follow/[username].json						(PUT: unfollow a user; this is one way)
	 * /api/users/disconnect/block/[username].json						(PUT: unblock a user)
	 * /api/users/approve/friend/[username].json						(PUT: approve a friend request)
	 * /api/users/deny/friend/[username].json							(PUT: deny a friend request)
	 *
	 * Additional formatting can be added to determine
	 * what gets returned in the result.
	 *
	 * /include=extended/												(include the extended connection data)
	 * /sort=username/													(sort the results by username)
	 * /sort=displayname/												(sort the results by display name)
	 * /sort=latest/													(sort the results with newest users first)
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
	class UsersApi extends SiteApi {
	
		protected $blnExtended;
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
				'all'			=> 'GetAll',
				'filter'		=> 'GetFiltered',
				'suggest'		=> 'GetSuggestions',
				'me'			=> 'GetMe',
				'relationship'	=> 'GetRelationship',
				'approved'		=> 'GetApproved',
				'pending'		=> 'GetPending',
				
				'connect'		=> 'DoConnect',
				'disconnect'	=> 'DoDisconnect',
				'approve'		=> 'DoApprove',
				'deny'			=> 'DoDeny',
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
		 * Includes and instantiates a user model class.
		 *
		 * @access public
		 * @return object The user model
		 */
		public function initModel() {
			AppLoader::includeModel('UserModel');
			$objUser = new UserModel();
			return $objUser;
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
			
			$intNumResults = (int) !empty($this->arrParams['num']) ? $this->arrParams['num'] : 10;
			$intPage = (int) !empty($this->arrParams['p']) ? $this->arrParams['p'] : 1;
			$arrFilters = array(
				'Conditions' => array(),
				'Limit' => $intNumResults, 
				'Offset' => ($intPage - 1) * $intNumResults
			);
			
			if ($strSortBy = $objUrl->getFilter('sort')) {
				switch ($strSortBy) {
					case 'username':
					case 'displayname':
						$arrFilters['Order'][] = array(
							'Column'	=> $strSortBy,
							'Sort'		=> 'ASC'
						);
						break;
						
					case 'latest':
						$arrFilters['Order'][] = array(
							'Column'	=> 'created',
							'Sort'		=> 'DESC'
						);
						break;
				}
			}
			
			if ($this->blnInternal) {
				$arrInternal = explode(',', $objUrl->getFilter('internal'));
				if (in_array('nocache', $arrInternal)) {
					$this->blnNoCache = true;
				}
			} else {
				$arrInternal = array();
			}
			
			if ($arrInclude = explode(',', $objUrl->getFilter('include'))) {
				$this->blnExtended = in_array('extended', $arrInclude);
			}
			
			return compact('arrFilters', 'arrInternal');
		}
		
		
		/**
		 * Verifies the parameters from the URL, including the
		 * maximum number of results allowed.
		 *
		 * @access protected
		 * @return boolean True if valid
		 */
		protected function verifyParams() {
			$blnResult = true;
			
			if (!empty($this->arrParams['num']) && $this->arrParams['num'] > ($intMaxResults = 50)) {
				$blnResult = false;
				trigger_error(AppLanguage::translate('The maximum number of results allowed is %d', $intMaxResults));
			}
			
			return $blnResult;
		}
		
		
		/*****************************************/
		/**     HANDLER METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Get all the users. Defaults to 10 results but
		 * is configurable.
		 *
		 * @access protected
		 */
		protected function handleGetAll() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				extract($this->getResultParams());
				
				if (!$this->loadFromCache()) {
					$objUser = $this->initModel();
					if ($objUser->load($arrFilters, true)) {
						$this->blnSuccess = true;
						if ($objUser->count()) {
							$this->arrResult = array(
								'users' => $this->formatUsers($objUser),
								'total' => $objUser->getFoundRows()
							);
						} else {
							$this->arrResult = array(
								'users' => array(),
								'total' => $objUser->getFoundRows()
							);
						}
						
						$this->saveToCache($this->intCacheExpire);
					} else {
						trigger_error(AppLanguage::translate('There was an error loading the user data'));
						$this->error();
					}
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Gets the filtered users. Defaults to 10 results but
		 * is configurable.
		 *
		 * @access protected
		 */
		protected function handleGetFiltered() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {		
				extract($this->getResultParams());
				
				if (!$this->loadFromCache()) {
					$objUrl = AppRegistry::get('Url');
					$strFilterBy = $objUrl->getFilter('by');
					$mxdFilter = str_replace('.' . $this->strFormat, '', $objUrl->getSegment(3));
					
					switch ($strFilterBy) {
						case 'ids':
							$mxdFilter = explode(',', $mxdFilter);
							break;
							
						case 'username':
							AppLoader::includeUtility('DataHelper');
							$mxdFilter = DataHelper::getUserIdByUsername($mxdFilter);
							break;
					}
					
					$objUser = $this->initModel();
					$blnResult = $objUser->loadById($mxdFilter);
					
					if ($blnResult) {
						if ($objUser->count()) {
							$this->blnSuccess = true;
							$this->arrResult = array(
								'users' => $this->formatUsers($objUser->getRecords()),
								'total' => $objUser->getFoundRows()
								
							);
						} else {
							$this->arrResult = array(
								'users'	=> array(),
								'total' => 0
							);
						}
						
						$this->saveToCache($this->intCacheExpire);
					} else {
						trigger_error(AppLanguage::translate('There was an error loading the user data'));
						$this->error();
					}
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Gets the auto-complete suggestions for the users.
		 * This ignores all include parameters and only gets
		 * the basic user data.
		 *
		 * @access protected
		 */
		protected function handleGetSuggestions() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				if (!$this->loadFromCache()) {
					$objUser = $this->initModel();
					
					$arrFilters['Conditions'] = array(
						array(
							'Column'	=> 'username',
							'Value'		=> $this->arrParams['term'],
							'Operator'	=> 'begins with'
						)
					);
					
					if ($objUser->load($arrFilters)) {
						$this->blnSuccess = true;
						if ($objUser->count()) {
							$this->arrResult = array(
								'users' => $this->formatUsers($objUser)
							);
						} else {
							$this->arrResult = array(
								'users' => array()
							);
						}
						
						$this->saveToCache($this->intCacheExpire);
					} else {
						trigger_error(AppLanguage::translate('There was an error loading the user data'));
						$this->error(400);
					}
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Gets the currently logged in user's info.
		 *
		 * @access protected
		 */
		protected function handleGetMe() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					$this->blnSuccess = true;
					$this->arrResult = array(
						'users' => $this->formatUsers(AppRegistry::get('UserLogin')->getUserModel())
					);
				} else {
					trigger_error(AppLanguage::translate('Missing or invalid authentication'));
					$this->error(401);
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Gets the logged in user's relationship to the IDs
		 * or username passed.
		 *
		 * @access protected
		 */
		protected function handleGetRelationship() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {			
				if ($this->blnAuthenticated) {
					$objUrl = AppRegistry::get('Url');
					$strFilterBy = $objUrl->getFilter('by');
					$mxdFilter = str_replace('.' . $this->strFormat, '', $objUrl->getSegment(3));
					
					switch ($strFilterBy) {
						case 'id':
							$mxdConnectionId = (int) $mxdFilter;
							break;
						
						case 'ids':
							$mxdConnectionId = explode(',', $mxdFilter);
							break;
							
						case 'username':
							AppLoader::includeUtility('DataHelper');
							$mxdConnectionId = DataHelper::getUserIdByUsername($mxdFilter);
							break;
					}
					
					if (!empty($mxdConnectionId)) {
						$intUserId = AppRegistry::get('UserLogin')->getUserId();
						
						if ($strFilterBy != 'ids') {
							$blnCached = $this->loadFromCache($strNamespace = sprintf(AppConfig::get('UserConnectionNamespace'), $mxdConnectionId));
						}
						
						if (empty($blnCached)) {
							AppLoader::includeModel('UserConnectionModel');
							$objUserConnection = new UserConnectionModel(array('UserJoinExtended' => $this->blnExtended));
							if ($objUserConnection->loadByUserIdAndConnectionId($intUserId, $mxdConnectionId)) {
								$this->blnSuccess = true;
								$this->arrResult = array(
									'relationships' => $this->formatRelationships($objUserConnection, is_array($mxdConnectionId) ? $mxdConnectionId : array($mxdConnectionId), true)
								);
							} else {
								trigger_error(AppLanguage::translate('There was an error loading the relationship data'));
								$this->error(400);
							}
							
							if (isset($blnCached)) {
								$this->saveToCache($this->intCacheExpire, $strNamespace);
							}
						}
					} else {
						trigger_error(AppLanguage::translate('Invalid friend'));
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
		 * Gets the approved connection requests for the user.
		 *
		 * @access protected
		 */
		protected function handleGetApproved() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				if ($this->blnAuthenticated || $this->blnInternal) {
					extract($this->getResultParams());
					
					$objUrl = AppRegistry::get('Url');
					if (($strConnectionType = $objUrl->getSegment(3)) && ($strUsernameSegment = $objUrl->getSegment(4))) {
						$strUsername = str_replace('.' . $this->strFormat, '', $strUsernameSegment);
						
						AppLoader::includeUtility('DataHelper');
						if ($intUserId = DataHelper::getUserIdByUsername($strUsername)) {				
							if (!$this->loadFromCache($strNamespace = sprintf(AppConfig::get('UserConnectionNamespace'), $intUserId))) {
								AppLoader::includeModel('UserConnectionModel');
								switch ($strConnectionType) {
									case 'friends':
										$intStatus = UserConnectionModel::STATUS_FRIEND;
										$strMethod = 'loadByUserId';
										break;
										
									case 'following':
										$intStatus = UserConnectionModel::STATUS_FOLLOW;
										$strMethod = 'loadByUserId';
										break;
										
									case 'followers':
										$intStatus = UserConnectionModel::STATUS_FOLLOW;
										$strMethod = 'loadByConnectionId';
										break;
										
									case 'blocked':
										$intStatus = UserConnectionModel::STATUS_BLOCKED;
										$strMethod = 'loadByUserId';
										break;
								}
								
								if (isset($intStatus)) {
									$arrFilters['Status'] = $intStatus;
									
									$objUserConnection = new UserConnectionModel(array('UserJoinExtended' => $this->blnExtended));
									if ($objUserConnection->$strMethod($intUserId, $arrFilters, true)) {
										$this->blnSuccess = true;
										if ($objUserConnection->count()) {
											$this->arrResult = array(
												'connections' => $this->formatConnections($objUserConnection),
												'total' => $objUserConnection->getFoundRows()
											);
										} else {
											$this->arrResult = array(
												'connections' => array(),
												'total' => 0
											);
										}
										
										$this->saveToCache($this->intCacheExpire, $strNamespace);
									} else {
										trigger_error(AppLanguage::translate('There was an error loading the connections'));
										$this->error(400);
									}
								}
							}
						} else {
							trigger_error(AppLanguage::translate('Invalid user'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing connection type'));
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
		 * Gets the pending connection requests for the logged
		 * in user. Currently only the friend connection requires
		 * approval.
		 *
		 * @access protected
		 */
		protected function handleGetPending() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					extract($this->getResultParams());
					
					if ($strConnectionTypeSegment = AppRegistry::get('Url')->getSegment(3)) {
						$strConnectionType = str_replace('.' . $this->strFormat, '', $strConnectionTypeSegment);
						$intUserId = AppRegistry::get('UserLogin')->getUserId();
						
						if (!$this->loadFromCache($strNamespace = sprintf(AppConfig::get('UserConnectionNamespace'), $intUserId))) {
							AppLoader::includeModel('UserConnectionModel');
							
							switch ($strConnectionType) {
								case 'friends':
									$intStatus = UserConnectionModel::STATUS_FRIEND;
									break;
							}
							
							if (isset($intStatus)) {
								$objUserConnection = new UserConnectionModel();
								
								$arrFilters['Conditions'][] = array(
									'Column'	=> $objUserConnection->getTable() . '.pending',
									'Value'		=> $intStatus,
									'Operator'	=> '&'
								);
								
								if ($objUserConnection->loadByConnectionId($intUserId, $arrFilters, true)) {
									$this->blnSuccess = true;
									if ($objUserConnection->count()) {
										$this->arrResult = array(
											'connections' => $this->formatConnections($objUserConnection),
											'total' => $objUserConnection->getFoundRows()
										);
									} else {
										$this->arrResult = array(
											'connections' => array(),
											'total' => 0
										);
									}
									
									$this->saveToCache($this->intCacheExpire, $strNamespace);
								} else {
									trigger_error(AppLanguage::translate('There was an error loading the pending connections'));
									$this->error(400);
								}
							} else {
								trigger_error(AppLanguage::translate('Invalid status'));
								$this->error(400);
							}
						}
					} else {
						trigger_error(AppLanguage::translate('Missing connection type'));
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
		
		
		/*****************************************/
		/**     ACTION METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Connect to another user as either a friend (mutual),
		 * a follower (one-sided), or block them. This requires 
		 * authentication.
		 *
		 * @access protected
		 */
		protected function handleDoConnect() {
			if ($this->verifyRequest('PUT', true) && $this->verifyParams()) {		
				if ($this->blnAuthenticated) {
					if ($strUsernameSegment = AppRegistry::get('Url')->getSegment(4)) {
						$strUsername = str_replace('.' . $this->strFormat, '', $strUsernameSegment);
						
						AppLoader::includeUtility('DataHelper');
						if ($intUserId = DataHelper::getUserIdByUsername($strUsername)) {				
							AppLoader::includeModel('UserConnectionModel');
							
							switch (AppRegistry::get('Url')->getSegment(3)) {
								case 'friend':
									$intStatus = UserConnectionModel::STATUS_FRIEND;
									$strSuccessMessage = AppLanguage::translate('%s has been sent a friend request.', $strUsername);
									break;
									
								case 'follow':
									$intStatus = UserConnectionModel::STATUS_FOLLOW;
									$strSuccessMessage = AppLanguage::translate('You are now following %s.', $strUsername);
									break;
									
								case 'block':
									$intStatus = UserConnectionModel::STATUS_BLOCKED;
									$strSuccessMessage = AppLanguage::translate('%s has been blocked.', $strUsername);
									break;
							}
							
							if (isset($intStatus)) {
								$blnRequiresApproval = UserConnectionModel::requiresApproval($intStatus);
								
								$objUserConnection = new UserConnectionModel(array('Validate' => true));
								$objUserConnection->import(array(
									'userid'		=> AppRegistry::get('UserLogin')->getUserId(),
									'connectionid'	=> $intUserId
								));
								
								if ($objUserConnection->addConnection($intStatus)) {
									CoreAlert::alert($strSuccessMessage);
									$this->blnSuccess = true;
									$this->intStatusCode = 201;
								} else {
									trigger_error(AppLanguage::translate('There was an error connecting to %s', $strUsername));
									$this->error();
								}
							} else {
								trigger_error(AppLanguage::translate('Invalid status'));
								$this->error(400);
							}
						} else {
							trigger_error(AppLanguage::translate('Invalid username'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing username'));
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
		 * Disconnect from another user by unfriending them 
		 * (mutual), unfollowing them, or unblocking them. This
		 * requires authentication.
		 *
		 * @access protected
		 */
		protected function handleDoDisconnect() {
			if ($this->verifyRequest('PUT', true) && $this->verifyParams()) {		
				if ($this->blnAuthenticated) {
					if ($strUsernameSegment = AppRegistry::get('Url')->getSegment(4)) {
						$strUsername = str_replace('.' . $this->strFormat, '', $strUsernameSegment);
						
						AppLoader::includeUtility('DataHelper');
						if ($intUserId = DataHelper::getUserIdByUsername($strUsername)) {				
							AppLoader::includeModel('UserConnectionModel');
							
							switch (AppRegistry::get('Url')->getSegment(3)) {
								case 'friend':
									$intStatus = UserConnectionModel::STATUS_FRIEND;
									$strSuccessMessage = AppLanguage::translate('%s has been removed from your connections.', $strUsername);
									break;
									
								case 'follow':
									$intStatus = UserConnectionModel::STATUS_FOLLOW;
									$strSuccessMessage = AppLanguage::translate('You are no longer following %s.', $strUsername);
									break;
									
								case 'block':
									$intStatus = UserConnectionModel::STATUS_BLOCKED;
									$strSuccessMessage = AppLanguage::translate('%s has been unblocked.', $strUsername);
									break;
							}
							
							if (isset($intStatus)) {
								$objUserConnection = new UserConnectionModel(array('Validate' => true));
								$objUserConnection->import(array(
									'userid'		=> AppRegistry::get('UserLogin')->getUserId(),
									'connectionid'	=> $intUserId
								));
								
								if ($objUserConnection->removeConnection($intStatus)) {
									CoreAlert::alert($strSuccessMessage);
									$this->blnSuccess = true;
									$this->intStatusCode = 201;
								} else {
									trigger_error(AppLanguage::translate('There was an error disconnecting from %s', $strUsername));
									$this->error();
								}
							}
						} else {
							trigger_error(AppLanguage::translate('Invalid username'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing username'));
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
		 * Approves a connection request from a user. This
		 * requires authentication. Currently only the friend
		 * connection requires approval.
		 *
		 * @access protected
		 */
		protected function handleDoApprove() {
			if ($this->verifyRequest('PUT', true) && $this->verifyParams()) {		
				if ($this->blnAuthenticated) {
					if ($strUsernameSegment = AppRegistry::get('Url')->getSegment(4)) {
						$strUsername = str_replace('.' . $this->strFormat, '', $strUsernameSegment);
						
						AppLoader::includeUtility('DataHelper');
						if ($intUserId = DataHelper::getUserIdByUsername($strUsername)) {				
							AppLoader::includeModel('UserConnectionModel');
							
							switch (AppRegistry::get('Url')->getSegment(3)) {
								case 'friend':
									$intStatus = UserConnectionModel::STATUS_FRIEND;
									$strSuccessMessage = AppLanguage::translate('%s has been approved as a friend.', $strUsername);
									break;
							}
							
							if (isset($intStatus)) {
								$objUserConnection = new UserConnectionModel(array('Validate' => true));
								$objUserConnection->import(array(
									'userid'		=> $intUserId,
									'connectionid'	=> AppRegistry::get('UserLogin')->getUserId()
								));
								
								if ($objUserConnection->approveConnection($intStatus)) {
									CoreAlert::alert($strSuccessMessage);
									$this->blnSuccess = true;
									$this->intStatusCode = 201;
								} else {
									trigger_error(AppLanguage::translate('There was an error approving the connection from %s', $strUsername));
									$this->error();
								}
							}
						} else {
							trigger_error(AppLanguage::translate('Invalid username'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing username'));
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
		 * Denies a connection request from a user. This
		 * requires authentication. Currently only the friend
		 * connection requires approval.
		 *
		 * @access protected
		 */
		protected function handleDoDeny() {
			if ($this->verifyRequest('PUT', true) && $this->verifyParams()) {		
				if ($this->blnAuthenticated) {
					if ($strUsernameSegment = AppRegistry::get('Url')->getSegment(4)) {
						$strUsername = str_replace('.' . $this->strFormat, '', $strUsernameSegment);
						
						AppLoader::includeUtility('DataHelper');
						if ($intUserId = DataHelper::getUserIdByUsername($strUsername)) {				
							AppLoader::includeModel('UserConnectionModel');
							
							switch (AppRegistry::get('Url')->getSegment(3)) {
								case 'friend':
									$intStatus = UserConnectionModel::STATUS_FRIEND;
									$strSuccessMessage = AppLanguage::translate('%s has been denied as a friend.', $strUsername);
									break;
							}
							
							if (isset($intStatus)) {
								$objUserConnection = new UserConnectionModel(array('Validate' => true));
								$objUserConnection->import(array(
									'userid'		=> $intUserId,
									'connectionid'	=> AppRegistry::get('UserLogin')->getUserId()
								));
								
								if ($objUserConnection->denyConnection($intStatus)) {
									CoreAlert::alert($strSuccessMessage);
									$this->blnSuccess = true;
									$this->intStatusCode = 201;
								} else {
									trigger_error(AppLanguage::translate('There was an error denying the connection from %s', $strUsername));
									$this->error();
								}
							}
						} else {
							trigger_error(AppLanguage::translate('Invalid username'));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing username'));
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
		
		
		/*****************************************/
		/**     FORMAT METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Formats the users into an array to be encoded.
		 *
		 * @access public
		 * @param object $objUser The list of user records to format
		 * @return array The users in array format
		 */
		public function formatUsers($objUser) {
			$arrUsers = array();
			
			while (list(, $objUserRecord) = $objUser->each()) {
				$arrUsers[] = array(
					'id'			=> $objUserRecord->get('__id'),
					'username'		=> $objUserRecord->get('username'),
					'displayname'	=> $objUserRecord->get('displayname'),
					'location'		=> $objUserRecord->get('location'),
					'url'			=> $objUserRecord->get('url'),
					'blurb'			=> $objUserRecord->get('blurb'),
					'avatars'		=> $this->formatAvatars($objUserRecord->get('avatar')),
					'noavatar'		=> !$objUserRecord->get('avatar')
				);
			}
			$objUser->rewind();
			
			return $arrUsers;
		}
		
		
		/**
		 * Formats the connections into an array to be
		 * encoded.
		 *
		 * @access public
		 * @param object $objUserConnection The list of user connections to format
		 * @return array The connections in array format
		 */
		public function formatConnections($objUserConnection) {
			$arrConnections = array();
			
			while (list(, $objUserConnectionRecord) = $objUserConnection->each()) {
				$intUserApproved = $objUserConnectionRecord->get('approved');
				
				$arrConnections[] = array(
					'id'			=> $objUserConnectionRecord->get('connectionid'),
					'username'		=> $objUserConnectionRecord->get('username'),
					'displayname'	=> $objUserConnectionRecord->get('displayname'),
					'location'		=> $objUserConnectionRecord->get('location'),
					'avatars'		=> $this->formatAvatars($objUserConnectionRecord->get('avatar')),
					'noavatar'		=> !$objUserConnectionRecord->get('avatar'),
					'friend'		=> !!($intUserApproved & UserConnectionModel::STATUS_FRIEND),
					'follow'		=> !!($intUserApproved & UserConnectionModel::STATUS_FOLLOW),
					'blocked'		=> !!($intUserApproved & UserConnectionModel::STATUS_BLOCKED)
				);
			}
			$objUserConnection->rewind();
			
			return $arrConnections;
		}
		
		
		/**
		 * Formats the relationships into an array to be
		 * encoded.
		 *
		 * @access public
		 * @param object $objUserConnection The list of user connections to format
		 * @param array $arrConnectionIds The connection IDs to include in the result
		 * @param boolean $blnReciprocate Whether to include the reciprocal relationship
		 * @return array The relationships in array format
		 */
		public function formatRelationships($objUserConnection, $arrConnectionIds, $blnReciprocate = true) {
			$arrConnections = array();
		
			$arrConnectionsById = $objUserConnection->getAssociativeList('connectionid');
			foreach ($arrConnectionIds as $intConnectionId) {
				if (!empty($arrConnectionsById[$intConnectionId])) {
					$intUserApproved = $arrConnectionsById[$intConnectionId]->get('approved');
					$intUserPending = $arrConnectionsById[$intConnectionId]->get('pending');
					
					$arrConnections[$intConnectionId] = array(
						'user'			=> array(
							'friend'		=> $this->getRelationshipFlag($intUserApproved, $intUserPending, UserConnectionModel::STATUS_FRIEND),
							'follow'		=> $this->getRelationshipFlag($intUserApproved, $intUserPending, UserConnectionModel::STATUS_FOLLOW),
							'blocked'		=> $this->getRelationshipFlag($intUserApproved, $intUserPending, UserConnectionModel::STATUS_BLOCKED)
						)
					);
					
					if ($blnReciprocate && is_array($arrConnectionRelationship = $arrConnectionsById[$intConnectionId]->get('connection'))) {
						$intConnectionApproved = $arrConnectionRelationship['approved'];
						$intConnectionPending = $arrConnectionRelationship['pending'];
						
						$arrConnections[$intConnectionId]['connection'] = array(
							'friend'		=> $this->getRelationshipFlag($intConnectionApproved, $intConnectionPending, UserConnectionModel::STATUS_FRIEND),
							'follow'		=> $this->getRelationshipFlag($intConnectionApproved, $intConnectionPending, UserConnectionModel::STATUS_FOLLOW),
							'blocked'		=> $this->getRelationshipFlag($intConnectionApproved, $intConnectionPending, UserConnectionModel::STATUS_BLOCKED)
						);
					}
				} else {
					$arrConnections[$intConnectionId] = array(
						'user'			=> array(
							'friend'		=> null,
							'follow'		=> null,
							'blocked'		=> null
						)
					);
					
					if ($blnReciprocate) {
						$arrConnections[$intConnectionId]['connection'] = array(
							'friend'		=> null,
							'follow'		=> null,
							'blocked'		=> null
						);
					}
				}
			}
			return $arrConnections;
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
				case 'users':
				case 'connections':
					$strNode = substr($strParentNode, 0, -1);
					break;
			}
			return $strNode;
		}
		
		
		/**
		 * 
		 *
		 * @access protected
		 * @return integer The connection flag value
		 */
		protected function getRelationshipFlag($intApproved, $intPending, $intStatus) {
			if ($intApproved & $intStatus) {
				return 'approved';
			} else if ($intPending & $intStatus) {
				return 'pending';
			}
		}
	}