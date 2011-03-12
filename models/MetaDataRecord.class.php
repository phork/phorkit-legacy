<?php
	require_once('php/core/CoreRecord.class.php');
	
	/**
	 * MetaDataRecord.class.php
	 * 
	 * Stores a item record from a data source with
	 * additional functionality to store the metadata
	 * as serialized and encoded.
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
	class MetaDataRecord extends CoreRecord {
	
		/**
		 * Sets the value of the property to the value passed.
		 * This has been extended to store the metadata in
		 * encoded serialized data.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to set
		 * @param mixed $mxdValue The value to set the property to
		 * @return mixed The value the property was set to
		 */
		public function set($strProperty, $mxdValue) {
			$this->$strProperty = $mxdValue;
			switch ($strProperty) {
				case 'raw':
					$this->metadata = $mxdValue ? base64_encode(serialize($mxdValue)) : null;
					break;
			
				case 'metadata':
					$this->raw = $mxdValue ? unserialize(base64_decode($mxdValue)) : null;
					break;
			}
			return $this->$strProperty;
		}
	}