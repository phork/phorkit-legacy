<?php
	require_once('php/core/CoreRecord.class.php');
	
	/**
	 * UserRecord.class.php
	 * 
	 * Stores a user record from a data source with
	 * additional functionality to automatically encrypt
	 * the plain text passwords, build the birthdate
	 * and calculate the roles.
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
	class UserRecord extends CoreRecord {
	
		/**
		 * Sets the value of the property to the value passed.
		 * This has been extended to set the encrypted password
		 * whenever a decrypted password is set. If no decrypted
		 * password is set then the encrypted password remains
		 * the same. This also breaks out / pieces together the
		 * birthdate values and calculates the roles from the
		 * array of bits.
		 *
		 * @access public
		 * @param string $strProperty The name of the property to set
		 * @param mixed $mxdValue The value to set the property to
		 * @return mixed The value the property was set to
		 */
		public function set($strProperty, $mxdValue) {
			$this->$strProperty = $mxdValue;
			switch ($strProperty) {
				case 'password_plaintext':
					if ($mxdValue) {
						$this->set('password', PasswordHelper::encryptPassword($mxdValue));
					}
					break;
					
				case 'rolebits':
					$this->set('roles', !empty($mxdValue) ? array_sum($mxdValue) : 0);
					break;
				
				case 'birthdate':
					if (preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $mxdValue, $arrMatches)) {
						$this->birthdate_year = $arrMatches[1];
						$this->birthdate_month = $arrMatches[2];
						$this->birthdate_day = $arrMatches[3];
					}
					break;
					
				case 'birthdate_year':
				case 'birthdate_month':
				case 'birthdate_day':
					if (($intYear = $this->get('birthdate_year')) && ($intMonth = $this->get('birthdate_month')) && ($intDay = $this->get('birthdate_day'))) {
						$this->birthdate = sprintf('%04d-%02d-%02d', $intYear, $intMonth, $intDay);
					} else {
						$this->birthdate = null;
					}
					break;
					
				case 'avatar':
					$this->set('thumb', sprintf($mxdValue, 'thumb'));
					break;
			}
			return $this->$strProperty;
		}
	}