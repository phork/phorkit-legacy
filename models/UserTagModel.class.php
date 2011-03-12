<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * UserTagModel.class.php
	 * 
	 * Used to add, edit, delete and load the user tag records
	 * from the database using the database model.
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
	class UserTagModel extends CoreDatabaseModel {
		
		protected $strTable = 'user_tags';
		protected $strPrimaryKey = 'usertagid';
		
		protected $arrInsertCols = array('userid', 'tagid', 'weight');
		protected $arrUpdateCols = array('weight');
		
		
		/**
		 * Includes the record class, sets up an iterator 
		 * object to hold the records, and sets up an event 
		 * key which is used to register and run events in
		 * the event object. This also sets up the relations
		 * helper to load relations.
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
							'Error'			=> 'Invalid user ID',
						),
						
						'TagId'			=> array(
							'Property'		=> 'tagid',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid tag ID',
						),
						
						'Weight'		=> array(
							'Property'		=> 'weight',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid weight',
						)				
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
			
			if (!empty($arrConfig['Relations'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelRelations')) {
					$this->appendHelper('relations', 'ModelRelations', array(
						'HasOne'		=> array(
							'Tag'			=> array(
								'LoadAs'		=> 'tag',
								'AutoLoad'		=> false,
								'ClassName'		=> 'TagModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'tagid',
										'Property' 	=> 'tagid',
										'Operator'	=> '='
									)
								)
							)
						),
						
						'BelongsToOne'	=> array(
							'User'			=> array(
								'LoadAs'		=> 'user',
								'AutoLoad'		=> false,
								'ClassName'		=> 'UserModel',
								'Dependent'		=> true,
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
			if (empty($this->arrConfig['NoSaveHelpers'])) {
				if (!array_key_exists('cache-bust-save', $this->arrHelpers)) {
					if (AppLoader::includeExtension('helpers/', 'ModelCache')) {
						$this->appendHelper('cache-bust-save', 'ModelCache');
						$this->initHelper('cache-bust-save', array('postSave'));
					}
				}
			}
		}


		/*****************************************/
		/**     EVENT CALLBACKS                 **/
		/*****************************************/
		
		
		/**
		 * Sets any default values before saving including the
		 * weight. Also sets the tag ID if only a tag has been
		 * set.
		 *
		 * @access public
		 */
		public function setDefaults() {
			if (!$this->current()->get('tagid') && $strTag = $this->current()->get('tag')) {
				AppLoader::includeModel('TagModel');
				$objTag = new TagModel();
				if ($objTag->loadByTag($strTag, array('AutoFilterOff' => true))) {
					if (!$objTag->count()) {
						$objTag->import(array(
							'tag' => $strTag
						));
						
						if ($objTag->save()) {
							$this->current()->set('tagid', $objTag->first()->get('tagid'));
						}
					} else {
						$this->current()->set('tagid', $objTag->first()->get('tagid'));
					}
				}
			}
			
			if (!$this->current()->get('weight')) {
				$this->current()->set('weight', 1);
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by the user
		 * ID. This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserId($intUserId, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => $this->strTable . '.userid',
				'Value'  => $intUserId
			);
			
			if (!array_key_exists('Order', $arrFilters)) {
				$arrFilters['Order'] = array();
			}
			$arrFilters['Order'][] = array(
				'Column'	=> 'weight',
				'Sort'		=> 'DESC'
			);
			$arrFilters['Order'][] = array(
				'Column'	=> 'updated',
				'Sort'		=> 'ASC'
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the records by the tag
		 * ID. This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intTagId The tag ID to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByTagId($intTagId, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => $this->strTable . '.tagid',
				'Value'  => $intTagId
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the records by the user and
		 * tag. This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to load by
		 * @param string $strTag The tag to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserIdAndTag($intUserId, $strTag) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			AppLoader::includeModel('TagModel');
			$strAbbr = TagModel::formatTag($strTag);
			
			$arrFilters['AutoFilterOff'] = true;
			$arrFilters['Conditions'] = array(
				array(
					'Column' => $this->strTable . '.userid',
					'Value'  => $intUserId
				),
				array(
					'Column' => 't.abbr',
					'Value'  => $strAbbr
				)
			);
			
			$blnResult = $this->load($arrFilters);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the latest records.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadLatest($arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Order', $arrFilters)) {
				$arrFilters['Order'] = array();
			}
			$arrFilters['Order'][] = array(
				'Column'	=> 'updated',
				'Sort'		=> 'DESC'
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Returns the query to load a record from the database.
		 * Has additional handling to join on the tag table.
		 *
		 * @access protected
		 * @param array $arrFilters The filters to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return array The load query
		 */
		protected function getLoadQuery($arrFilters, $blnCalcFoundRows) {
			$objQuery = AppRegistry::get('Database')->getQuery()->select($blnCalcFoundRows)->from($this->strTable);			
			
			$objQuery->addColumn('t.*');
			$objQuery->addTableJoin('tags', 't', array(array($this->strTable . '.tagid', 't.tagid')));
			
			switch ($this->arrLoading['Function']) {
				case 'loadLatest':
					$objQuery->addColumn($objQuery->buildFunction('COUNT', '*'), 'tally');
					$objQuery->addGroupBy('tagid');
					break;
					
				default:
					$objQuery->addColumn($this->strTable . '.*');
					break;
			}
			
			if ($this->addQueryFilters($objQuery, $arrFilters)) {
				return $objQuery->buildQuery();
			}
		}
		
		
		/**
		 * Adds the various parameters to the query object
		 * passed. Used to add where, order by, limit, etc.
		 * Has special handling to filter out banned tags
		 * on the live site.
		 *
		 * @access protected
		 * @param object $objQuery The query object to add the filters to
		 * @param array $arrFilters The filters to add
		 * @return boolean True if the filters were all valid
		 */
		protected function addQueryFilters($objQuery, $arrFilters) {
			if (parent::addQueryFilters($objQuery, $arrFilters)) {
				if (AppConfig::get('FilterRecords') && empty($arrFilters['AutoFilterOff']) && empty($this->arrConfig['NoTagJoin']) && $objQuery->isSelect()) {
					$objQuery->addWhere('t.banned', 0);
				}
				return true;
			}
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
	}