<?php
	require_once('php/core/CoreLoader.class.php');
	
	/**
	 * AppLoader.class.php
	 * 
	 * The loader class is used to include files. It
	 * has special methods to include controller, api,
	 * model, hook, extension, and utility classes.
	 *
	 * This is a singleton class and therefore it must
	 * be instantiated using the getInstance() method.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://www.phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage app
	 */
	class AppLoader extends CoreLoader {
	
		/**
		 * Includes a hook class. This first checks the global
		 * directory, then the site-specific directory and then
		 * the admin directory.
		 *
		 * @access public
		 * @param string $strClass The hook class name
		 * @return boolean True on success
		 * @static
		 */
		static public function includeHooks($strClass) {
			return self::getInstance()->includeClass(AppConfig::get('InstallDir') . 'php/hooks/', $strClass, false) ||
			       self::getInstance()->includeClass(AppConfig::get('SiteDir') . 'hooks/', $strClass);
		}
		
		
		/**
		 * Includes an extension class. This first checks the global
		 * directory, then the site-specific directory.
		 *
		 * @access public
		 * @param string $strExtension The extension sub-directory
		 * @param string $strClass The extension class name
		 * @param boolean $blnFile Whether to use the file include method
		 * @return boolean True on success
		 * @static
		 */
		static public function includeExtension($strExtensionDir, $strClass, $blnFile = false) {
			$strMethod = $blnFile ? 'includeFile' : 'includeClass';
			return self::getInstance()->$strMethod(AppConfig::get('InstallDir') . 'php/ext/' . $strExtensionDir, $strClass, false) ||
			       self::getInstance()->$strMethod(AppConfig::get('SiteDir') . 'ext/' . $strExtensionDir, $strClass);
		}
		
		
		/**
		 * Includes a utility class. Has additional handling
		 * to check the site-specific and admin utilities as
		 * well.
		 *
		 * @access public
		 * @param string $strClass The utility class name
		 * @return boolean True on success
		 * @static
		 */
		static public function includeUtility($strClass) {
			return self::getInstance()->includeClass(AppConfig::get('SiteDir') . 'utilities/', $strClass, false) ||
			       self::getInstance()->includeClass(AppConfig::get('InstallDir') . 'php/utilities/', $strClass);
		}
	}