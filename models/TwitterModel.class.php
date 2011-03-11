<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * TwitterModel.class.php
	 * 
	 * Used to add, edit, delete and load the twitter records
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
	class TwitterModel extends CoreDatabaseModel {
		
		protected $strTable = 'twitter';
		protected $strPrimaryKey = 'twitterid';
		
		protected $arrInsertCols = array('userid', 'externalid', 'secret', 'token', 'username', 'displayname', 'email', 'location', 'url', 'blurb', 'avatar', 'created');
		protected $arrUpdateCols = array('userid', 'secret', 'token');
		
		
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
						
						'ExternalId'	=> array(
							'Property'		=> 'externalid',
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid external ID'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
			
			if (!empty($arrConfig['Relations'])) {
				if (AppLoader::includeExtension('helpers/', 'ModelRelations')) {
					$this->appendHelper('relations', 'ModelRelations', array(
						'HasOne'		=> array(
							'User'			=> array(
								'LoadAs'		=> 'user',
								'AutoLoad'		=> true,
								'ClassName'		=> 'UserModel',
								'Dependent'		=> false,
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
		 * Sets any default values before saving including the
		 * created date.
		 *
		 * @access public
		 */
		public function setDefaults() {
			if (!$this->current()->get(self::ID_PROPERTY)) {
				$this->current()->set('created', date(AppRegistry::get('Database')->getDatetimeFormat()));
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load the records by the
		 * user ID. This does not clear out any previously
		 * loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserId($intUserId) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' => 'userid',
						'Value'  => $intUserId
					)
				)		
			));
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load the records by the
		 * external ID. This does not clear out any previously
		 * loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadByExternalId($intExternalId) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' => 'externalid',
						'Value'  => $intExternalId
					)
				)		
			));
			
			$this->clearLoading();
			return $blnResult;
		}
	}