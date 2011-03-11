<?php
	require_once('SiteController.class.php');
	
	/**
	 * FilesController.class.php
	 * 
	 * This controller outputs the private files. If the
	 * file is an image it's displayed, otherwise it's sent
	 * as a downloadable file. This should be altered or 
	 * extended to check for the correct access permissions 
	 * first. Currently only developers can access files.
	 *
	 * The URL format is http://www.example.org/files/private/foo.png
	 * 
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage controllers
	 */
	class FilesController extends SiteController {
	
		protected $blnDeveloper;
		
		
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
			
			if (!$this->blnDeveloper) {
				$this->error(403);
			}
		}
		
		
		/**
		 * Handles the actual ouputting of the file. If it's 
		 * an image then it gets displayed otherwise it gets
		 * downloaded.
		 *
		 * @access public
		 */
		public function run() {
			if (!($objFileSystem = AppRegistry::get('FileSystem', false))) {
				if (AppLoader::includeExtension('files/', $strFileSystem = AppConfig::get('FileSystem') . 'FileSystem')) {
					AppRegistry::register('FileSystem', $objFileSystem = new $strFileSystem());
				}
			}
			
			$strFilePath = str_replace('/files/', '', AppRegistry::get('Url')->getUrl());
			$strFileName = substr($strFilePath, strrpos($strFilePath, '/') + 1);
			$strFileExt = substr($strFileName, strrpos($strFileName, '.') + 1);
			
			if ($strContent = $objFileSystem->readFile($strFilePath)) {
				$objDisplay = AppDisplay::getInstance();
								
				switch ($strFileExt) {
					case 'gif':
						$objDisplay->appendHeader('Content-type: image/gif');
						break;
						
					case 'jpg':
						$objDisplay->appendHeader('Content-type: image/jpeg');
						break;
						
					case 'png':
						$objDisplay->appendHeader('Content-type: image/png');
						break;
						
					default:
						$objDisplay->appendHeader('Content-type: application/force-download');
						$objDisplay->appendHeader('Content-Disposition: attachment; filename="' . $strFileName . '"');
						break;
				}	
				$objDisplay->appendString('content', $strContent);
			} else {
				$this->error();
			}
		}
	}