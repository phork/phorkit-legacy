<?php
	require_once('ScriptController.class.php');
	
	/**
	 * LogScriptController.class.php
	 * 
	 * This controller handles parsing the various logs
	 * from the public site.
	 *
	 * php -d memory_limit=64M /path/to/phork/sites/public/scripts/index.php dev LogScript commonQueries
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
	class LogScriptController extends ScriptController {
	
		/**
		 * Parses the debug logs for SQL queries to get the
		 * most common queries.
		 *
		 * @access protected
		 */
		protected function runCommonQueries() {
			$arrQueries = $arrGrouped = array();
			
			AppLoader::includeExtension('files/', $strFileSystem = 'LocalFileSystem');
			$objFileSystem = new $strFileSystem();
			if ($arrFiles = $objFileSystem->listFiles($strFileDir = dirname(AppConfig::get('DebugFile')))) {
				foreach ($arrFiles as $strFile) {
					if (preg_match('/debug(\.[0-9]{8}){0,1}\.log/', $strFile)) {
						if ($strData = $objFileSystem->readFile("{$strFileDir}/{$strFile}")) {
							if (preg_match_all('/Db: MySQL: Query \(Read @ [^)]+\): (.*)/', $strData, $arrMatches)) {
								foreach ($arrMatches[1] as $strQuery) {
									$strQuery = preg_replace('/\s+/', ' ', $strQuery);
									$strGroup = preg_replace("/'[^']*'/", "''", $strQuery);
									$strGroup = preg_replace('/IN ([^)]+)/', 'IN ()', $strGroup);
									
									if (empty($arrGrouped[$strGroup])) {
										$arrGrouped[$strGroup] = 0;
									}
									
									$arrQueries[$strGroup][] = $strQuery;
									$arrGrouped[$strGroup]++;
								}
							}
						}
					}
				}
			}
			
			asort($arrGrouped, SORT_NUMERIC);
			$arrGrouped = array_reverse($arrGrouped);
			
			foreach ($arrGrouped as $strQuery=>$intCount) {
				printf("Count: %d\n\n", $intCount);
				printf("%s;", $arrQueries[$strQuery][0]);
				print "\n" . str_repeat('-', 100) . "\n";
			}
		}
	}