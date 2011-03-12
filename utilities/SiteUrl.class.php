<?php
	/**
	 * SiteUrl.class.php
	 *
	 * A utility class to build the page URLs.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage utilities
	 */
	class SiteUrl {
		
		/**
		 * Builds a URL off the current base URL with
		 * the segments and filters passed.
		 *
		 * @access public
		 * @param array $arrUrlSegments The URL segments to build
		 * @param array $arrUrlFilters The URL filters to build
		 * @param boolean $blnCleanUrl Whether to clean the URL data if applicable
		 * @return string The built URL
		 * @static
		 */
		static public function buildUrl($arrUrlSegments, $arrUrlFilters = null, $blnCleanUrl = true) {
			if ($blnCleanUrl) {
				$arrUrlSegments = array_map(htmlentities($arrUrlSegments));
			}
		
			$strUrl = AppRegistry::get('Url')->getBaseUrl() . '/' . implode('/', $arrUrlSegments) . '/';
			if (!empty($arrUrlFilters)) {
				foreach ($arrUrlFilters as $strFilter=>$mxdValue) {
					self::appendUrlFilter($strUrl, $strFilter, $mxdValue, $blnCleanUrl);
				}
			}
			return $strUrl;
		}
		
		
		/**
		 * Appends a filter to the URL.
		 *
		 * @access public
		 * @param string $strUrl The URL to append the filter to
		 * @param string $strFilter The name of the filter to append
		 * @param mixed $mxdValue The value of the filter
		 * @param boolean $blnCleanUrl Whether to clean the URL data if applicable
		 * @static
		 */
		static public function appendUrlFilter(&$strUrl, $strFilter, $mxdValue, $blnCleanUrl = true) {
			if ($blnCleanUrl) {
				$strFilter = htmlentities($strFilter);
				$mxdValue = htmlentities($mxdValue);
			}
			$strUrl = "{$strUrl}{$strFilter}={$mxdValue}/";
		}
		
		
		/**
		 * Appends a query string to the URL and accounts for any
		 * existing query string.
		 *
		 * @acces public
		 * @param string $strUrl The URL to append the query string to
		 * @param array $arrQueryString The query string pieces to build
		 * @param boolean $blnCleanUrl Whether to clean the URL data if applicable
		 * @static
		 */
		static public function appendQueryString(&$strUrl, $arrQueryString, $blnCleanUrl = true) {
			if (count($arrQueryString)) {
				$strAmp = $blnCleanUrl ? '&amp;' : '&';
				
				$strUrl .= (strpos($strUrl, '?') !== false ? $strAmp : '?');
				$strUrl .= http_build_query($arrQueryString, null, $strAmp);
			}
		}
		
		
		/**
		 * Returns the URL of the current page with 
		 * a %s for the page number.
		 * 
		 * @access public
		 * @param boolean $blnCleanUrl Whether to clean the URL data with htmlentities
		 * @return string The paginated URL
		 * @static
		 */
		static public function getPaginateUrlTemplate($blnCleanUrl = true) {
			$strUrl = AppRegistry::get('Url')->getCurrentUrl();
			
			if (preg_match('/page=[0-9]+/', $strUrl)) {
				$strUrl = preg_replace('/page=[0-9]+/', "page=%s", $strUrl);
			} else {
				if (strpos($strUrl, '?')) {
					list($strUrl, $strQueryString) = explode('?', $strUrl);
					$strUrl .= "page=%s/?" . $strQueryString;
				} else {
					$strUrl .= "page=%s/";
				}
			}			
			return $strUrl;
		}
		
		
		/**
		 * Returns the URL of the current page with 
		 * the new page number.
		 * 
		 * @access public
		 * @param integer $intPage The list page number 
		 * @param boolean $blnCleanUrl Whether to clean the URL data with htmlentities
		 * @return string The paginated URL
		 * @static
		 */
		static public function getPaginateUrl($intPage = 1, $blnCleanUrl = true) {
			return sprintf(self::getPaginateUrlTemplate($blnCleanUrl), $intPage);
		}
	}