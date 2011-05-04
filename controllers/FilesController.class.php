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
	 * The URL format is http://example.com/files/private/foo.png
	 * or http://example.com/files/filesystem=AmazonS3/private/foo.png
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
		 * Verifies that the user has permission to access the
		 * file. Currently only developers can access files.
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
			$objUrl = AppRegistry::get('Url');
			if (!($strFileSystemType = $objUrl->getFilter('filesystem'))) {
				$strFileSystemType = 'Local';
			}
			$strFileSystem = $strFileSystemType . 'FileSystem';
			
			if (!($objFileSystem = AppRegistry::get('FileSystem', false)) || !($objFileSystem instanceof $strFileSystem)) {
				AppLoader::includeExtension('files/', $strFileSystem);
				$objFileSystem = new $strFileSystem();
			}
						
			$strFilePath = str_replace('/files/', '', $objUrl->getUrl());
			$strFilePath = preg_replace('|/?filesystem=[^/]*/|', '', $strFilePath);
			
			$strFileName = substr($strFilePath, strrpos($strFilePath, '/') + 1);
			$strFileExt = substr($strFileName, strrpos($strFileName, '.') + 1);
		
			if (($intFileSize = $objFileSystem->getFileSize($strFilePath)) !== false) {
				$objDisplay = AppDisplay::getInstance();
				$objDisplay->appendHeader('Content-length: ' . $intFileSize);
							
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
						$objDisplay->appendHeader('Content-disposition: attachment; filename="' . $strFileName . '"');
						break;
				}
				$objFileSystem->outputFile($strFilePath);
			} else {
				$this->error();
			}
		}
	}