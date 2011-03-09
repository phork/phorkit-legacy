<?php
	/**
	 * CacheHelper.class.php
	 *
	 * Clears the appropriate caches after a record has
	 * been saved or deleted.
	 *
	 * Copyright 2006-2011, Phork Labs. (http://www.phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage utilities
	 */
	class CacheHelper {
	
		/**
		 * Clears a specific set of caches based on the type
		 * of object saved or deleted. This is called from the 
		 * ModelCache helper. This can either clear specific
		 * caches or flush namespaces. Alternately, caches can
		 * just be set to expire instead of manual clearing.
		 * 
		 * This currently flushes various presentation namespaces
		 * that are used in the associated API classes.
		 *
		 * The delete feature works best if the deleted records
		 * have been loaded into the model object.
		 *
		 * @access public
		 * @param object $objModel The model object operated on
		 * @param string $strFunction The save or delete function called
		 * @param boolean $blnInserted True if one or more records were inserted if post-save
		 * @param array $arrDeleted An array of filters used to deleted records if post-delete
		 * @return boolean True on success
		 * @static
		 */
		static public function clearByModel($objModel, $strFunction, $blnInserted, $arrDeleted) {
			$objCache = AppRegistry::get('Cache');
			
			switch (get_class($objModel)) {
				case 'UserEventModel':
					if ($blnInserted) {
						$objCache->initPresentation();
						if ($intUserId = $objModel->current()->get('userid')) {
							$objCache->flushNS(sprintf(AppConfig::get('UserEventNamespace'), $intUserId));
						}
					}
					break;
					
				case 'UserConnectionModel':
					$objCache->initPresentation();
					if ($intUserId = $objModel->current()->get('userid')) {
						$objCache->flushNS(sprintf(AppConfig::get('UserConnectionNamespace'), $intUserId));
					}
					if ($intConnectionId = $objModel->current()->get('connectionid')) {
						$objCache->flushNS(sprintf(AppConfig::get('UserConnectionNamespace'), $intConnectionId));
					}
					break;
					
				case 'UserTagModel':
					if ($blnInserted) {
						$objCache->initPresentation();
						if ($strTag = $objModel->current()->get('tag')) {
							$objCache->flushNS(sprintf(AppConfig::get('UserTagNamespace'), TagModel::formatTag($strTag)));
						}
					}
					break;
			}
			
			return true;
		}
		
		
		/**
		 * Extracts a specific filter from an array of filters
		 * used with the delete method.
		 *
		 * @access protected
		 * @param array $arrFilters The filters to extract from
		 * @param string $strColumn The column to extract 
		 * @return array The array of filter values
		 * @static
		 */
		protected function getFilter($arrFilters, $strColumn) {
			if (!empty($arrFilters['Conditions'])) {
				foreach ($arrFilters['Conditions'] as $arrFilter) {
					if (!empty($arrFilter['Column']) && $arrFilter['Column'] == $strColumn) {
						return $arrFilter['Value'];
					}
				}
			}
		}
	}