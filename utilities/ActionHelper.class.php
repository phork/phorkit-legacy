<?php
	/**
	 * ActionHelper.class.php
	 *
	 * Generates and verifies action URLs by creating and
	 * checking an encoded token to make sure it matches.
	 * This works in conjunction with the ActionController
	 * to allow safe GET, POST, PUT and DELETE API requests.
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
	class ActionHelper {
		
		const QUERY_ARG = '_k';
		const DIVIDER = '-';
		
		
		/**
		 * Generates an action URL including a secret
		 * key used to protect spoofed data. This doesn't
		 * work with query strings.
		 *
		 * @access public
		 * @param string $strUrl The URL to turn into an action URL
		 * @return string The action URL
		 * @static
		 */
		static public function generateUrl($strUrl) {
			$arrSegments = explode('/', ($intQueryPos = strpos($strUrl, '?')) !== false ? substr($strUrl, 0, $intQueryPos) : $strUrl);
			foreach ($arrSegments as $intKey=>$strSegment) {
				if (!$strSegment) {
					unset($arrSegments[$intKey]);
				}
			}
			return $strUrl . ($intQueryPos !== false ? '&' : '?') . self::QUERY_ARG . '=' . self::encodeUrl($arrSegments, time());
		}
		
		
		/**
		 * Verifies the current action URL and that the
		 * secret key is valid.
		 *
		 * @access public
		 * @return boolean True if verified
		 * @static
		 */
		static public function verifyUrl() {
			if (!empty($_GET[self::QUERY_ARG])) {
				list($intTimestamp, $strSecretKey) = explode(self::DIVIDER, $_GET[self::QUERY_ARG]);
				if (time() - $intTimestamp < AppConfig::get('ActionKeyTTL')) {
					return self::encodeUrl(explode('/', AppConfig::get('BaseUrl') . AppRegistry::get('Url')->getUrl()), $intTimestamp) == $_GET[self::QUERY_ARG];
				}
			}
		}
		
		
		/**
		 * Encodes the URL segments to generate the
		 * secret key.
		 *
		 * @access protected
		 * @param array $arrSegments The URL segments to encode
		 * @param integer $intTimestamp The UNIX timestamp to add to the key
		 * @return string The secret key
		 * @static
		 */
		static protected function encodeUrl($arrSegments, $intTimestamp) {
			return $intTimestamp . self::DIVIDER . md5($intTimestamp . implode('', $arrSegments) . AppConfig::get('ActionKey') . AppRegistry::get('UserLogin')->getUserId());
		}
	}