<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * VerifyModel.class.php
	 * 
	 * Used to add, edit, delete and load the verify records
	 * from the database using the database model.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://www.phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage models
	 */
	class VerifyModel extends CoreDatabaseModel {
		
		protected $strTable = 'verify';
		protected $strPrimaryKey = 'verifyid';
		
		protected $arrInsertCols = array('typeid', 'type', 'token');
		protected $arrUpdateCols = array('verified');
		
		
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
			if (!empty($arrConfig['Validate'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelValidation')) {
					$this->appendHelper('validation', 'ModelValidation', array(
						'Id'			=> array(
							'Property'		=> $this->strPrimaryKey,
							'Unique'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid ID'
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
						),
						
						'Token'			=> array(
							'Property'		=> 'token',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid token'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by the type
		 * ID and verification type. This does not clear out any 
		 * previously loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intTypeId The type ID to load by
		 * @param string $strType The type of verify (eg. user)
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByTypeAndId($intTypeId, $strType, $arrFilters = array(), $blnCalcFoundRows = true) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => 'typeid',
				'Value'  => $intTypeId
			);
			$arrFilters['Conditions'][] = array(
				'Column' => 'type',
				'Value'  => $strType
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
	}