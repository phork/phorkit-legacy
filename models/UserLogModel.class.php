<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * UserLogModel.class.php
	 * 
	 * Used to add, edit, delete and load the userlog log records
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
	class UserLogModel extends CoreDatabaseModel {
		
		protected $strTable = 'user_logs';
		protected $strPrimaryKey = 'userlogid';
		
		protected $arrInsertCols = array('userid', 'site', 'method', 'ipaddr', 'created');
		protected $arrUpdateCols = array('userid', 'site', 'method', 'ipaddr');
		
		
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
							'Error'			=> 'Invalid user ID'
						),
						
						'Site'			=> array(
							'Property'		=> 'site',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid site'
						),
						
						'Method'		=> array(
							'Property'		=> 'method',
							'Required'		=> true,
							'Type'			=> 'string',
							'RegEx'			=> '/^(form|cookie)$/i',
							'Error'			=> 'Invalid method'
						),
						
						'Ip'			=> array(
							'Property'		=> 'ipaddr',
							'Type'			=> 'string',
							'Error'			=> 'Invalid IP address'
						),
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
		 * created date.
		 *
		 * @access public
		 */
		public function setDefaults() {
			$objDb = AppRegistry::get('Database');
			if (!$this->current()->get(self::ID_PROPERTY)) {
				$this->current()->set('created', date($objDb->getDatetimeFormat()));
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                   **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by the user
		 * ID. This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param string $intUserId The user ID to load by
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
				'Column' => 'userid',
				'Value'  => $intUserId
			);
			
			if (!array_key_exists('Order', $arrFilters)) {
				$arrFilters['Order'] = array();
			}
			$arrFilters['Order'][] = array(
				'Column'	=> 'created',
				'Sort'		=> 'DESC'
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
	}