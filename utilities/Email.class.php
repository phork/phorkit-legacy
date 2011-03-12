<?php	
	/**
	 * Email.class.php
	 *
	 * Sends emails with the correct headers. This
	 * has been extended from the standard email
	 * utility to send via Postmark.
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
	class Email {
		
		/**
		 * Sends a plain text email via Postmark.
		 *
		 * @access public
		 * @param string $strToEmail The address to send the email to
		 * @param string $strToName The name to address the email to
		 * @param string $strFromEmail The address to send the email from
		 * @param string $strFromName The name to send the email from
		 * @param string $strSubject The email subject
		 * @param string $strBody The email body
		 * @param string $strTag The tag/category of the email (postmark only)
		 * @return boolean True on success
		 */
		static public function sendTextEmail($strToEmail, $strToName, $strFromEmail, $strFromName, $strSubject, $strBody, $strTag) {
			if (AppConfig::get('PostmarkEnabled')) {
				try {
					AppLoader::includeExtension('postmark/', 'Postmark', true);
						
					if (!defined('POSTMARKAPP_API_KEY')) {
						$arrConfig = AppConfig::load('postmark');
						define('POSTMARKAPP_API_KEY', $arrConfig['PostmarkApiKey']);
					}
					
					Mail_Postmark::compose()
						->to($strToEmail, $strToName)
						->from($strFromEmail, $strFromName)
						->subject($strSubject)
						->messagePlain($strBody)
						->tag($strTag)
						->send();
						
					return true;
				} catch (Exception $objException) {
					return false;
				}
			} else {
				$strTo = "{$strToName} <{$strToEmail}>";
				$strFrom = "{$strFromName} <{$strFromEmail}>";
				$strHeaders = "From: {$strFromName} <{$strFromEmail}>\r\n";
				
				return mail($strTo, $strSubject, $strBody, $strHeaders);
			}
		}
	}