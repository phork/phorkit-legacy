<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * WorkflowModel.class.php
	 * 
	 * Used to add, edit, delete and load the workflow records
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
	class WorkflowModel extends CoreDatabaseModel {
		
		protected $strRecordClass = 'MetaDataRecord';
		
		protected $strTable = 'workflow';
		protected $strPrimaryKey = 'workflowid';
		
		protected $arrInsertCols = array('userid', 'moderatorid', 'itemtype', 'itemid', 'metadata', 'step', 'status', 'created', 'updated');
		protected $arrUpdateCols = array('userid', 'moderatorid', 'itemtype', 'itemid', 'metadata', 'step', 'status', 'created', 'received', 'updated');
		
		protected $arrStepOptions = array();
		protected $arrStatusOptions = array();
		
		
		/**
		 * Initializes the model and sets up the step and status
		 * option arrays.
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function __construct($arrConfig = array()) {
			$this->arrStepOptions = array(
				'drafted'		=> AppLanguage::translate('Drafted'),
				'queued'		=> AppLanguage::translate('Queued'),
				'published'		=> AppLanguage::translate('Published')
			);
			
			$this->arrStatusOptions = array(
				'pending'		=> AppLanguage::translate('Pending'),
				'approved'		=> AppLanguage::translate('Approved'),
				'rejected'		=> AppLanguage::translate('Rejected')
			);
			
			parent::__construct($arrConfig);
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
			parent::init($arrConfig);
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
							'Unique'		=> true,
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid user ID'
						),
				
						'ModeratorId'	=> array(
							'Property'		=> 'moderatorid',
							'Type'			=> 'integer',
							'Error'			=> 'Invalid moderator ID'
						),
						
						'ItemType'		=> array(
							'Property'		=> 'itemtype',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid item type'
						),
						
						'ItemId'		=> array(
							'Property'		=> 'itemid',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid item ID'
						),
						
						'MetaData'		=> array(
							'Property'		=> 'metadata',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid meta data'
						),
						
						'Step'			=> array(
							'Property'		=> 'step',
							'Required'		=> true,
							'Type'			=> 'string',
							'RegEx'			=> '/^' . implode(array_keys($this->arrStepOptions), '|') . '$/',
							'Error'			=> 'Invalid step'
						),
						
						'Status'		=> array(
							'Property'		=> 'status',
							'Required'		=> true,
							'Type'			=> 'string',
							'RegEx'			=> '/^' . implode(array_keys($this->arrStatusOptions), '|') . '$/',
							'Error'			=> 'Invalid status'
						),
						
						'Created'		=> array(
							'Property'		=> 'created',
							'Required'		=> true,
							'Type'			=> 'datetime',
							'Error'			=> 'Invalid created date'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
			
			if (!empty($arrConfig['Relations'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelRelations')) {
					$this->appendHelper('relations', 'ModelRelations', array(
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
							),
							
							'Moderator'		=> array(
								'LoadAs'		=> 'moderator',
								'AutoLoad'		=> false,
								'ClassName'		=> 'UserModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'userid',
										'Property' 	=> 'moderatorid',
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
			if (!$this->current()->get('step')) {
				$arrStepKeys = array_keys($this->arrStepOptions);
				$this->current()->set('step', $arrStepKeys[0]);
			}
			if (!$this->current()->get('status')) {
				$arrStatusKeys = array_keys($this->arrStatusOptions);
				$this->current()->set('status', $arrStatusKeys[0]);
			}
			$this->current()->set('updated', date($objDb->getDatetimeFormat()));
		}
		
		
		/*****************************************/
		/**     SAVE METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Approves the current record and changes it to the next
		 * appropriate step. If the next step is published then
		 * this will publish the record and set the status to
		 * approved.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function approve() {
			$objRecord = $this->current();
		
			$arrStepKeys = array_keys($this->arrStepOptions);
			foreach ($arrStepKeys as $intKey=>$strStep) {
				if ($objRecord->get('step') == $strStep) {
					if (!empty($arrStepKeys[$intKey + 1])) {
						if (empty($arrStepKeys[$intKey + 2])) {
							$objRecord->set('status', 'approved');
							$blnPublish = 1;
						}
						$objRecord->set('step', $arrStepKeys[$intKey + 1]);
					} else {
						trigger_error(AppLanguage::translate('The changes have already been published'));
					}
					break;
				}
			}
			
			if (!empty($blnPublish)) {
				AppLoader::includeModel($strItemType = $objRecord->get('itemtype'));
				$objModel = new $strItemType();
				$objModel->append($objRecord->get('raw'));
				$blnSave = $objModel->save();
			} else {
				$blnSave = true;
			}
			
			return $blnSave && $this->save();
		}
		
		
		/**
		 * Rejects the current record by setting the status
		 * to rejected.
		 *
		 * @access public
		 * @return boolean True on success
		 */
		public function reject() {
			$this->current()->set('status', 'rejected');
			return $this->save();
		}


		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load a record by the item.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param string $strItemType The type of item to load (usually the model name)
		 * @param integer $intItemId The ID of the item to get the workflow for
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByItem($strItemType, $intItemId, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => 'itemtype',
				'Value'  => $strItemType
			);
			$arrFilters['Conditions'][] = array(
				'Column' => 'itemid',
				'Value'  => $intItemId
			);
			
			
			if (!array_key_exists('Order', $arrFilters)) {
				$arrFilters['Order'] = array();
			}
			$arrFilters['Order'][] = array(
				'Column' => 'updated',
				'Sort'  => 'desc'
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Returns the query to load a record from the database.
		 * Has additional handling to join on the user table to
		 * get the submitter.
		 *
		 * @access protected
		 * @param array $arrFilters The filters to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return array The load query
		 */
		protected function getLoadQuery($arrFilters, $blnCalcFoundRows) {
			$objQuery = AppRegistry::get('Database')->getQuery()->select($blnCalcFoundRows)->from($this->strTable);			
			
			if (empty($this->arrConfig['NoUserJoin'])) {
				$objQuery->addColumn($this->strTable . '.*');
				$objQuery->addColumn('u.username');
				$objQuery->addColumn('u.displayname');
				$objQuery->addColumn('u.email');
				$objQuery->addTableJoin('users', 'u', array(array($this->strTable . '.userid', 'u.userid')));
			}
			
			if ($this->addQueryFilters($objQuery, $arrFilters)) {
				return $objQuery->buildQuery();
			}
		}
		
		
		/*****************************************/
		/**     GET & SET METHODS               **/
		/*****************************************/
		
		
		/**
		 * Returns the array of workflow step options.
		 *
		 * @access public
		 * @return array The array of step options
		 */
		public function getStepOptions() {
			return $this->arrStepOptions;
		}
		
		
		/**
		 * Returns the array of workflow status options.
		 *
		 * @access public
		 * @return array The array of status options
		 */
		public function getStatusOptions() {
			return $this->arrStatusOptions;
		}
	}