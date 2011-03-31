<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * CountryModel.class.php
	 * 
	 * Used to add, edit, delete and load the country records
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
	class CountryModel extends CoreDatabaseModel {
		
		protected $strTable = 'countries';
		protected $strPrimaryKey = 'countryid';
		
		protected $arrInsertCols = array('country', 'abbr2', 'abbr3', 'continent');
		protected $arrUpdateCols = array('country', 'abbr2', 'abbr3', 'continent');
		
		protected $arrContinents = array();
		
		
		/**
		 * Initializes the model and sets up the continents.
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function __construct($arrConfig = array()) {
			parent::__construct($arrConfig);
			
			$this->arrContinents = array(
				'AF' => 'Africa',
				'AN' => 'Antarctica',
				'AS' => 'Asia',
				'EU' => 'Europe',
				'NA' => 'North America',
				'OC' => 'Oceania',
				'SA' => 'South America'
			);
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
			
			if (!empty($arrConfig['Validate'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelValidation')) {
					$this->appendHelper('validation', 'ModelValidation', array(
						'Id'			=> array(
							'Property'		=> $this->strPrimaryKey,
							'Unique'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid ID'
						),
						
						'Country'		=> array(
							'Property'		=> 'country',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Missing or invalid country'
						),
						
						'Abbr2'			=> array(
							'Property'		=> 'abbr2',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Missing or invalid 2 char abbreviation'
						),
						
						'Abbr3'			=> array(
							'Property'		=> 'abbr3',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Missing or invalid 3 char abbreviation'
						),
						
						'Continent'		=> array(
							'Property'		=> 'continent',
							'Required'		=> true,
							'Type'			=> 'string',
							'RegEx'			=> '/^' . implode(array_keys($this->arrContinents), '|') . '$/',
							'Error'			=> 'Missing or invalid continent'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
		}
		
		
		/**
		 * Adds the various parameters to the query object
		 * passed. Used to add where, order by, limit, etc.
		 * Defaults the order by to the country name.
		 *
		 * @access protected
		 * @param object $objQuery The query object to add the filters to
		 * @param array $arrFilters The filters to add
		 * @return boolean True if the filters were all valid
		 */
		protected function addQueryFilters($objQuery, $arrFilters) {
			if (parent::addQueryFilters($objQuery, $arrFilters)) {
				$objQuery->addOrderBy('country');
				return true;
			}
		}
	}