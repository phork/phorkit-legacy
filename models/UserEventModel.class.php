<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * UserEventModel.class.php
	 * 
	 * Used to add, edit, delete and load the user event
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
	class UserEventModel extends CoreDatabaseModel {
		
		protected $strRecordClass = 'MetaDataRecord';
		
		protected $strTable = 'user_events';
		protected $strPrimaryKey = 'usereventid';
		
		protected $arrInsertCols = array('userid', 'typeid', 'type', 'typegroup', 'metadata', 'created');
		protected $arrUpdateCols = array('metadata');
		
		protected $blnSaveHelpers;
		
		
		/**
		 * Includes the record class, sets up an iterator 
		 * object to hold the records, and sets up an event 
		 * key which is used to register and run events in
		 * the event object. This also sets up the validation
		 * helper.
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
			AppEvent::register($this->strEventKey . '.pre-save', array($this, 'setDefaults'));
			
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
							'Error'			=> 'Invalid user ID'
						),
						
						'TypeId'		=> array(
							'Property'		=> 'typeid',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid type ID'
						),
						
						'Type'			=> array(
							'Property'		=> 'type',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid type'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
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
					if (!array_key_exists('cache-bust-save', $this->arrHelpers)) {
						if (AppLoader::includeExtension('helpers/', 'ModelCache')) {
							$this->appendHelper('cache-bust-save', 'ModelCache');
							$this->initHelper('cache-bust-save', array('postSave'));
						}
					}
					$this->blnSaveHelpers = true;
				}
			}
		}
		
		
		/*****************************************/
		/**     EVENT CALLBACKS                 **/
		/*****************************************/
		
		
		/**
		 * Sets any default values before saving including the
		 * created date.
		 *
		 * @access public
		 */
		public function setDefaults() {
			$objDb = AppRegistry::get('Database');
			if (!$this->current()->get('created')) {
				$this->current()->set('created', date($objDb->getDatetimeFormat()));
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by user ID. 
		 * This does not clear out any previously loaded data. 
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserId($intUserId, $arrFilters = array(), $blnCalcFoundRows = true) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => $this->strTable . '.userid',
				'Value'  => $intUserId
			);
				
			$this->addDefaultOrder($arrFilters);
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the records of a user's
		 * connections by user ID. This does not clear out any 
		 * previously loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to load connections by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadConnectionsByUserId($intUserId, $arrFilters = array(), $blnCalcFoundRows = true) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => 'uc.userid',
				'Value'  => $intUserId
			);
			
			$this->addDefaultOrder($arrFilters);
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the records of a user
		 * and their connections by user ID. This cannot have
		 * any additional conditional filters. This does not 
		 * clear out any previously loaded data. That should
		 * be done explicitly.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadCombinedByUserId($intUserId, $arrFilters = array(), $blnCalcFoundRows = true) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$arrFilters['Conditions'] = array(
				array(
					'Column' => 'uc.userid',
					'Value'  => $intUserId
				),
				array(
					'Column' => $this->strTable . '.userid',
					'Value'  => $intUserId
				)
			);
			
			$this->addDefaultOrder($arrFilters);
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the records by the type
		 * ID and event type. This does not clear out any 
		 * previously loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intTypeId The type ID to load by
		 * @param string $strType The type of event (eg. item)
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByTypeIdAndType($intTypeId, $strType, $arrFilters = array(), $blnCalcFoundRows = true) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => $this->strTable . '.typeid',
				'Value'  => $intTypeId
			);
			$arrFilters['Conditions'][] = array(
				'Column' => $this->strTable . '.type',
				'Value'  => $strType
			);
			
			$this->addDefaultOrder($arrFilters);
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the latest records. This 
		 * does not clear out any previously loaded data. 
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param boolean $blnGrouped Whether to group similar events using the typegroup data
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadLatest($blnGrouped = false, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$this->addDefaultOrder($arrFilters);
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Returns the query to load a record from the database.
		 * Has additional handling to join on the user table.
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
				$objQuery->addTableJoin('users', 'u', array(array($this->strTable . '.userid', 'u.userid')));
			}
			
			switch ($this->arrLoading['Function']) {
				case 'loadConnectionsByUserId':
					$objQuery->addTableJoin('user_connections', 'uc', array(array($this->strTable . '.userid', 'uc.connectionid')));
					break;
					
				case 'loadCombinedByUserId':
					$objQuery->addTableJoin('user_connections', 'uc', array(array($this->strTable . '.userid', 'uc.connectionid')), 'LEFT JOIN');
					$objQuery->addDistinct();
					$objQuery->useWhereOr();
					break;
					
				case 'loadLatest':
					if (!empty($this->arrLoading['Params'][0])) {
						$objQuery->addColumn($objQuery->buildFunction('COUNT', '*'), 'tally');
						$objQuery->addGroupBy('type');
						$objQuery->addGroupBy('typegroup');
						$objQuery->addGroupBy($objQuery->buildFunction('DATE', $this->strTable . '.created'));
					}
					break;
			}
			
			if ($this->addQueryFilters($objQuery, $arrFilters)) {
				return $objQuery->buildQuery();
			}
		}
		
		
		/**
		 * Adds the default order to the filters array.
		 *
		 * @access protected
		 * @param array $arrFilters The array of existing filters
		 */
		protected function addDefaultOrder(&$arrFilters) {
			if (!array_key_exists('Order', $arrFilters)) {
				$arrFilters['Order'] = array();
			}
			$arrFilters['Order'][] = array(
				'Column'	=> $this->strTable . '.created',
				'Sort'		=> 'DESC'
			);
		}
		
		
		/*****************************************/
		/**     SAVE METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Saves a record to the database. Has additional
		 * handling to initialize any save helpers.
		 *
		 * @access public
		 * @param boolean $blnForceInsert Whether to force insert a record even though it has an ID
		 * @return boolean True on success
		 */
		public function save($blnForceInsert = false) {
			$this->addSaveHelpers();
			return parent::save($blnForceInsert);
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