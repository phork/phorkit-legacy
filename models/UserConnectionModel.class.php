<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * UserConnectionModel.class.php
	 * 
	 * Used to add, edit, delete and load the user connection
	 * records from the database using the database model.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage models
	 */
	class UserConnectionModel extends CoreDatabaseModel {
		
		protected $strRecordClass = 'UserConnectionRecord';
		
		protected $strTable = 'user_connections';
		protected $strPrimaryKey = 'userconnectionid';
		
		protected $arrInsertCols = array('userid', 'connectionid', 'pending', 'approved', 'denied', 'created');
		protected $arrUpdateCols = array('pending', 'approved', 'denied', 'updated');
		
		protected $arrSaving;
		protected $blnSaveHelpers;
		protected $blnSkipReciprocate;
	
		const STATUS_FRIEND = 1;
		const STATUS_FOLLOW = 2;
		const STATUS_BLOCKED = 4;
		
		
		/**
		 * Includes the record class, sets up an iterator 
		 * object to hold the records, and sets up an event 
		 * key which is used to register and run events in
		 * the event object. This also sets up the relations
		 * helper to load relations and a validation helper.
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function __construct($arrConfig = array()) {
			parent::__construct($arrConfig);
			$this->init($arrConfig);
		}
		
		
		/**
		 * Initializes any events and config actions. This 
		 * has been broken out from the constructor so cloned
		 * objects can use it. 
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function init($arrConfig) {		
			if (!empty($arrConfig['Validate'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelValidation')) {
					$this->appendHelper('validation', 'ModelValidation', array(
						'Id'			=> array(
							'Property'		=> $this->strPrimaryKey,
							'Unique'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid ID'
						),
						
						'UserId'		=> array(
							'Property'		=> 'userid',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Missing user ID'
						),
						
						'ConnectionId'	=> array(
							'Property'		=> 'connectionid',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Missing connection ID'
						),
						
						'Pending'		=> array(
							'Property'		=> 'pending',
							'Type'			=> 'integer',
							'Error'			=> 'Invalid pending value'
						),
						
						'Approved'		=> array(
							'Property'		=> 'approved',
							'Type'			=> 'integer',
							'Error'			=> 'Invalid approved value'
						),
						
						'Denied'		=> array(
							'Property'		=> 'denied',
							'Type'			=> 'integer',
							'Error'			=> 'Invalid denied value'
						)
					));
				}
			}
			
			if (!empty($arrConfig['Relations'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelRelations')) {
					$this->appendHelper('relations', 'ModelRelations', array(
						'BelongsToOne'	=> array(
							'User'		=> array(
								'LoadAs'		=> 'user',
								'AutoLoad'		=> false,
								'ClassName'		=> 'UserModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'userid',
										'Property' 	=> 'userid',
										'Operator'	=> '='
									)
								)
							)
						),
						
						'HasOne'		=> array(
							'Connection'	=> array(
								'LoadAs'		=> 'connection',
								'AutoLoad'		=> false,
								'ClassName'		=> 'UserModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'userid',
										'Property' 	=> 'userid',
										'Operator'	=> '='
									)
								)
							)
						)
					));
					
					if (!empty($arrConfig['RelationsAutoLoad'])) {
						$this->initHelper('relations', array('loadAutoLoad'), array(
							'Recursion' => isset($arrConfig['RelationsRecursion']) ? $arrConfig['RelationsRecursion'] : 0
						));
					}
				}
			}
		}
		
		
		/**
		 * Adds the save helpers. This has been broken out
		 * because save helpers don't need to be added all
		 * the time.
		 *
		 * @access protected
		 */
		protected function addSaveHelpers() {
			if (!$this->blnSaveHelpers) {
				if (empty($this->arrConfig['NoSaveHelpers'])) {
					AppEvent::register($this->strEventKey . '.pre-save', array($this, 'setDefaults'));
					AppEvent::register($this->strEventKey . '.post-save', array($this, 'postSave'));
					
					if (!empty($this->arrConfig['Validate'])) {
						$this->initHelper('validation', array('validateAll'));
					}
					
					if (!array_key_exists('cache-bust-save', $this->arrHelpers)) {
						if (AppLoader::includeExtension('helpers/', 'ModelCache')) {
							$this->appendHelper('cache-bust-save', 'ModelCache');
						}
					}
				}
				$this->blnSaveHelpers = true;
			}
		}
		
		
		/**
		 * Returns true if the status requires approval.
		 *
		 * @access public
		 * @param integer $intStatus The status to check
		 * @return boolean True if approval is required
		 * @static
		 */
		static public function requiresApproval($intStatus) {
			switch ($intStatus) {
				case self::STATUS_FOLLOW:
				case self::STATUS_BLOCKED:
					return false;
					
				default:
					return true;
			}
		}
		
		
		/**
		 * Returns true if the status is reciprocal.
		 *
		 * @access public
		 * @param integer $intStatus The status to check
		 * @return boolean True if approval is reciprocal
		 * @static
		 */
		static public function isReciprocal($intStatus) {
			switch ($intStatus) {
				case self::STATUS_FRIEND:
					return true;
					
				default:
					return false;
			}
		}
		
		
		/*****************************************/
		/**     EVENT CALLBACKS                 **/
		/*****************************************/
		
		
		/**
		 * Sets any default values before saving including the
		 * created and updated dates.
		 *
		 * @access public
		 */
		public function setDefaults() {
			$objDb = AppRegistry::get('Database');
			if (!$this->current()->get(self::ID_PROPERTY)) {
				$this->current()->set('created', date($objDb->getDatetimeFormat()));
			}
			$this->current()->set('updated', date($objDb->getDatetimeFormat()));
			
			if (!$this->current()->get('pending')) {
				$this->current()->set('pending', 0);
			}
			if (!$this->current()->get('approved')) {
				$this->current()->set('approved', 0);
			}
			if (!$this->current()->get('denied')) {
				$this->current()->set('denied', 0);
			}
		}
		
		
		/**
		 * Handles the event logging and reciprocal connections
		 * after the connection has been saved. This only executes
		 * if the blnSuccess flag is set and a row was changed.
		 * If a row was inserted the affected rows will be 1 and
		 * if a row was updated the affected rows will be 2.
		 *
		 * @access public
		 * @return array The array of success data
		 */
		public function postSave() {
			$arrFunctionArgs = func_get_args();
			if (!empty($arrFunctionArgs[4])) {
				if ($intAffectedRows = AppRegistry::get('Database')->getAffectedRows()) {
					$objRecord = $this->current();
					if ($intStatus = $this->arrSaving['Params'][0]) {
						$intUserId = $objRecord->get('userid');
						$intConnectionId = $objRecord->get('connectionid'); 
						$blnResult = true;
					
						switch ($this->arrSaving['Function']) {
							case 'addConnection':
								if (!self::requiresApproval($intStatus)) {
									$blnAddConnectedEvent = true;
									if (self::isReciprocal($intStatus)) {
										$blnReciprocateConnect = true;
									}
								}
								break;
								
							case 'removeConnection':
								if (self::isReciprocal($intStatus)) {
									$blnReciprocateDisconnect = true;
								}
								break;
								
							case 'approveConnection':
								$blnAddConnectedEvent = true;
								if (self::isReciprocal($intStatus)) {
									$blnReciprocateConnect = true;
								}
								break;
								
							case 'denyConnection':
								if (self::isReciprocal($intStatus)) {
									$blnReciprocateDisconnect = true;
								}
								break;
						}
						
						if (!$this->blnSkipReciprocate) {
							if (($blnConnect = !empty($blnReciprocateConnect)) || !empty($blnReciprocateDisconnect)) {
								$objReciprocal = clone $this;
								$objReciprocal->setSkipReciprocate(true);
								$objReciprocal->import(array(
									'userid' => $intConnectionId,
									'connectionid' => $intUserId
								));
								
								if ($blnConnect) {
									$blnResult = $objReciprocal->approveConnection($intStatus);
								} else {
									$blnResult = $objReciprocal->removeConnection($intStatus);
								}
								
								unset($objReciprocal);
							}
						}
						
						AppLoader::includeUtility('EventHelper');
						if (!empty($blnAddConnectedEvent)) {
							if ($intStatus == self::STATUS_FRIEND) {
								EventHelper::friendConnection($objRecord);
							} else if ($intStatus == self::STATUS_FOLLOW) {
								EventHelper::followConnection($objRecord);
							}
						}
						
						return array(
							'blnResult' => !empty($blnResult)
						);
					}
				}
			}
		}


		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by the user ID 
		 * or array of IDs passed. This does not clear out any 
		 * previously loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param mixed $mxdUserId The user ID or array of IDs to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserId($mxdUserId, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' 	=> $this->strTable . '.userid',
				'Value' 	=> $mxdUserId,
				'Operator'	=> is_array($mxdUserId) ? 'IN' : '='
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load a record or an array of
		 * records by the connection ID or array of IDs passed.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param mixed $mxdConnectionId The connection ID or array of IDs to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByConnectionId($mxdConnectionId, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' 	=> $this->strTable . '.connectionid',
				'Value' 	=> $mxdConnectionId,
				'Operator'	=> is_array($mxdConnectionId) ? 'IN' : '='
			);
			$arrFilters['NoConnectionJoin'] = true;
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the records by the user ID 
		 * and connection ID or array of connection IDs. This does
		 * not clear out any previously loaded data. That should
		 * be done explicitly.
		 *
		 * @access public
		 * @param mixed $intUserId The user ID to load by
		 * @param mixed $mxdConnectionId The connection ID or array of IDs to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserIdAndConnectionId($intUserId, $mxdConnectionId) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' 	=> $this->strTable . '.userid',
						'Value' 	=> $intUserId,
						'Operator'	=> '='
					),
					array(
						'Column' 	=> $this->strTable . '.connectionid',
						'Value' 	=> $mxdConnectionId,
						'Operator'	=> is_array($mxdConnectionId) ? 'IN' : '='
					)
				),
				'NoUserJoin' => true
			));
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Returns the query to load a record from the database.
		 * Has additional handling to join on the user table
		 * and to filter records based on custom status filters.
		 *
		 * @access protected
		 * @param array $arrFilters The filters to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return array The load query
		 */
		protected function getLoadQuery($arrFilters, $blnCalcFoundRows) {
			$objQuery = AppRegistry::get('Database')->getQuery()->select($blnCalcFoundRows)->from($this->strTable);			
			
			if (empty($this->arrConfig['NoUserJoin']) && empty($arrFilters['NoUserJoin'])) {
				$objQuery->addColumn($this->strTable . '.*');
				$objQuery->addColumn('u.username');
				$objQuery->addColumn('u.displayname');
				$objQuery->addColumn('u.avatar');
				
				if (!empty($this->arrConfig['UserJoinExtended']) || !empty($arrFilters['UserJoinExtended'])) {
					$objQuery->addColumn('u.location');
					$objQuery->addColumn('u.roles');
				}
				
				if ($this->arrLoading['Function'] == 'loadByConnectionId') {
					$objQuery->addTableJoin('users', 'u', array(array($this->strTable . '.userid', 'u.userid')));
				} else {
					$objQuery->addTableJoin('users', 'u', array(array($this->strTable . '.connectionid', 'u.userid')));
				}
			}
			
			if ($blnConnectionJoin = (empty($this->arrConfig['NoConnectionJoin']) && empty($arrFilters['NoConnectionJoin']))) {
				$objQuery->addTableJoin($this->strTable, 'uc2', array(array($this->strTable . '.userid', 'uc2.connectionid'), array($this->strTable . '.connectionid', 'uc2.userid')), 'LEFT JOIN');
				$objQuery->addColumn($this->strTable . '.*');
				$objQuery->addColumn('uc2.pending', 'connection_pending');
				$objQuery->addColumn('uc2.approved', 'connection_approved');
				$objQuery->addColumn('uc2.denied', 'connection_denied');
			}
			
			if (!empty($arrFilters['Status'])) {
				switch ($arrFilters['Status']) {
					case self::STATUS_FRIEND:
						$objQuery->addWhere($this->strTable . '.approved', $arrFilters['Status'], '&');
						$objQuery->addWhere($this->strTable . '.approved & ' . self::STATUS_BLOCKED, self::STATUS_BLOCKED, '!=', true);
						if ($blnConnectionJoin) {
							$objQuery->addWhere('(CASE WHEN uc2.approved IS NULL THEN 1 ELSE uc2.approved & ' . self::STATUS_BLOCKED . ' != ' . self::STATUS_BLOCKED. ' END)', 1, '=', true);
						}
						break;
					
					case self::STATUS_FOLLOW:
						$objQuery->addWhere($this->strTable . '.approved', $arrFilters['Status'], '&');
						$objQuery->addWhere($this->strTable . '.approved & ' . self::STATUS_BLOCKED, self::STATUS_BLOCKED, '!=', true);
						if ($blnConnectionJoin) {
							$objQuery->addWhere('(CASE WHEN uc2.approved IS NULL THEN 1 ELSE uc2.approved & ' . self::STATUS_BLOCKED . ' != ' . self::STATUS_BLOCKED. ' END)', 1, '=', true);
						}
						break;
						
					case self::STATUS_BLOCKED:
						$objQuery->addWhere($this->strTable . '.approved', $arrFilters['Status'], '&');
						break;
				}
				unset($arrFilters['Status']);
			}
			
			if ($this->addQueryFilters($objQuery, $arrFilters)) {
				return $objQuery->buildQuery();
			}
		}
		
		
		/*****************************************/
		/**     SAVE METHODS                    **/
		/*****************************************/
		
	
		/**
		 * Adds a connection type from the current connection.
		 * This adds to either the pending or approved value
		 * and removes the denied data.
		 *
		 * @access public
		 * @param integer $intConnection The connection to add
		 * @return boolean True on success
		 */
		public function addConnection($intConnection) {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->save();
			
			$this->clearSaving();
			return $blnResult;
		}
		
		
		/**
		 * Removes a connection type from the current connection.
		 * This removes the pending, approved, and denied data.
		 *
		 * @access public
		 * @param integer $intConnection The connection to remove
		 * @return boolean True on success
		 */
		public function removeConnection($intConnection) {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->save();
			
			$this->clearSaving();
			return $blnResult;
		}
		
		
		/**
		 * Approves a requested connection type from the current
		 * connection. This adds the approved data and removes
		 * the pending and denied data. This can also be used
		 * to bypass the pending process and immediately add the
		 * connection.
		 *
		 * @access public
		 * @param integer $intConnection The connection to approve
		 * @return boolean True on success
		 */
		public function approveConnection($intConnection) {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->save();
			
			$this->clearSaving();
			return $blnResult;
		}
		
		
		/**
		 * Denies a requested connection type from the current 
		 * connection. This adds the denied data and removes
		 * the pending and approved data.
		 *
		 * @access public
		 * @param integer $intConnection The connection to deny
		 * @return boolean True on success
		 */
		public function denyConnection($intConnection) {
			$arrFunctionArgs = func_get_args();
			$this->setSaving(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->save();
			
			$this->clearSaving();
			return $blnResult;
		}
		
				
		/**
		 * Saves a record to the database. All of the validation
		 * should be handled in an extension using an event.
		 * This has special handling to only allow for the
		 * custom relative methods to be used.
		 *
		 * @access public
		 * @param boolean $blnForceInsert Whether to force insert a record even though it has an ID
		 * @return boolean True on success
		 */
		public function save($blnForceInsert = false) {
			if (!$this->arrSaving['Function']) {
				throw new CoreException('Invalid save method');
			}
			$this->addSaveHelpers();
			return parent::save($blnForceInsert);
		}
		
		
		/**
		 * Returns the query to save the data in the database.
		 * Has special handling to change the query into an
		 * insert or update and to make the status column
		 * updates (pending, approved, denied) relative to
		 * their existing value.
		 *
		 * Only the predefined relative functions can be used
		 * to save the data. Otherwise it makes it too hard
		 * to reciprocate connections.
		 *
		 * @access protected
		 * @param boolean $blnForceInsert Whether to force insert a record if it has an ID
		 * @return string The save query
		 */
		protected function getSaveQuery($blnForceInsert = false) {
			$objQuery = AppRegistry::get('Database')->getQuery();
			
			$objQuery->insert()->table($this->strTable);
			$objQuery->addColumn('userid', $this->current()->get('userid'));
			$objQuery->addColumn('connectionid', $this->current()->get('connectionid'));
			$objQuery->addColumn('created', $this->current()->get('created'));
			
			$objUpdate = clone $objQuery;
			$objUpdate->initUpdateQuery();
			
			switch ($this->arrSaving['Function']) {
				case 'addConnection':
					$intStatus = $this->arrSaving['Params'][0];
					
					if (self::requiresApproval($this->arrSaving['Params'][0])) {
						$objQuery->addColumn('pending', $intStatus);
						
						$objUpdate->addColumn('pending', "pending | {$intStatus}", true);
						$objUpdate->addColumn('approved', "(approved | {$intStatus}) - {$intStatus}", true);
					} else {
						$objQuery->addColumn('approved', $intStatus);
						
						$objUpdate->addColumn('pending', "(pending | {$intStatus}) - {$intStatus}", true);
						$objUpdate->addColumn('approved', "approved | {$intStatus}", true);
					}
					$objUpdate->addColumn('denied', "(denied | {$intStatus}) - {$intStatus}", true);
					break;
					
				case 'removeConnection':
					$intStatus = $this->arrSaving['Params'][0];
					
					$objUpdate->addColumn('pending', "(pending | {$intStatus}) - {$intStatus}", true);
					$objUpdate->addColumn('approved', "(approved | {$intStatus}) - {$intStatus}", true);
					$objUpdate->addColumn('denied', "(denied | {$intStatus}) - {$intStatus}", true);
					break;
					
				case 'approveConnection':
					$intStatus = $this->arrSaving['Params'][0];
					$objQuery->addColumn('approved', $intStatus);
					
					$objUpdate->addColumn('pending', "(pending | {$intStatus}) - {$intStatus}", true);
					$objUpdate->addColumn('approved', "approved | {$intStatus}", true);
					$objUpdate->addColumn('denied', "(denied | {$intStatus}) - {$intStatus}", true);
					break;
					
				case 'denyConnection':
					$intStatus = $this->arrSaving['Params'][0];
					$objQuery->addColumn('denied', $intStatus);
					
					$objUpdate->addColumn('pending', "(pending | {$intStatus}) - {$intStatus}", true);
					$objUpdate->addColumn('approved', "(approved | {$intStatus}) - {$intStatus}", true);
					$objUpdate->addColumn('denied', "denied | {$intStatus}", true);
					break;
			}
			
			$strQuery = $objQuery->buildInsertOrUpdateQuery($objUpdate);
			return $strQuery;
		}
		
		
		/*****************************************/
		/**     CALL METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Returns the name of the saving function that was
		 * called as well as the function arguments.
		 *
		 * @access public
		 * @return array The array of saving data
		 */
		public function getSaving() {
			return $this->arrSaving;
		}
		
		
		/**
		 * Sets the name of the saving function that was called
		 * as well as the function arguments.
		 *
		 * @access public
		 */
		public function setSaving($strFunction, $arrFuncArgs) {
			if (!$this->arrSaving) {
				$this->arrSaving = array(
					'Function'	=> $strFunction,
					'Params'	=> $arrFuncArgs
				);
			}
		}
		
		
		/**
		 * Clears the saving function and args after it has
		 * been called.
		 *
		 * @access public
		 */
		public function clearSaving() {
			$this->arrSaving = null;
		}
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Sets whether or not the reciprocal actions should
		 * be run.
		 *
		 * @access public
		 * @param boolean $blnSkipReciprocate Whether to skip the reciprocation action
		 */
		public function setSkipReciprocate($blnSkipReciprocate) {
			$this->blnSkipReciprocate = $blnSkipReciprocate;
		}
		
		
		/*****************************************/
		/**     MAGIC METHODS                   **/
		/*****************************************/
		
		
		/**
		 * Method called when the object is cloned. Resets
		 * the event key and helpers and then calls init()
		 * to re-initialize them with a new event key.
		 * Also clears the blnSaveHelpers flag.
		 *
		 * @access public
		 */
		public function __clone() {
			parent::__clone();
			$this->blnSaveHelpers = false;
		}
	}