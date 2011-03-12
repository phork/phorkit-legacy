<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * UserModel.class.php
	 * 
	 * Used to add, edit, delete and load the user records
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
	class UserModel extends CoreDatabaseModel {
		
		protected $strRecordClass = 'UserRecord';
		
		protected $strTable = 'users';
		protected $strPrimaryKey = 'userid';
		
		protected $arrInsertCols = array('username', 'password', 'email', 'firstname', 'lastname', 'displayname', 'birthdate',  'timezone', 'countryid', 'location', 'url', 'blurb', 'avatar', 'roles', 'verified', 'created', 'updated');
		protected $arrUpdateCols = array('password', 'email', 'firstname', 'lastname', 'displayname', 'birthdate', 'timezone', 'countryid', 'location', 'url', 'blurb', 'avatar', 'roles', 'verified', 'updated');
		
		
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
		
			AppLoader::includeUtility('PasswordHelper');
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
						
						'Username'		=> array(
							'Property'		=> 'username',
							'Unique'		=> true,
							'Required'		=> true,
							'Type'			=> 'string',
							'RegEx'			=> '/^[0-9a-z]{3,15}$/i',
							'Error'			=> 'Invalid username. It must be between 3 and 15 characters in length, containing only a-z and 0-9.',
						),
				
						'Password'		=> array(
							'Property'		=> 'password_plaintext',
							'Required'		=> false,
							'Type'			=> 'string',
							'Error'			=> 'Invalid password. It must be at least 5 characters long.',
							'MinLength'		=> 5
						),
						
						'PasswordAgain'	=> array(
							'Function'		=> 'validatePassword',
							'Error'			=> 'Invalid password verification'
						),
						
						'Email'			=> array(
							'Property'		=> 'email',
							'Unique'		=> true,
							'Required'		=> true,
							'Type'			=> 'email',
							'CheckMx'		=> false,
							'Error'			=> 'Missing or invalid email address'
						),
						
						'FirstName'		=> array(
							'Property'		=> 'firstname',
							'Required'		=> false,
							'Type'			=> 'string',
							'Error'			=> 'Invalid first name'
						),
						
						'LastName'		=> array(
							'Property'		=> 'lastname',
							'Required'		=> false,
							'Type'			=> 'string',
							'Error'			=> 'Invalid last name'
						),
						
						'DisplayName'	=> array(
							'Property'		=> 'displayname',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Missing or invalid display name'
						),
						
						'Birthdate'		=> array(
							'Property'		=> 'birthdate',
							'Required'		=> false,
							'Type'			=> 'string',
							'RegEx'			=> '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
							'Error'			=> 'Invalid birthdate'
						),
						
						'Timezone'		=> array(
							'Property'		=> 'timezone',
							'Required'		=> false,
							'Type'			=> 'float',
							'Error'			=> 'Invalid timezone'
						),
						
						'CountryId'		=> array(
							'Property'		=> 'countryid',
							'Required'		=> false,
							'Type'			=> 'integer',
							'Error'			=> 'invalid country'
						),
						
						'Location'		=> array(
							'Property'		=> 'location',
							'Required'		=> false,
							'Type'			=> 'string',
							'Error'			=> 'Invalid location'
						),
						
						'Roles'			=> array(
							'Property'		=> 'roles',
							'Type'			=> 'integer',
							'Error'			=> 'Invalid roles'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
			
			if (!empty($arrConfig['Relations'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelRelations')) {
					$this->appendHelper('relations', 'ModelRelations', array(
						'HasMany'		=> array(
							'Roles'			=> array(
								'LoadAs'		=> 'rolelist',
								'AutoLoad'		=> true,
								'ClassName'		=> 'RoleModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> '1 << roleid - 1',
										'Property' 	=> 'roles',
										'Operator'	=> '&',
										'NoQuote'	=> true
									)
								)
							)
						),
						
						'HasOne'		=> array(
							'Country'		=> array(
								'LoadAs'		=> 'country',
								'AutoLoad'		=> false,
								'ClassName'		=> 'CountryModel',
								'Dependent'		=> false,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'countryid',
										'Property' 	=> 'countryid',
										'Operator'	=> '='
									)
								)
							),
							
							'Facebook'		=> array(
								'LoadAs'		=> 'facebook',
								'AutoLoad'		=> false,
								'ClassName'		=> 'FacebookModel',
								'Dependent'		=> true,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'userid',
										'Property' 	=> $this->strPrimaryKey,
										'Operator'	=> '='
									)
								)
							),
							
							'Twitter'		=> array(
								'LoadAs'		=> 'twitter',
								'AutoLoad'		=> false,
								'ClassName'		=> 'TwitterModel',
								'Dependent'		=> true,
								'Conditions'	=> array(
									array(
										'Column' 	=> 'userid',
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
			
			if (!$this->current()->get('displayname')) {
				$this->current()->set('displayname', $this->current()->get('username'));
			}
		}
		
		
		/**
		 * Returns any incomplete fields that the user still needs
		 * to fill out.
		 *
		 * @access public
		 * @return array The array of missing fields
		 */
		public function getMissingFields() {
			$arrMissing = array();
			
			$objRecord = $this->current();
			foreach (array('email', 'firstname', 'lastname', 'countryid') as $strField) {
				if (!$objRecord->get($strField)) {
					$arrMissing[] = $strField;
				}
			}
			
			if (!$objRecord->get('birthdate-year') || !$objRecord->get('birthdate-month') || !$objRecord->get('birthdate-day')) { 
				$arrMissing[] = 'birthdate';
			}
			
			return $arrMissing;
		}


		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load a record or an array
		 * of records by the ID or array of IDs passed.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param mixed $mxdId The ID or array of IDs to load by
		 * @param array $arrFilters Any additional filters as well as the limits
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadById($mxdId, $arrFilters = array(), $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			if (!array_key_exists('Conditions', $arrFilters)) {
				$arrFilters['Conditions'] = array();
			}
			$arrFilters['Conditions'][] = array(
				'Column'	=> $this->strTable . '.' . $this->strPrimaryKey,
				'Value' 	=> $mxdId,
				'Operator'	=> is_array($mxdId) ? 'IN' : '='
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load a record by username.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param string $strUsername The username to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUsername($strUsername) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' => 'username',
						'Value'  => $strUsername
					)
				)		
			));
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load a record by email address.
		 * This does not clear out any previously loaded data.
		 * That should be done explicitly.
		 *
		 * @access public
		 * @param string $strEmail The email address to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadByEmail($strEmail) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' => 'email',
						'Value'  => $strEmail
					)
				)		
			));
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Loads users by their Facebook ID.
		 *
		 * @access public
		 * @param mixed $mxdFacebookId The ID or array of IDs to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByFacebookId($mxdFacebookId, $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column'	=> 'f.externalid',
						'Value' 	=> $mxdFacebookId,
						'Operator'	=> is_array($mxdFacebookId) ? 'IN' : '='
					)
				)
			), $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Loads users by their Twitter ID.
		 *
		 * @access public
		 * @param mixed $mxdTwitterId The ID or array of IDs to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return boolean True if the query executed successfully
		 */
		public function loadByTwitterId($mxdTwitterId, $blnCalcFoundRows = false) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column'	=> 't.externalid',
						'Value' 	=> $mxdTwitterId,
						'Operator'	=> is_array($mxdTwitterId) ? 'IN' : '='
					)
				)
			), $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * Returns the query to load a record from the database.
		 * Has additional handling to join on the friends table.
		 *
		 * @access protected
		 * @param array $arrFilters The filters to load by
		 * @param boolean $blnCalcFoundRows Whether to calculate the total number of matching rows
		 * @return array The load query
		 */
		protected function getLoadQuery($arrFilters, $blnCalcFoundRows) {
			$objQuery = AppRegistry::get('Database')->getQuery()->select($blnCalcFoundRows)->from($this->strTable);			
			$objQuery->addColumn($this->strTable . '.*');
			
			switch ($this->arrLoading['Function']) {
				case 'loadByFacebookId':
					$objQuery->addColumn('f.externalid');
					$objQuery->addColumn($objQuery->buildFunction('CONCAT', "f.firstname, ' ', f.lastname"), 'facebook_displayname');
					$objQuery->addTableJoin('facebook', 'f', array(array($this->strTable . '.userid', 'f.userid')));
					break;
					
				case 'loadByTwitterId':
					$objQuery->addColumn('t.externalid');
					$objQuery->addColumn('t.username', 'twitter_username');
					$objQuery->addColumn('t.displayname', 'twitter_displayname');
					$objQuery->addTableJoin('twitter', 't', array(array($this->strTable . '.userid', 't.userid')));
					break;
			}
			
			if ($this->addQueryFilters($objQuery, $arrFilters)) {
				return $objQuery->buildQuery(); 
			}
		}
				
		
		/*****************************************/
		/**     VALIDATION METHODS              **/
		/*****************************************/
		
		
		/**
		 * Validates that password exists for a new user and
		 * that the password matches the verification.
		 *
		 * @access public
		 * @return boolean True if valid
		 */
		public function validatePassword() {
			$objRecord = $this->current();
			if (!$objRecord->get(self::ID_PROPERTY) && !$objRecord->get('password_plaintext')) {
				return false;
			}
			return $objRecord->get('password_plaintext_again') == $objRecord->get('password_plaintext');
		}
	}