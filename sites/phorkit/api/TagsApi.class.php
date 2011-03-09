<?php
	require_once('SiteApi.class.php');
	
	/**
	 * TagsApi.class.php
	 * 
	 * This class handles all the tags API calls. This can
	 * either be called via the ApiHelper class or by URL
	 * using the ApiController.
	 *
	 * /api/tags/filter/by=tag/[tag].json								(GET: tags by tag name)
	 * /api/tags/filter/by=abbr/[abbr].json								(GET: tags by abbreviation)
	 * /api/tags/suggest.json?term=foo									(GET: auto complete suggestions)
	 *
	 * The following calls require authentication.
	 *
	 * /api/tags/for=user/add/[user id].json							(POST: add a tag to a user)
	 *
	 * The following values can be used for internal calls.
	 *
	 * /internal=nocache/												(don't load from or save to the cache)
	 * /internal=banned/												(include banned tags)
	 *
	 * Copyright 2006-2011, Phork Labs. (http://www.phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phorkit
	 * @subpackage api
	 */
	class TagsApi extends SiteApi {
	
		protected $strTagFor;
		
	
		/**
		 * Maps the API method to a method within this class
		 * and returns the response. If no method is mapped
		 * then it attempts to use the core handler.
		 *
		 * @access protected
		 */
		protected function handle() {
			$arrHandlers = array(
				'filter'		=> 'GetFiltered',
				'suggest'		=> 'GetSuggestions',
				
				'add'			=> 'DoAdd'
			);
			
			$strSegment = str_replace('.' . $this->strFormat, '', AppRegistry::get('Url')->getSegment(2));
			if (!empty($arrHandlers[$strSegment])) {
				$strMethod = $this->strMethodPrefix . $arrHandlers[$strSegment];
				$this->$strMethod();
			} else {
				parent::handle();
			}
		}
		
		
		/**
		 * Includes and instantiates a tag model class or a
		 * a tag relation (eg. user tags).
		 *
		 * @access public
		 * @return object The tag model
		 */
		public function initModel() {
			if ($this->strTagFor) {
				AppLoader::includeModel($strTagModel = ucfirst($this->strTagFor) . 'TagModel');
				$objTag = new $strTagModel();
			} else {
				AppLoader::includeModel('TagModel');
				$objTag = new TagModel();
			}
			
			return $objTag;
		}
		
		
		/**
		 * Gets the result parameters from the URL and returns
		 * the data to be extracted by the display method.
		 *
		 * @access protected
		 * @return array The compacted data
		 */
		protected function getResultParams() {
			$objUrl = AppRegistry::get('Url');
			
			$intNumResults = !empty($this->arrParams['num']) ? $this->arrParams['num'] : 10;
			$intPage = !empty($this->arrParams['p']) ? $this->arrParams['p'] : 1;
			$arrFilters = array(
				'Conditions' => array(),
				'Limit' => $intNumResults, 
				'Offset' => ($intPage - 1) * $intNumResults
			);
			
			if ($this->blnInternal) {
				$arrInternal = explode(',', $objUrl->getFilter('internal'));
				if (in_array('nocache', $arrInternal)) {
					$this->blnNoCache = true;
				}
				if (in_array('banned', $arrInternal)) {
					$arrFilters['AutoFilterOff'] = true;
				}
			} else {
				$arrInternal = array();
			}
			
			return compact('arrFilters', 'arrInternal');
		}
		
		
		/**
		 * Verifies the parameters from the URL. There is
		 * currently nothing to verify.
		 *
		 * @access protected
		 * @return boolean True if valid
		 */
		protected function verifyParams() {
			$this->strTagFor = AppRegistry::get('Url')->getFilter('for');
			return true;
		}
		
		
		/*****************************************/
		/**     HANDLER METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Gets the filtered users. Defaults to 10 results but
		 * is configurable.
		 *
		 * @access protected
		 */
		protected function handleGetFiltered() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				extract($this->getResultParams());
				
				if (!$this->loadFromCache()) {
					$objTag = $this->initModel();
					
					$objUrl = AppRegistry::get('Url');
					$strFilterBy = $objUrl->getFilter('by');
					$mxdFilter = str_replace('.' . $this->strFormat, '', $objUrl->getSegment(3));
					
					switch ($strFilterBy) {
						case 'tag':
							$blnResult = $objTag->loadByTag($mxdFilter, $arrFilters);
							break;
							
						case 'abbr':
							$blnResult = $objTag->loadByAbbr($mxdFilter, $arrFilters);
							break;
					}
					
					if ($blnResult) {
						$this->blnSuccess = true;
						
						if ($objTag->count()) {
							$this->arrResult = array(
								'tags' => $this->formatTags($objTag)
							);
						} else {
							$this->arrResult = array(
								'tags' => array()
							);
						}
						
						$this->saveToCache(300);
					} else {
						trigger_error(AppLanguage::translate('There was an error loading the tag data'));
						$this->error();
					}
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/**
		 * Gets the auto-complete suggestions for the 
		 * existing tags.
		 *
		 * @access protected
		 */
		protected function handleGetSuggestions() {
			if ($this->verifyRequest('GET') && $this->verifyParams()) {
				if (!$this->loadFromCache()) {
					extract($this->getResultParams());
					$objTag = $this->initModel();
					
					$arrFilters['Conditions'] = array(
						array(
							'Column'	=> 'abbr',
							'Value'		=> TagModel::formatTag($this->arrParams['term']),
							'Operator'	=> 'begins with'
						)
					);
					
					if ($objTag->load($arrFilters)) {
						$this->blnSuccess = true;
						
						if ($objTag->count()) {
							$this->arrResult = array(
								'tags' => $this->formatTags($objTag)
							);
						} else {
							$this->arrResult = array(
								'tags' => array()
							);
						}
						
						$this->saveToCache(300);
					} else {
						trigger_error(AppLanguage::translate('There was an error loading the tag data'));
						$this->error(400);
					}
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/*****************************************/
		/**     ACTION METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Adds a tag to a record. Currently only set up
		 * to support user tags.
		 *
		 * @access protected
		 */
		protected function handleDoAdd() {
			if ($this->verifyRequest('POST') && $this->verifyParams()) {
				if ($this->blnAuthenticated) {
					if ($intUserId = (int) str_replace('.' . $this->strFormat, '', AppRegistry::get('Url')->getSegment(3))) {
						AppLoader::includeUtility('Sanitizer');
						if (!($arrUnsanitary = Sanitizer::sanitizeArray($this->arrParams))) {
							if (!empty($this->arrParams['tag'])) {
								$objTag = $this->initModel();
								$strTag = $this->arrParams['tag'];
								
								switch ($this->strTagFor) {
									case 'user':
										if ($intUserId == AppRegistry::get('UserLogin')->getUserId()) {
											if ($objTag->loadByUserIdAndTag($intUserId, $strTag) && $objTagRecord = $objTag->current()) {
												$objTagRecord->set('weight', $objTagRecord->get('weight') + 1);
											} else {
												$objTag->import(array(
													'userid'	=> $intUserId,
													'tag'		=> $strTag
												));
											}
											$blnContinue = true;
										} else {
											trigger_error(AppLanguage::translate('Invalid user permissions'));
											$this->error(400);
										}
										break;
										
									default:
										trigger_error(AppLanguage::translate('Missing tag type'));
										$this->error(400);
										break;
								}
							
								if (!empty($blnContinue)) {
									if ($objTag->save()) {
										CoreAlert::alert('The tag was added successfully.');
										$this->blnSuccess = true;
										$this->intStatusCode = 201;
									} else {
										trigger_error(AppLanguage::translate('There was an error adding the tag'));
										$this->error();
									}
								}
							} else {
								trigger_error(AppLanguage::translate('Missing tag'));
								$this->error(400);
							}
						} else {
							trigger_error(AppLanguage::translate('The following value(s) contain illegal data: %s', implode(', ', array_map('htmlentities', $arrUnsanitary))));
							$this->error(400);
						}
					} else {
						trigger_error(AppLanguage::translate('Missing ID'));
						$this->error(400);
					}
				} else {
					trigger_error(AppLanguage::translate('Missing or invalid authentication'));
					$this->error(401);
				}
			} else {
				$this->error(400);
			}
		}
		
		
		/*****************************************/
		/**     FORMAT METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Formats the tags into an array to be encoded.
		 *
		 * @access public
		 * @param object $objTag The list of tag records to format
		 * @param boolean $blnCount Whether to add the tag count
		 * @return array The tags in array format
		 */
		public function formatTags($objTag, $blnCount = false) {
			$arrTags = array();
			
			while (list(, $objTagRecord) = $objTag->each()) {
				$arrTag = array(
					'id'		=> $objTagRecord->get('tagid'),
					'tag'		=> $objTagRecord->get('tag'),
					'abbr'		=> $objTagRecord->get('abbr')
				);
				
				if ($blnCount) {
					$arrTag['count'] = $objTagRecord->get('tally');
				}
				
				$arrTags[] = $arrTag;
			}
			$objTag->rewind();
			
			return $arrTags;
		}
		
		
		/**
		 * Formats an XML node name. This is to prevent child
		 * nodes being named with a generic name.
		 *
		 * @access public
		 * @param string $strNode The name of the node to potentially format
		 * @param string $strParentNode The name of the parent node
		 * @return string The formatted node name
		 */
		public function getXmlNodeName($strNode, $strParentNode) {
			switch ($strParentNode) {
				case 'tags':
					$strNode = substr($strParentNode, 0, -1);
					break;
			}
			return $strNode;
		}
	}