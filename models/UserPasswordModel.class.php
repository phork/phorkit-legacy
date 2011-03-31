<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * UserPasswordModel.class.php
	 * 
	 * Used to add, edit, delete and load the user password
	 * records from the database using the database model.
	 * This is for forgotten passwords.
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
	class UserPasswordModel extends CoreDatabaseModel {
		
		protected $strTable = 'user_passwords';
		protected $strPrimaryKey = 'passwordid';
		
		protected $arrInsertCols = array('userid', 'password', 'created');
		protected $arrUpdateCols = array();
		
		
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
							'Required'		=> true,
							'Type'			=> 'integer',
							'Error'			=> 'Invalid user ID'
						),
						
						'Password'		=> array(
							'Property'		=> 'password',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid password'
						)
					));
					
					$this->initHelper('validation', array('validateAll'));
				}
			}
		}
		
		
		/**
		 * Sets any default values before saving including the
		 * password and created date.
		 *
		 * @access public
		 */
		public function setDefaults() {
			if (!$this->current()->get('created')) {
				$this->current()->set('created', date(AppRegistry::get('Database')->getDatetimeFormat()));
			}
			if (!$this->current()->get('password')) {
				$this->current()->set('password', md5(time() . rand(1, 10000)));
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load a valid temporary password.
		 * The validity is determined by the date. This does not 
		 * clear out any previously loaded data. That should be 
		 * done explicitly.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to load by
		 * @param string $strPassword The password to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadValidByUserIdAndPassword($intUserId, $strPassword) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' 	=> 'userid',
						'Value'  	=> $intUserId
					),
					array(
						'Column' 	=> 'password',
						'Value'  	=> $strPassword
					),
					array(
						'Column' 	=> 'created',
						'Value'  	=> date(AppRegistry::get('Database')->getDatetimeFormat(), time() + AppConfig::get('TempPasswordTTL')),
						'Operator'	=> '<='
					)
				)
			));
						
			$this->clearLoading();
			return $blnResult;
		}
	}