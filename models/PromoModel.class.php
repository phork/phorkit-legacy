<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * PromoModel.class.php
	 * 
	 * Used to add, edit, delete and load the promo records
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
	class PromoModel extends CoreDatabaseModel {
		
		protected $strTable = 'promo';
		protected $strPrimaryKey = 'promoid';
		
		protected $arrInsertCols = array('type', 'code', 'sent');
		protected $arrUpdateCols = array('userid', 'code', 'claimed');
		
		
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
						
						'Type'			=> array(
							'Property'		=> 'type',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid type'
						),
						
						'Code'			=> array(
							'Property'		=> 'code',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid code'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
		}
		
		
		/**
		 * Sets any default values before saving including the
		 * promo code and sent date.
		 *
		 * @access public
		 */
		public function setDefaults() {
			if (!$this->current()->get('sent')) {
				$this->current()->set('sent', date(AppRegistry::get('Database')->getDatetimeFormat()));
			}
			if (!$this->current()->get('code')) {
				$this->current()->set('code', md5(time() . rand(1, 10000)));
			}
		}
		

		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by the code.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param string $strCode The promo code to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByCode($strCode, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => 'code',
				'Value'  => $strCode
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the records by the code
		 * and promo type. This does not clear out any previously
		 * loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param string $strCode The promo code to load by
		 * @param string $strType The promo type to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByCodeAndType($strCode, $strType, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => 'code',
				'Value'  => $strCode
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