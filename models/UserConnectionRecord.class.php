<?php
	require_once('php/core/CoreRecord.class.php');
	
	/**
	 * UserConnectionRecord.class.php
	 * 
	 * Stores a user connection record from a data source.
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
	class UserConnectionRecord extends CoreRecord {
	
		/**
		 * Sets the value of the property to the value passed.
		 * This has additional functionality to put the connection
		 * data in a separate array.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to set
		 * @param mixed $mxdValue The value to set the property to
		 * @return mixed The value the property was set to
		 */
		public function set($strProperty, $mxdValue) {
			if (substr($strProperty, 0, 11) == 'connection_') {
				if (!property_exists($this, 'connection')) {
					$this->connection = array();
				}
				$this->connection[substr($strProperty, 11)] = $mxdValue;
			} else {
				$this->$strProperty = $mxdValue;
			}
			return $mxdValue;
		}
	}