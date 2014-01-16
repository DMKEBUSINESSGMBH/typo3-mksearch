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
	 * @var int
	 */
	const USE_INDEXER_CONFIGURATION = 0;

	/**
	 * @var int
	 */
	const IS_INDEXABLE = 1;

	/**
	 * @var int
	 */
	const IS_NOT_INDEXABLE = -1;

	/**
	 * @see tx_mksearch_indexer_Base::indexData()
	 */
	public function indexData(tx_rnbase_IModel $oModel, $tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		//@todo indexing via mapping so we dont have all field in the content

		// Set uid. Take care for localized records where uid of original record
		// is stored in $rawData['l18n_parent'] instead of $rawData['uid']!
		$indexDoc->setUid($rawData['sys_language_uid'] ? $rawData['l18n_parent'] : $rawData['uid']);

		//@TODO: l18n_parent abprüfen, wenn $lang!=0 !?
		$lang = isset($options['lang']) ? $options['lang'] : 0;
		if($rawData['sys_language_uid'] != $lang) {
			return null;
			/**
			 * löschen!?
			 * Default -> wird indiziert, wenn default index
			 * 	EN -> wird gelöscht, wenn nicht default index, somit fehlt nun das default doc.
			 */
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
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookKey]) and is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookKey])){
			tx_rnbase_util_Misc::callHook(
				'mksearch',
				$hookKey,
				array(
					'rawData' => &$rawData,
					'options' => isset($options['CType.'][$rawData['CType'].'.'])?$options['CType'][$rawData['CType'].'.']:array(),
					'indexDoc' => &$indexDoc,
				)
			);
		} else {
			// No hook found - we have to take care for content and abstract by ourselves...
			$fields = isset($options['CType.'][$rawData['CType'].'.']['indexedFields.'])
				? $options['CType.'][$rawData['CType'].'.']['indexedFields.']
				: $options['CType.']['_default_.']['indexedFields.'];

			$c = '';
			foreach ($fields as $field) {
				$c .= $this->getContentByFieldAndCType($field, $rawData) . ' ';
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
	 * @param mixed $field
	 * @param array $rawData
	 *
	 * @return string
	 */
	protected function getContentByFieldAndCType($field, array $rawData) {
		switch ($rawData['CType']) {
			case 'table':
				$tempContent = $rawData[$field];
				// explode bodytext containing table cells separated
				// by the character defined in flexform
				if ($field == 'bodytext') {
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
			case 'templavoila_pi1':
				if(method_exists($this, 'getTemplavoilaElementContent')){
					$tempContent = $this->getTemplavoilaElementContent();
				} else {
					$tempContent = $rawData[$field];
				}
				break;
			default:
				$tempContent = $rawData[$field];
		}

		return $tempContent;
	}

	/**
	 * check if related data has changed and stop indexing in case
	 * @see tx_mksearch_indexer_Base::stopIndexing()
	 */
	protected function stopIndexing($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		if($tableName == 'pages') {
			$this->handlePagesChanged($rawData);
			return true;
		}
		//else
		return parent::stopIndexing($tableName, $rawData, $indexDoc, $options);
	}


	/**
	 * Adds all given models to the queue
	 * @param array $aModels
	 */
	protected function handlePagesChanged(array $aRawData) {
		// every tt_content element on this page or it's
		// subpages has to be put into the queue.
		//@todo support deleted pages, too
		$aPidList = explode(',', $this->_getPidList($aRawData['uid'], 999));

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
	* Sets the index doc to deleted if neccessary
	* @param tx_rnbase_IModel $oModel
	* @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	* @return bool
	*/
	protected function hasDocToBeDeleted(tx_rnbase_IModel $oModel, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions = array()) {
		//checkPageRights() considers deleted and parent::hasDocToBeDeleted() takes
		//care of all possible hidden parent pages
		return (!$this->getPageContent($oModel->record['pid']) || parent::hasDocToBeDeleted($oModel,$oIndexDoc,$aOptions));
	}

	/**
	 * @see tx_mksearch_indexer_Base::isIndexableRecord()
	 */
	protected function isIndexableRecord(array $sourceRecord, array $options) {
		if(
			!isset($sourceRecord['tx_mksearch_is_indexable']) ||
			($sourceRecord['tx_mksearch_is_indexable'] == self::USE_INDEXER_CONFIGURATION)
		) {
			return
				$this->isOnIndexablePage($sourceRecord,$options) &&
				$this->checkCTypes($sourceRecord,$options);
		} else {
			$isIndexable = ($sourceRecord['tx_mksearch_is_indexable'] == self::IS_INDEXABLE);
			return $isIndexable ? true : false;
		}


		return false;
	}

	/**
	 * wir brauchen auch noch die enable columns der page
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 *
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	protected function indexEnableColumns(
		tx_rnbase_IModel $model, $tableName,
		tx_mksearch_interface_IndexerDocument $indexDoc
	) {
		$indexDoc = parent::indexEnableColumns($model, $tableName, $indexDoc);

		$page = $this->getPageContent($model->record['pid']);
		$pageModel = tx_rnbase::makeInstance('tx_rnbase_model_base',$page);
		$indexDoc = parent::indexEnableColumns($pageModel, 'pages', $indexDoc,'page_');

		return $indexDoc;
	}

	/**
	 * @see tx_mksearch_indexer_TtContent::getContentType()
	 */
	public static function getContentType() {
		return array();
	}

	/**
	 * @see tx_mksearch_indexer_TtContent::getDefaultTSConfig()
	 */
	public function getDefaultTSConfig() {
		return '';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Normal.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Normal.php']);
}
