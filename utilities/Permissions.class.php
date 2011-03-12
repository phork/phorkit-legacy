<?php	
	/**
	 * Permissions.class.php
	 * 
	 * A simple class to calculate permissions using
	 * bitwise operators.
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
	class Permissions {
		
		/**
		 * Determines if a particular permission bit is set.
		 *
		 * @access public
		 * @param integer $intCheckFor The permission to check for
		 * @param integer $intCheckIn The permissions to check in
		 * @return boolean True if set
		 * @static
		 */
		static public function isPermissionSet($intCheckFor, $intCheckIn) {
			return ($intCheckFor & $intCheckIn) != 0;
		}
		
		
		/**
		 * Calculates the permissions based on the array of
		 * permission bits.
		 *
		 * @access public
		 * @param array $arrPermissions The array of permissions
		 * @return integer The permissions integer
		 * @static
		 */
		static public function calcPermissions($arrPermissions) {
			if (!empty($arrPermissions)) {
				$intResult = array_sum($arrPermissions);
			}	
			return isset($intResult) ? $intResult : 0;
		}
		
		
		/**
		 * Calculates the bit from the permission's ID.
		 *
		 * @access public
		 * @param integer $intId The ID
		 * @return integer The calculated bit
		 * @static
		 */
		static public function calcBitFromId($intId) {
			return 1 << ($intId - 1);
		}
	}