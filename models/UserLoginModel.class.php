<?php
	require_once('php/core/CoreDatabaseModel.class.php');

	/**
	 * UserLoginModel.class.php
	 * 
	 * Used to add, edit, delete and load the user login
	 * record from the database using the database model.
	 * This allows cookied, persistant logging in.
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
	class UserLoginModel extends CoreDatabaseModel {
		
		protected $strTable = 'user_login';
		protected $strPrimaryKey = 'userloginid';
		
		protected $arrInsertCols = array('userid', 'publickey', 'privatekey', 'created', 'accessed');
		protected $arrUpdateCols = array('accessed');
		
		
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
			AppEvent::register($this->strEventKey . '.post-save', array($this, 'flushExpired'));
			
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
						
						'PrivateKey'	=> array(
							'Property'		=> 'privatekey',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid private key'
						),
						
						'PublicKey'		=> array(
							'Property'		=> 'publickey',
							'Required'		=> true,
							'Type'			=> 'string',
							'Error'			=> 'Invalid public key'
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
								'LoadAs'		=> 'users',
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
		
		
		/**
		 * Generates a private key to use based on the user ID
		 * and the user's browser data. This should only use data
		 * that doesn't change (eg. browser type and OS). Ideally
		 * this will use the browscap.ini to get the platform and
		 * browser, but if that hasn't been installed the user
		 * agent will be used. In this case when the user agent 
		 * changes (when the browser or OS is upgraded) then the
		 * user may have to log in again.
		 *
		 * @access public
		 * @param integer $intUserId The user ID to generate the key for
		 * @return string The private key
		 */
		public function getPrivateKey($intUserId) {
			if (@ini_get('browscap')) {
				$objBrowser = get_browser();
				$strIdentity = $objBrowser->platform . $objBrowser->parent;
			} else {
				$strIdentity = $_SERVER['HTTP_USER_AGENT'];
			}
			return md5($intUserId . $strIdentity . AppConfig::get('HashKey'));
		}
		
		
		/*****************************************/
		/**     EVENT CALLBACKS                 **/
		/*****************************************/		
		
		
		/**
		 * Sets any default values before saving including the
		 * created and accessed dates and the keys.
		 *
		 * @access public
		 */
		public function setDefaults() {
			$objDb = AppRegistry::get('Database');
			if (!$this->current()->get(self::ID_PROPERTY)) {
				$this->current()->set('created', date($objDb->getDatetimeFormat()));
				$this->current()->set('publickey', md5(rand() . microtime()));
				$this->current()->set('privatekey', $this->getPrivateKey($this->current()->get('userid')));
			}
			$this->current()->set('accessed', date($objDb->getDatetimeFormat()));
		}
		
		
		/**
		 * Flushes the expired login records for the current 
		 * user. This is only run when a new record is inserted.
		 *
		 * @access public
		 */
		public function flushExpired() {
			$arrFunctionArgs = func_get_args();
			if (!empty($arrFunctionArgs[2])) {
				if ($intUserId = $this->current()->get('userid')) {
					if ($intMaxConcurrentLogins = AppConfig::get('MaxConcurrentLogins', false)) {
						$objUserLogin = clone $this;
						if ($objUserLogin->loadByUserId($intUserId) && $objUserLogin->count() > $intMaxConcurrentLogins) {
							$objUserLogin->seek($intMaxConcurrentLogins);
							while (list(, $objRecord) = $objUserLogin->each()) {
								$arrDeleteIds[] = $objRecord->get(self::ID_PROPERTY);
							}
							
							if (!empty($arrDeleteIds)) {
								$objUserLogin->deleteById($arrDeleteIds);
							}
						}
						unset($objUserLogin);
					}
				}
			}
		}
		
		
		/*****************************************/
		/**     LOAD METHODS                    **/
		/*****************************************/
		
		
		/**
		 * A shortcut function to load a record by the user ID
		 * passed. This does not clear out any previously loaded
		 * data. That should be done explicitly.
		 *
		 * @access public
		 * @param mixed $intUserId The user ID to load by
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
				'Column'	=> 'userid',
				'Value' 	=> $intUserId,
				'Operator'	=> '='
			);
			
			if (!array_key_exists('Order', $arrFilters)) {
				$arrFilters['Order'] = array();
			}
			$arrFilters['Order'][] = array(
				'Column'	=> 'accessed',
				'Sort'		=> 'DESC'
			);
			
			$blnResult = $this->load($arrFilters, $blnCalcFoundRows);
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load a record by the user ID
		 * and public key passed. This does not clear out any
		 * previously loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param mixed $intUserId The user ID to load by
		 * @param string $strPublicKey The public key to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserIdAndPublicKey($intUserId, $strPublicKey) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' => 'userid',
						'Value'  => $intUserId
					),
					array(
						'Column' => 'publickey',
						'Value'  => $strPublicKey
					)
				)		
			));
			
			$this->clearLoading();
			return $blnResult;
		}
		
		
		/**
		 * A shortcut function to load a record by the user ID
		 * and private key passed. This does not clear out any
		 * previously loaded data. That should be done explicitly.
		 *
		 * @access public
		 * @param mixed $intUserId The user ID to load by
		 * @param string $strPrivateKey The private key to load by
		 * @return boolean True if the query executed successfully
		 */
		public function loadByUserIdAndPrivateKey($intUserId, $strPrivateKey) {
			$arrFunctionArgs = func_get_args();
			$this->setLoading(__FUNCTION__, $arrFunctionArgs);
			
			$blnResult = $this->load(array(
				'Conditions' => array(
					array(
						'Column' => 'userid',
						'Value'  => $intUserId
					),
					array(
						'Column' => 'privatekey',
						'Value'  => $strPrivateKey
					)
				)		
			));
			
			$this->clearLoading();
			return $blnResult;
		}
	}