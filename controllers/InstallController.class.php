<?php
	require_once('SiteController.class.php');
	
	/**
	 * InstallController.class.php
	 * 
	 * This controller handles all the installation pages.
	 * It runs tests to make sure everything has been installed
	 * and set up correctly.
	 * 
	 * Copyright 2006-2011, Phork Labs. (http://www.phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage controllers
	 */
	class InstallController extends SiteController {
		
		protected $blnDeveloper;
		protected $arrTests = array();
		protected $arrTips = array();
		
		
		/**
		 * Sets up the common page variables to be used
		 * across all node templates, including the styles
		 * and javascript.
		 * 
		 * @access public
		 */
		public function __construct() {
			parent::__construct();
			
			if ($this->blnLoggedIn) {
				if ($intRoles = AppRegistry::get('UserLogin')->getUserRecord()->get('roles')) {
					AppLoader::includeUtility('Permissions');
					$this->blnDeveloper = Permissions::isPermissionSet(Permissions::calcBitFromId(AppConfig::get('DeveloperRole')), $intRoles);
				}
			}
			
			if (!AppConfig::get('Installing', false) && !$this->blnDeveloper) {
				$this->error(403);
			}
			
			$this->assignPageVar('strBodyClass', 'install');
			$this->assignPageVar('arrStylesheets', array(
				AppConfig::get('CssUrl') . $this->strThemeCssDir . 'install.css'
			));
		}
		
		
		/**
		 * Runs a single test and stores the result and any errors.
		 * Errors are not added to the global error list.
		 *
		 * @access protected
		 * @param string $strTest The name of the test to run
		 * @return boolean True if the test passed
		 */
		protected function runTest($strTest) {
			if (empty($this->arrTests[$strTest])) {
				$objError = AppRegistry::get('Error');
				$objError->startGroup($strTest, false);
				
				$this->arrTests[$strTest] = $this->{'test' . ucfirst($strTest)}();
				
				$objError->endGroup($strTest);
				if ($arrErrors = $objError->getGroupErrors($strTest)) {
					$objError->clearGroupErrors($strTest);
				}
				
				$this->arrTests[$strTest]['passed'] = empty($arrErrors);
				$this->arrTests[$strTest]['errors'] = $arrErrors;
			}
			
			return $this->arrTests[$strTest]['passed'];
		}
		
		
		/*****************************************/
		/**     TEST METHODS                    **/
		/*****************************************/
		
		
		/**
		 * Tests whether Amazon S3 support has been set up.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 */
		protected function testAmazonS3() {
			if ($blnEnabled = AppConfig::get('FileSystem') == 'AmazonS3') {
				if ($this->runTest('zend')) {
					try {
						$arrConfig = AppConfig::load('amazon');
						
						if (empty($arrConfig['S3FolderBase']) || $arrConfig['S3FolderBase'] == 'YOUR_BUCKET_NAME/') {
							$blnFailed = true;
							trigger_error(AppLanguage::translate('Missing or invalid S3FolderBase'));
						}
						
						if (empty($arrConfig['S3FolderRoot']) || $arrConfig['S3FolderRoot'] == 's3://YOUR_BUCKET_NAME/') {
							$blnFailed = true;
							trigger_error(AppLanguage::translate('Missing or invalid S3FolderRoot'));
						}
						
						if (empty($arrConfig['S3FilesUrl']) || $arrConfig['S3FilesUrl'] == 'http://YOUR_BUCKET_URL.amazonaws.com/' . AppConfig::get('PublicFilePath')) {
							$blnFailed = true;
							trigger_error(AppLanguage::translate('Missing or invalid S3FilesUrl'));
						}
						
						if (empty($arrConfig['S3AccessKey']) || $arrConfig['S3AccessKey'] == 'YOUR_ACCESS_KEY') {
							$blnFailed = true;
							trigger_error(AppLanguage::translate('Missing or invalid S3AccessKey'));
						}
						
						if (empty($arrConfig['S3SecretKey']) || $arrConfig['S3SecretKey'] == 'YOUR_SECRET_KEY') {
							$blnFailed = true;
							trigger_error(AppLanguage::translate('Missing or invalid S3SecretKey'));
						}
						
						if (empty($blnFailed)) {
							try {
								AppLoader::includeExtension('files/', 'AmazonS3FileSystem');
								$objFileSystem = new AmazonS3FileSystem();
								if ($objFileSystem->createFile($strFilePath = '__file_test', 1)) {
									if ($objFileSystem->readFile($strFilePath) == 1) {
										$objFileSystem->deleteFile($strFilePath);
									} else {
										trigger_error(AppLanguage::translate('Unable to create test file'));
									}
								} else {
									trigger_error(AppLanguage::translate('Unable to create test file'));
								}
							} catch (Exception $objException) {
								trigger_error($objException->getMessage());
							}
						}
					} catch (Exception $objException) {
						trigger_error(AppLanguage::translate('Missing Amazon configuration file (amazon.config.php)'));
					}
				} else {
					trigger_error(AppLanguage::translate('AmazonS3 support requires the Zend framework'));
				}
			} else {
				trigger_error(AppLanguage::translate('To enable Amazon S3 file storage go into global.config.php and set FileSystem to "AmazonS3" and then edit amazon.config.php'));
			}
			
			return array(
				'name'		=> 'Amazon S3',
				'enabled'	=> $blnEnabled
			);
		}
		
		
		/**
		 * Tests whether the database has been configured and is
		 * able to connect.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 */
		protected function testCache() {
			if ($blnEnabled = AppConfig::get('CacheEnabled')) {
				if ($objCache = AppRegistry::get('Cache', false)) {
					if (call_user_func(array(get_class($objCache), 'isAvailable'))) {
						try {
							if ($objCache->initBase()) {
								if ($objCache->save($strCacheKey = '__base_test', 1, 20)) {
									if ($objCache->load($strCacheKey) != 1) {
										trigger_error(AppLanguage::translate('Mismatched cache load response for %s', 'Base'));
									}
								}
							} else {
								trigger_error(AppLanguage::translate('Unable to connect to the %s cache', 'Base'));	
							}
							
							if ($objCache->initPresentation()) {
								if ($objCache->save($strCacheKey = '__presentation_test', 1, 20)) {
									if ($objCache->load($strCacheKey) != 1) {
										trigger_error(AppLanguage::translate('Mismatched cache load response for %s', 'Presentation'));
									}
								}
							} else {
								trigger_error(AppLanguage::translate('Unable to connect to the %s cache', 'Presentation'));	
							}
						} catch (Exception $objException) {
							trigger_error($objException->getMessage());
						}
					} else {
						trigger_error(AppLanguage::translate('Missing the cache dependency classes (see %s::isAvailable())', get_class($objCache)));
					}
				} else {
					trigger_error(AppLanguage::translate('Unable to load the cache from the registry'));
				}
			} else {
				trigger_error(AppLanguage::translate('To enable the cache go into global.config.php and set CacheEnabled to true and then edit cache.config.php'));
			}
			
			return array(
				'name'		=> 'Cache',
				'enabled'	=> $blnEnabled
			);
		}
		
		
		/**
		 * Tests whether the database has been configured and is
		 * able to connect. The database object itself triggers
		 * connection errors so they're not redefined here.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 */
		protected function testDatabase() {
			if ($blnEnabled = AppConfig::get('DatabaseEnabled')) {
				if ($objDb = AppRegistry::get('Database', false)) {
					if ($objDb->initRead() && $objDb->initWrite()) {
						if (($mxdResult = $objDb->read('SHOW TABLES')) !== false) {
							$arrTables = array(
								'cache',
								'countries',
								'facebook',
								'promo',
								'roles',
								'sessions',
								'tags',
								'twitter',
								'user_connections',
								'user_events',
								'user_login',
								'user_logs',
								'user_passwords',
								'user_tags',
								'users',
								'verify'
							);
							
							while ($arrTable = $objDb->fetchRow($mxdResult)) {
								if (($intKey = array_search($arrTable[0], $arrTables)) !== false) {
									unset($arrTables[$intKey]);
								}
							}
							$objDb->freeResult($mxdResult);
							
							if (!empty($arrTables)) {
								trigger_error(AppLanguage::translate('The following database tables are missing: %s', implode(', ', $arrTables)));
								$this->arrTips['database']['tables'] = AppLanguage::translate('The SQL create and insert queries for all the required tables can be found in sites/phorkit/sql/initial.sql');
							}
						} else {
							trigger_error(AppLanguage::translate('Unable to load the list of database tables'));
						}
					}
				} else {
					trigger_error(AppLanguage::translate('Unable to load the database from the registry'));
				}
			} else {
				trigger_error(AppLanguage::translate('To enable the database go into global.config.php and set DatabaseEnabled to true and then edit database.config.php'));
			}
						
			return array(
				'name'		=> 'Database',
				'enabled'	=> $blnEnabled
			);
		}
		
		
		/**
		 * Tests whether Facebook connect works.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 * @todo Need a way to validate API key and secret
		 */
		protected function testFacebook() {
			if ($blnEnabled = AppConfig::get('FacebookConnect', false)) {
				try {
					AppLoader::includeExtension('facebook/', 'facebook', true);
				} catch (Exception $objException) {
					$blnFailed = true;
					trigger_error(AppLanguage::translate('Missing Facebook connection library (ext/facebook/facebook.php)'));
					$this->arrTips['facebook']['library'] = AppLanguage::translate('The Facebook SDK can be downloaded from <a href="https://github.com/facebook/php-sdk/" rel="external">Github</a>.');
				}
				
				try {
					$arrConfig = AppConfig::load('facebook');
					
					if (empty($arrConfig['FacebookApiKey']) || $arrConfig['FacebookApiKey'] == 'YOUR_API_KEY') {
						$blnFailed = true;
						trigger_error(AppLanguage::translate('Missing or invalid FacebookApiKey'));
					}
					
					if (empty($arrConfig['FacebookAppSecret']) || $arrConfig['FacebookAppSecret'] == 'YOUR_APP_SECRET') {
						$blnFailed = true;
						trigger_error(AppLanguage::translate('Missing or invalid FacebookAppSecret'));
					}
				} catch (Exception $objException) {
					trigger_error(AppLanguage::translate('Missing Facebook configuration file (facebook.config.php)'));
				}
				
				if (empty($blnFailed)) {
					AppLoader::includeUtility('FacebookConnect');
					if ($objFacebookConnect = FacebookConnect::getConnectObject()) {
						//need a way to validate API key and secret here
					} else {
						trigger_error(AppLanguage::translate('There was an error connecting to Facebook'));
					}
				}
			} else {
				trigger_error(AppLanguage::translate('To enable Facebook connect go into site.config.php and set FacebookConnect to true and then edit facebook.config.php'));
			}
			
			return array(
				'name'		=> 'Facebook connect',
				'enabled'	=> $blnEnabled
			);
		}
		
		
		/**
		 * Tests whether the files folders are set up and have
		 * the right permissions.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 */
		protected function testFiles() {
			if ($strFilesDir = AppConfig::get('FilesDir', false)) {
				if (is_dir($strFilesDir)) {
					$arrSubDirs = array(
						'app/cache',
						'app/cache/base',
						'app/cache/presentation',
						'app/lang',
						'app/logs',
						'public',
						'public/avatar'
					);
					
					foreach ($arrSubDirs as $strSubDir) {
						if (is_dir($strFilesDir . $strSubDir)) {
							if (!is_writable($strFilesDir . $strSubDir)) {
								$arrInvalid[] = $strFilesDir . $strSubDir;
							}
						} else {
							$arrMissing[] = $strFilesDir . $strSubDir;
						}
					}
					
					if (!empty($arrMissing)) {
						trigger_error(AppLanguage::translate('The following directories are missing: %s', implode(', ', $arrMissing)));
					}
					
					if (!empty($arrInvalid)) {
						trigger_error(AppLanguage::translate('The following directories must be web-writable: %s', implode(', ', $arrInvalid)));
						$this->arrTips['files']['writable'] = AppLanguage::translate('To set up a directory as web-writable it should be owned by the same user that runs your webserver (eg. chown apache:apache /path/to/dir) or, if you prefer, it can be set to world-writable (eg. chmod 777 /path/to/dir)');
					}
				} else {
					trigger_error(AppLanguage::translate('%s is not a valid directory', $strFilesDir));
				}
			} else {
				trigger_error(AppLanguage::translate('Missing FilesDir configuration'));
			}
			
			return array(
				'name'		=> 'File permissions',
				'enabled'	=> true
			);
		}
		
		
		/**
		 * Tests whether the application has been installed.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 */
		protected function testInstalled() {
			if (!($blnInstalled = !AppConfig::get('Installing', false))) {
				trigger_error(AppLanguage::translate('To install %s go into site.config.php and follow the instructions', AppConfig::get('SiteTitle')));
			}
			
			return array(
				'name'		=> 'Installed',
				'enabled'	=> true
			);
		}
		
		
		/**
		 * Tests whether the application has been installed.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 */
		protected function testPostmark() {
			if ($blnEnabled = AppConfig::get('PostmarkEnabled', false)) {
				try {
					AppLoader::includeExtension('postmark/', 'Postmark', true);
				} catch (Exception $objException) {
					trigger_error(AppLanguage::translate('Missing Postmark library (ext/postmark/Postmark.php)'));
					$this->arrTips['postmark']['library'] = AppLanguage::translate('The Postmark library can be downloaded from <a href="https://github.com/Znarkus/postmark-php" rel="external">Github</a>.');
				}
			} else {
				trigger_error(AppLanguage::translate('To enable Postmark for sending emails go into site.config.php and set PostmarkEnabled to true and then edit postmark.config.php'));
			}
			
			return array(
				'name'		=> 'Postmark',
				'enabled'	=> $blnEnabled
			);
		}
		
		
		/**
		 * Tests whether Twitter connect works. Runs the zend
		 * prerequisite.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 */
		protected function testTwitter() {
			if ($blnEnabled = AppConfig::get('TwitterConnect', false)) {
				if ($this->runTest('zend')) {
					try {
						$arrConfig = AppConfig::load('twitter');
						
						if (empty($arrConfig['TwitterConsumerKey']) || $arrConfig['TwitterConsumerKey'] == 'YOUR_CONSUMER_KEY') {
							$blnFailed = true;
							trigger_error(AppLanguage::translate('Missing or invalid TwitterConsumerKey'));
						}
						
						if (empty($arrConfig['TwitterConsumerSecret']) || $arrConfig['TwitterConsumerSecret'] == 'YOUR_CONSUMER_SECRET') {
							$blnFailed = true;
							trigger_error(AppLanguage::translate('Missing or invalid TwitterConsumerSecret'));
						}
						
						if (empty($blnFailed)) {
							AppLoader::includeUtility('TwitterConnect');
							if ($objTwitterConnect = TwitterConnect::getConnectObject()) {
								try {
									$objTwitterConnect->getRequestToken();
								} catch (Exception $objException) {
									trigger_error(AppLanguage::translate('Unable to connect to Twitter'));
								}
							} else {
								trigger_error(AppLanguage::translate('There was an error connecting to Twitter'));
							}
						}
					} catch (Exception $objException) {
						trigger_error(AppLanguage::translate('Missing Twitter configuration file (twitter.config.php)'));
					}
				} else {
					trigger_error(AppLanguage::translate('Twitter requires the Zend framework'));
				}
			} else {
				trigger_error(AppLanguage::translate('To enable Twitter connect go into site.config.php and set TwitterConnect to true and then edit twitter.config.php'));
			}
			
			return array(
				'name'		=> 'Twitter connect',
				'enabled'	=> $blnEnabled
			);
		}
		
		
		/**
		 * Tests whether the Zend framework has been installed.
		 *
		 * @access protected
		 * @return array The name of the test and whether it's been enabled
		 */
		protected function testZend() {
			try {
				$arrConfig = AppConfig::load('zend');
				
				try {
					AppLoader::includeExtension('zend/', 'ZendLoader');
					
					if (file_exists($strLoaderPath = $arrConfig['ZendBase'] . '/Zend/Loader.php')) {
						$blnEnabled = true;
					} else {
						trigger_error(AppLanguage::translate('Missing Zend framework (' . $strLoaderPath . ')'));
						$this->arrTips['zend']['library'] = AppLanguage::translate('The Zend framework can be downloaded from <a href="http://framework.zend.com/download/current/" rel="external">Zend</a>. Only the minimal package is required.');
					}
				} catch (Exception $objException) {
					trigger_error(AppLanguage::translate('Missing ZendLoader library (ext/zend/ZendLoader.class.php)'));
				}	
			} catch (Exception $objException) {
				trigger_error(AppLanguage::translate('Missing Zend configuration file (zend.config.php)'));
			}
			
			return array(
				'name'		=> 'Zend framework',
				'enabled'	=> !empty($blnEnabled)
			);
		}
		
		
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Displays the navigation template. The install navigation
		 * is customized so that uninstalled applications don't get
		 * a nav.
		 *
		 * @access protected
		 */
		protected function displayNav() {
			if (AppConfig::get('Installing', false)) {
				$this->displayNode('nav', $this->getTemplatePath('install/common/nav'));
			} else {
				$this->displayNode('nav', $this->getTemplatePath('common/nav'));
			}
		}
		
		
		/**
		 * Displays the main install page. This runs a series of
		 * tests to make sure the right configuration and permissions
		 * have been set up.
		 *
		 * @access protected
		 */
		protected function displayIndex() {
			$objError = AppRegistry::get('Error');
			if ($blnDebugMode = $objError->getDebugMode()) {
				$objError->setDebugMode(false);
			}
			
			foreach (array('installed', 'files', 'database', 'cache', 'facebook', 'twitter', 'zend', 'amazonS3', 'postmark') as $strTest) {
				if (empty($this->arrTests[$strTest])) {
					$this->runTest($strTest);
				}
			}
			
			if ($blnDebugMode) {
				$objError->setDebugMode(true);
			}
			
			$this->displayNode('content', $this->getTemplatePath('install/index'), array(
				'strConfigPath'	=> AppConfig::get('ConfigDir'),
				'arrTests'		=> $this->arrTests,
				'arrTips'		=> $this->arrTips
			));
		}
	}