<?php
	/**
	 * DataHelper.class.php
	 *
	 * Used to wrap common data calls for easier use. This
	 * should not be overused and should not become a dumping
	 * ground of miscellaneous functions that are only used
	 * in a couple places.
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
	class DataHelper {
	
		/**
		 * Returns the user ID of the user whose username
		 * was passed. Includes an internal static cache to
		 * avoid repeat queries.
		 *
		 * @access public
		 * @param string $strUsername The username to get the ID for
		 * @return integer The user ID
		 * @static
		 */
		static public function getUserIdByUsername($strUsername) {
			static $arrUserCache = array();
			
			if (empty($arrUserCache[$strUsername])) {
				AppLoader::includeModel('UserModel');
				$objUser = new UserModel();
				if ($objUser->loadByUsername($strUsername) && $objUser->count()) {
					$arrUserCache[$strUsername] = $objUser->first()->get('__id');
				}
			}
			
			return !empty($arrUserCache[$strUsername]) ? $arrUserCache[$strUsername] : null;
		}
		
		
		/**
		 * Returns an unabbreviated tag for the abbreviation
		 * passed.
		 *
		 * @access public
		 * @param string $strAbbr The abbreviated tag
		 * @return string The unabbreviated tag
		 */
		static public function getTagByAbbr($strAbbr) {
			AppLoader::includeModel('TagModel');
			$objTag = new TagModel();
			if ($objTag->loadByAbbr($strAbbr) && $objTag->count()) {
				return $objTag->first()->get('tag');
			}
		}
	}