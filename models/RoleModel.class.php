<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * RoleModel.class.php
	 * 
	 * Used to add, edit, delete and load the role records
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
	class RoleModel extends CoreDatabaseModel {
		
		protected $strRecordClass = 'RoleRecord';
		
		protected $strTable = 'roles';
		protected $strPrimaryKey = 'roleid';
		
		protected $arrInsertCols = array('name', 'rank');
		protected $arrUpdateCols = array('name', 'rank');
		
		
		/**
		 * Includes the record class, sets up an iterator 
		 * object to hold the records, and sets up an event 
		 * key which is used to register and run events in
		 * the event object.
		 *
		 * @access public
		 * @param array $arrConfig The config vars, including which helpers to use
		 */
		public function __construct($arrConfig = array()) {
			parent::__construct($arrConfig);
			$this->init($arrConfig);
			
			AppLoader::includeUtility('Permissions');
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
						
						'Name'			=> array(
							'Property'		=> 'name',
							'Unique'		=> true,
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid name'
						),
						
						'Rank'			=> array(
							'Property'		=> 'rank',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid rank'
						),
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
		}
	}