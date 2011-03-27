<?php
	require_once('php/core/CoreBootstrap.class.php');
	require_once('php/core/CoreAlert.class.php');
	
	/**
	 * SiteBootstrap.class.php
	 * 
	 * The bootstrap sets up the site-wide libs and
	 * configs and delegates processing to a controller.
	 *
	 * This should also require the default controller
	 * at the top of the file. 
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage bootstraps
	 */
	class SiteBootstrap extends CoreBootstrap {
	
		protected $strDefaultController = 'SiteController';
		
		
		/**
		 * Sets up the configuration, loads all the libraries,
		 * sets up the error handler and the debugger, parses
		 * the URL and starts the session. This also sets up
		 * the script timer using the global timer object and
		 * registers the pre-output event.
		 *
		 * @access public
		 * @param string $arrConfig The configuration array
		 */
		public function __construct($arrConfig) {
			parent::__construct($arrConfig);
			
			AppLoader::includeUtility('Conversion');
			AppRegistry::register('Timer', $GLOBALS['objTimer']);
			AppEvent::register('display.pre-output', array($this, 'preOutput'));
		}
		
		
		/**
		 * Initializes the debugging dispatcher and adds
		 * the handler objects to it. The default handler
		 * logs the debugging data to a file.
		 *
		 * @access protected
		 */
		protected function initDebugging() {
			parent::initDebugging();
			
			if (AppConfig::get('DebugDisplay')) {
				AppLoader::includeExtension('debug/', 'DebugSession');
			
				$objDebug = CoreDebug::getInstance();
				$objDebug->addHandler('session', new DebugSession());
			}
		}
		
		
		/**
		 * Loads and parses the current URL. Also sets up the
		 * overlay, insert (for dynamically inserting a div in 
		 * the page), API and callback flags.
		 *
		 * @access protected
		 */
		protected function parseUrl() {
			parent::parseUrl();
			$objUrl = AppUrl::getInstance();
			
			AppConfig::set('Overlay', !empty($_GET['overlay']));
			AppConfig::set('Insert', !empty($_GET['insert']));
			
			AppConfig::set('ApiCall', $blnApiCall = $objUrl->getSegment(0) == 'api');
			AppConfig::set('Callback', $blnCallback = $objUrl->getSegment(0) == 'callback'); 
			
			AppConfig::set('TokenIgnore', $blnCallback || $blnApiCall);
		}
		
		
		/**
		 * Starts the session if sessions are enabled. Sets up
		 * an optional custom session handler.
		 *
		 * @access protected
		 */
		protected function startSession() {
			if (AppConfig::get('SessionsEnabled', false)) {
				if ($strSessionHandler = AppConfig::get('SessionHandler', false)) {
					AppLoader::includeClass('php/core/', 'CoreSession');
					CoreSession::setHandler(AppLoader::newObject('php/ext/sessions/' . strtolower($strSessionHandler) . '/', 'Session' . $strSessionHandler));
				}
			}
			parent::startSession();
		}
		
		
		/**
		 * Sets up the hooks to run during execution. Currently
		 * setting up the hook to verify the form post, track
		 * URL history, and serve and save the page cache.
		 *
		 * @access public
		 */
		public function initHooks() {
			if (AppLoader::includeHooks('CommonHooks')) {
				$objCommonHooks = new CommonHooks();
				$this->registerPreRunHook(array($objCommonHooks, 'verifyToken'));
				
				if (!AppConfig::get('Overlay') && !AppConfig::get('Insert')) {
					$this->registerPostRunHook(array($objCommonHooks, 'trackHistory'), array(5, array('css', 'js', 'xml', 'json', 'jsonp', 'html')));
				}
			}
			
			if (AppLoader::includeHooks('CacheHooks')) {
				$objCacheHooks = new CacheHooks();
				$this->registerPreRunHook(array($objCacheHooks, 'serveCache'));
				$this->registerPostRunHook(array($objCacheHooks, 'saveCache'));
			}
			
			if (AppLoader::includeHooks('AccessHooks')) {
				$objAccessHooks = new AccessHooks();
				$this->registerPreRunHook(array($objAccessHooks, 'handleAccess'), array(AppConfig::get('BaseUrl') . '/account/login/'));
			}
		}
		
		
		/**
		 * Determines which controller to use based on the
		 * parsed URL. Has special handling to allow a forced
		 * beta site for unlogged in users.
		 *
		 * @access public
		 * @return string The controller to use
		 */
		public function determineController() {
			if (AppConfig::get('PrivateBeta', false) && !in_array(AppRegistry::get('Url')->getSegment(0), array('api', 'concat'))) {
				if ($objUserLogin = AppRegistry::get('UserLogin', false)) {
					if ($blnLoggedIn = $objUserLogin->isLoggedIn()) {
						return parent::determineController();
					}
				}
				return 'BetaController';
			}
			return parent::determineController();
		}
		
		
		/**
		 * Replaces the load time and the peak memory
		 * usage in the output. This is registered as
		 * an event in the constructor and called from
		 * the display object.
		 *
		 * Don't use AppLoader::includeUtility in this 
		 * method unless the display output class is
		 * manually called and doesn't rely on the
		 * desctructor.
		 *
		 * @access public
		 */
		public function preOutput() {
			if (AppConfig::get('DebugEnabled')) {
				$objDisplay = AppDisplay::getInstance();
				$objDisplay->replace('<[LOAD TIME]>', AppRegistry::get('Timer')->getTime());
				$objDisplay->replace('<[PEAK MEMORY]>', Conversion::convertBytes(memory_get_peak_usage()));
			}
		}
	}