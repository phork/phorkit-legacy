<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * TagModel.class.php
	 * 
	 * Used to add, edit, delete and load the tag records
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
	class TagModel extends CoreDatabaseModel {
		
		protected $strTable = 'tags';
		protected $strPrimaryKey = 'tagid';
		
		protected $arrInsertCols = array('tag', 'abbr', 'banned');
		protected $arrUpdateCols = array('tag', 'abbr', 'banned');
		
		
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
						
						'Tag'			=> array(
							'Property'		=> 'tag',
							'Unique'		=> true,
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Missing or invalid tag',
						),
						
						'Abbr'			=> array(
							'Property'		=> 'abbr',
							'Required'		=> true,
							'Type'			=> 'string',
							'RegEx'			=> '/^[a-z0-9]+$/',
							'Error'			=> 'Invalid abbreviation',
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
			
			if (!empty($arrConfig['Relations'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelRelations')) {
					$this->appendHelper('relations', 'ModelRelations', array(
						'HasMany'		=> array(
							'Items'			=> array(
								'LoadAs'		=> 'items',
								'AutoLoad'		=> false,
								'ClassName'		=> 'ItemTagModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'tagid',
										'Property' 	=> $this->strPrimaryKey,
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
		 * Formats the tag to the abbreviated version.
		 *
		 * @access public
		 * @param string $strTag The tag to clean
		 * @return string The cleaned tag
		 */
		static public function formatTag($strTag) {
			$strTag = iconv('UTF-8', 'ASCII//TRANSLIT', $strTag);
			return strtolower(preg_replace('/[^a-z0-9]/i', '', $strTag));
		}
		
		
		/*****************************************/
		/**     EVENT CALLBACKS                 **/
		/*****************************************/
		
		
		/**
		 * Sets any default values before saving including the
		 * tag abbreviation and banned flag.
		 *
		 * @access public
		 */
		public function setDefaults() {
			if ($strTag = $this->current()->get('tag')) {
				$this->current()->set('abbr', self::formatTag($strTag));
			}
			
			if (!$this->current()->get('banned')) {
				$this->current()->set('banned', 0);
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by the abbr.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param string $strAbbr The tag abbreviation to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByTag($strTag, $arrFilters = array(), $blnCalcFoundRows = false) {
			return $this->loadByAbbr(self::formatTag($strTag), $arrFilters, $blnCalcFoundRows);
		}
		
		
		/**
		 * A shortcut function to load the records by the abbr.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param string $strAbbr The tag abbreviation to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByAbbr($strAbbr, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column' => 'abbr',
				'Value'  => $strAbbr
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
						
			$this->clearLoading();
			return $blnResult;
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
				if (AppConfig::get('FilterRecords') && empty($arrFilters['AutoFilterOff']) && $objQuery->isSelect()) {
					$objQuery->addWhere('banned', 0);
				}
				return true;
			}
		}
	}