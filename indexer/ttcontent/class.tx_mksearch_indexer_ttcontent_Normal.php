<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_indexer
 *  @author Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * benötigte Klassen einbinden
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_indexer_Base');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
tx_rnbase::load('tx_rnbase_util_Misc');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * Indexer service for core.tt_content called by the "mksearch" extension.
 * takes care of normal tt_content without templavoila support.
 * 
 * @author Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
 */
class tx_mksearch_indexer_ttcontent_Normal extends tx_mksearch_indexer_Base {

	/**
	 * Return content type identification.
	 * This identification is part of the indexed data
	 * and is used on later searches to identify the search results.
	 * You're completely free in the range of values, but take care
	 * as you at the same time are responsible for
	 * uniqueness (i.e. no overlapping with other content types) and
	 * consistency (i.e. recognition) on indexing and searching data.
	 *
	 * @return array([extension key], [key of content type])
	 */
	public static function getContentType() {
		return array('core', 'tt_content');
	}

	/**
	 * @see tx_mksearch_indexer_Base::indexData()
	 */
	public function indexData(tx_rnbase_model_base $oModel, $tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		//@todo indexing via mapping so we dont have all field in the content

		// Set uid. Take care for localized records where uid of original record
		// is stored in $rawData['l18n_parent'] instead of $rawData['uid']!
		$indexDoc->setUid($rawData['sys_language_uid'] ? $rawData['l18n_parent'] : $rawData['uid']);

		//@TODO: l18n_parent abprüfen, wenn $lang!=0 !?
		$lang = isset($options['lang']) ? $options['lang'] : 0;
		if($rawData['sys_language_uid'] != $lang) {
			//wir löschen den record aus dem Indexer, falls er schon existiert.
			$indexDoc->setDeleted(true);
			return $indexDoc;
		}

		//we added our own header_layout (101). as long as there is no
		//additional config for this type (101) it isn't displayed in the FE
		//but indexed for Solr. so use header_layout == 100 if the title
		//should neither be indexed nor displayed in ther FE. use header_layout == 101
		//if the title shouldn't be displayed in the FE but indexed
		$title = '';
		if ($rawData['header_layout'] != 100) {
			// Decode HTML
			$title = trim(tx_mksearch_util_Misc::html2plain($rawData['header']));
		}
		$indexDoc->setTitle($title);

		$indexDoc->setTimestamp($rawData['tstamp']);
		$indexDoc->setFeGroups(
			tx_mksearch_service_indexer_core_Config::getEffectiveContentElementFeGroups(
				$rawData['pid'],
				t3lib_div::trimExplode(',', $rawData['fe_group'], true)
			)
		);

		$indexDoc->addField('pid', $oModel->record['pid'], 'keyword');
		$indexDoc->addField('CType', $rawData['CType'], 'keyword');

		if($options['addPageMetaData']) {
			// @TODO: keywords werden doch immer kommasepariert angegeben, warum mit leerzeichen trennen, das macht die keywords kaputt.
			$separator = (!empty($options['addPageMetaData.']['separator'])) ? $options['addPageMetaData.']['separator'] : ' ';
			// @TODO: nur holen was wir benötigen (keywords)
			//		  konfigurierbar machen: description, author, etc. könnte wichtig werden!?
			$pageData = $this->getPageContent($oModel->record['pid']);
			if(!empty($pageData[0]['keywords'])) {
				$keywords = explode($separator, $pageData[0]['keywords']);
				foreach($keywords as $key => $keyword) $keywords[$key] = trim($keyword);
				$indexDoc->addField('keywords_ms', $keywords, 'keyword');
			}
		}

		// Try to call hook for the current CType.
		// A hook MUST call both $indexDoc->setContent and
		// $indexDoc->setAbstract (respect $indexDoc->getMaxAbstractLength())!
		$hookKey = 'indexer_core_TtContent_prepareData_CType_'.$rawData['CType'];
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookKey]) and is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookKey]))
			tx_rnbase_util_Misc::callHook(
											'mksearch',
											$hookKey,
											array(
												'rawData' => &$rawData,
												'options' => isset($options['CType.'][$rawData['CType'].'.'])?$options['CType'][$rawData['CType'].'.']:array(),
												'indexDoc' => &$indexDoc,
											)
										);
		else {
			// No hook found - we have to take care for content and abstract by ourselves...
			$fields = isset($options['CType.'][$rawData['CType'].'.']['indexedFields.']) ?
						$options['CType.'][$rawData['CType'].'.']['indexedFields.'] :
						$options['CType.']['_default_.']['indexedFields.'];

			$c = '';
			foreach ($fields as $f) {
				// Special dealing with diffent CTypes:
				switch ($rawData['CType']) {
					case 'table':
						$tempContent = $rawData[$f];
						// explode bodytext containing table cells separated
						// by the character defined in flexform
						if ($f == 'bodytext') {
							// Get table parsing options from flexform
							$flex = t3lib_div::xml2array($rawData['pi_flexform']);
							if (is_array($flex)) {
								$flexParsingOptions = $flex['data']['s_parsing']['lDEF'];

								// Replace special parsing characters
								if ($flexParsingOptions['tableparsing_quote']['vDEF'])
									$tempContent = str_replace(chr($flexParsingOptions['tableparsing_quote']['vDEF']), '', $tempContent);
								if ($flexParsingOptions['tableparsing_delimiter']['vDEF'])
									$tempContent = str_replace(chr($flexParsingOptions['tableparsing_delimiter']['vDEF']), ' ', $tempContent);
							}
						}
						break;
					default:
						$tempContent = $rawData[$f];
				}
				$c .= $tempContent . ' ';
			}

			// Decode HTML
			$c = trim(tx_mksearch_util_Misc::html2plain($c));

			// kein inhalt zum indizieren
			if(empty($title) && empty($c)) {
				$indexDoc->setDeleted(true);
				return $indexDoc;
			}

			// Include $title into indexed content
			$indexDoc->setContent($title . ' ' . $c);
			$indexDoc->setAbstract(empty($c) ? $title : $c, $indexDoc->getMaxAbstractLength());
		}
		return $indexDoc;
	}

	/**
	 * check if related data has changed and stop indexing in case
	 * @see tx_mksearch_indexer_Base::stopIndexing()
	 */
	protected function stopIndexing($sTableName, $aRawData, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions) {
		if($sTableName == 'pages') {
			$this->handlePagesChanged($aRawData);
			return true;
		}
		//else
		//don't stop
	}


	/**
	 * Adds all given models to the queue
	 * @param array $aModels
	 */
	protected function handlePagesChanged(array $aRawData) {
		// every tt_content element on this page or it's
		// subpages has to be put into the queue.
		//@todo support deleted pages, too
		$oDbUtil = tx_rnbase::makeInstance('tx_rnbase_util_DB');
		$aPidList = explode(',',$oDbUtil->_getPidList($aRawData['uid'],999));

		if(!empty($aPidList)){
			$oIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
			$aFrom = array('tt_content', 'tt_content');

			foreach ($aPidList as $iPid) {
				// if the site is not existent we have one empty entry.
				if(!empty($iPid)){
					// hidden/deleted datasets can be excluded as they are not indexed
					// see isIndexableRecord()
					$aOptions = array(
						'where' => 'tt_content.pid=' . $iPid,
					);
					// as the pid list can be very long, we don't risk to create a sql
					// statement that is too long. we are fine with a database access
					// for each pid in the list as we are in the BE and performance shouldn't
					// be a big concern!
					$aRows = tx_rnbase_util_DB::doSelect('tt_content.uid', $aFrom, $aOptions);

					foreach ($aRows as $aRow){
						$oIndexSrv->addRecordToIndex('tt_content', $aRow['uid']);
					}
				}
			}
		}
	}

	/**
	 * Prüft ob das Element anhand des CType inkludiert oder ignoriert werden soll
	 *
	 * @param array $sourceRecord
	 * @param array $options
	 * @return bool
	 */
	private function checkCTypes($sourceRecord,$options) {
		$ctypes = $this->getConfigValue('ignoreCTypes', $options);
		if(is_array($ctypes) && count($ctypes)) {
			// Wenn das Element eines der definierten ContentTypen ist,
			// NICHT indizieren
			if(in_array($sourceRecord['CType'],$ctypes))
				return false;
		}
		else {
			// Jetzt alternativ auf die includeCTypes prüfen
			$ctypes = $this->getConfigValue('includeCTypes', $options);
			if(is_array($ctypes) && count($ctypes)) {
				// Wenn das Element keines der definierten ContentTypen ist,
				// NICHT indizieren
				if(!in_array($sourceRecord['CType'],$ctypes))
					return false;
			}
		}

		return true;
	}

	/**
	 * Returns the model to be indexed
	 *
	 * @param array $aRawData
	 *
	 * @return tx_mksearch_model_irfaq_Question
	 */
	protected function createModel(array $aRawData) {
		$oModel = tx_rnbase::makeInstance('tx_rnbase_model_Base', $aRawData);

		return $oModel;
	}

	/**
	 * Returns the Pagecontent
	 *
	 * @param int $pid
	 */
	protected function getPageContent($pid) {
		$options = array(
			'where' => 'pages.uid=' . $pid,
			'enablefieldsfe' => true,
		);
		$from = array('pages', 'pages');
		return tx_rnbase_util_DB::doSelect('*', $from, $options);
	}

	/**
	 * Return the default Typoscript configuration for this indexer.
	 *
	 * Note that this config is not used for actual indexing
	 * but only serves as assistance when actually configuring an indexer!
	 * Hence all possible configuration options should be set or
	 * at least be mentioned to provide an easy-to-access inline documentation!
	 *
	 * @return string
	 *
	 */
	public function getDefaultTSConfig() {
		return <<<CONF
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields{
#	my_fixed_field_singlevalue = first
#   my_fixed_field_multivalue{
#      0 = first
#      1 = second
#   }
# }

addPageMetaData = 0
addPageMetaData.separator = ,

# Configuration for each cType:
CType {
	# Default configuration for unconfigured cTypes:
	_default_ {
		# Fields used for building the index:
		indexedFields {
			0 = bodytext
			1 = imagecaption
			2 = altText
			3 = titleText
		}
	}
	# cType "text":
	text.indexedFields {
		0 = bodytext
	}
}

# cTypes of content elements to be excluded from indexing.
# Obviously, the respective "indexedFields" option is ignored in this case.
#includeCTypes = list,header,text,textpic,image,text,bullets,table,html
ignoreCTypes = search,mailform,login,list,div

# \$sys_language_uid of the desired language
# lang = 1

### delete from or abort indexing for the record if isIndexableRecord or no record?
 deleteIfNotIndexable = 0

# White lists: Explicitely include items in indexing by various conditions.
# Note that defining a white list deactivates implicite indexing of ALL pages,
# i.e. only white-listed pages are defined yet!
# May also be combined with option "exclude"
include {
	# Include several content elements pages in indexing:
#	elements {
#		# Include tt_content #17
#		0 = 17
#		# Include tt_content #26
#		1 = 26
#	}
# Include several pages in indexing:
#		# Include page #18 and #27
#	pages = 18,27
# Include complete page trees (i. e. pages with all their children) in indexing:
#	pageTrees {
#		# Include page tree with root page #19
#		0 = 19
#		# Include page  tree with root page #28
#		1 = 28
#	}
}
# Black lists: Exclude pages from indexing by various conditions.
# May also be combined with option "include", while "exclude" option
# takes precedence over "include" option.
exclude {
	# Exclude several pages from indexing. @see respective include option
#	pages ...
 	# Exclude complete page trees (i. e. pages with all their children) from indexing.
 	# @see respective include option
#	pageTrees ...
}

CONF;
	}
	
	/**
	* Sets the index doc to deleted if neccessary
	* @param tx_rnbase_model_base $oModel
	* @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	* @return bool
	*/
	protected function hasDocToBeDeleted(tx_rnbase_model_base $oModel, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions = array()) {
		//checkPageRights() considers deleted and parent::hasDocToBeDeleted() takes
		//care of all possible hidden parent pages
		return (!$this->checkPageRights($oModel) || parent::hasDocToBeDeleted($oModel,$oIndexDoc,$aOptions));
	}
	
	/**
	 * @see tx_mksearch_indexer_Base::isIndexableRecord()
	 */
	protected function isIndexableRecord(array $sourceRecord, array $options) {
		if(	$this->checkPageTree($sourceRecord,$options)//is the element in a valid page tree?
			&& $this->checkCTypes($sourceRecord,$options)//is it's CType valid?
			&& tx_mksearch_util_Misc::isIndexable($sourceRecord, $options)//and is the element on a valid page?
		) {
			return true;
		}
	
		return false;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Normal.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Normal.php']);
}