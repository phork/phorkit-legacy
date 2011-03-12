<?php
	require_once('php/core/CoreRecord.class.php');
	
	/**
	 * RoleRecord.class.php
	 * 
	 * Stores a role record from a data source with
	 * additional functionality to automatically set
	 * the bit from the ID.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage models
	 */
	class RoleRecord extends CoreRecord {
	
		/**
		 * Sets the value of the property to the value passed.
		 * This has been extended to set the bit from the ID.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to set
		 * @param mixed $mxdValue The value to set the property to
		 * @return mixed The value the property was set to
		 */
		public function set($strProperty, $mxdValue) {
			$this->$strProperty = $mxdValue;
			if ($strProperty == 'roleid') {
				$this->set('bitmask', Permissions::calcBitFromId($mxdValue));
			}
			return $this->$strProperty;
		}
	}