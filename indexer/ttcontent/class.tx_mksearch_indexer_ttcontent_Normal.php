<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_indexer
 *  @author Hannes Bochmann <dev@dmk-ebusiness.de>
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
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
	 * Do the actual indexing for the given model
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @param array $rawData
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 * @return tx_mksearch_interface_IndexerDocument|null
	 */
	public function indexData(
		tx_rnbase_IModel $model,
		$tableName,
		$rawData,
		tx_mksearch_interface_IndexerDocument $indexDoc, $options
	) {
		// @todo indexing via mapping so we dont have all field in the content

		// Set uid. Take care for localized records where uid of original record
		// is stored in $rawData['l18n_parent'] instead of $rawData['uid']!
		$indexDoc->setUid($rawData['sys_language_uid'] ? $rawData['l18n_parent'] : $rawData['uid']);

		// @TODO: l18n_parent abprüfen, wenn $lang!=0 !?
		$lang = isset($options['lang']) ? $options['lang'] : 0;
		if ($rawData['sys_language_uid'] != $lang) {
			return NULL;
		}

		// we added our own header_layout (101). as long as there is no
		// additional config for this type (101) it isn't displayed in the FE
		// but indexed for Solr. so use header_layout == 100 if the title
		// should neither be indexed nor displayed in ther FE. use header_layout == 101
		// if the title shouldn't be displayed in the FE but indexed
		$title = '';
		if ($rawData['header_layout'] != 100) {
			// Decode HTML
			$title = trim(tx_mksearch_util_Misc::html2plain($rawData['header']));
		}

		// optional fallback to page title, if the content title is empty
		if (empty($title) && empty($options['leaveHeaderEmpty'])) {
			$pageData = $this->getPageContent($model->getPid());
			$title = $pageData['title'];
		}

		$indexDoc->setTitle($title);

		$indexDoc->setTimestamp($rawData['tstamp']);

		$indexDoc->addField('pid', $model->record['pid'], 'keyword');
		$indexDoc->addField('CType', $rawData['CType'], 'keyword');

		if ($options['addPageMetaData']) {
			// @TODO: keywords werden doch immer kommasepariert angegeben,
			// warum mit leerzeichen trennen, das macht die keywords kaputt.
			$separator = (!empty($options['addPageMetaData.']['separator'])) ? $options['addPageMetaData.']['separator'] : ' ';
			// @TODO:
			//        konfigurierbar machen: description, author, etc.
			//        könnte wichtig werden!?
			$pageData = $pageData ? $pageData : $this->getPageContent($model->record['pid']);
			if (!empty($pageData['keywords'])) {
				$keywords = explode($separator, $pageData['keywords']);
				foreach ($keywords as $key => $keyword) {
					$keywords[$key] = trim($keyword);
				}
				$indexDoc->addField('keywords_ms', $keywords, 'keyword');
			}
		}

		if ($options['indexPageData']) {
			$this->indexPageData($indexDoc, $options);
		}

		// Try to call hook for the current CType.
		// A hook MUST call both $indexDoc->setContent and
		// $indexDoc->setAbstract (respect $indexDoc->getMaxAbstractLength())!
		$hookKey = 'indexer_core_TtContent_prepareData_CType_' . $rawData['CType'];
		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookKey])
			&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookKey])
		) {
			tx_rnbase_util_Misc::callHook(
				'mksearch',
				$hookKey,
				array(
					'rawData' => &$rawData,
					'options' => isset($options['CType.'][$rawData['CType'] . '.'])
						? $options['CType'][$rawData['CType'] . '.']
						: array(),
					'indexDoc' => &$indexDoc,
				)
			);
		} else {
			// No hook found - we have to take care for content and abstract by ourselves...
			$fields = isset($options['CType.'][$rawData['CType'] . '.']['indexedFields.'])
				? $options['CType.'][$rawData['CType'] . '.']['indexedFields.']
				: $options['CType.']['_default_.']['indexedFields.'];

			$c = '';
			foreach ($fields as $field) {
				$c .= $this->getContentByFieldAndCType($field, $rawData) . ' ';
			}

			// Decode HTML
			$c = trim(tx_mksearch_util_Misc::html2plain($c));

			// kein inhalt zum indizieren
			if (empty($title) && empty($c)) {
				$indexDoc->setDeleted(TRUE);
				return $indexDoc;
			}

			// Include $title into indexed content
			$indexDoc->setContent($title . ' ' . $c);
			$indexDoc->setAbstract(empty($c) ? $title : $c, $indexDoc->getMaxAbstractLength());
		}
		return $indexDoc;
	}

	/**
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 */
	protected function indexPageData(tx_mksearch_interface_IndexerDocument $indexDoc, array $options) {
		$pageRecord = $this->getPageContent($this->getModelToIndex()->record['pid']);
		$pageModel = tx_rnbase::makeInstance('tx_rnbase_model_base', $pageRecord);
		$pageModel->setTableName('pages');

		if (!empty($options['pageDataFieldMapping.'])) {
			$this->indexModelByMapping(
				$pageModel, $options['pageDataFieldMapping.'], $indexDoc, 'page_', $options
			);
		}
	}

	/**
	 * get the content by field and CType
	 *
	 * @param mixed $field
	 * @param array $rawData
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
						if ($flexParsingOptions['tableparsing_quote']['vDEF']) {
							$tempContent = str_replace(
								chr($flexParsingOptions['tableparsing_quote']['vDEF']),
								'', $tempContent
							);
						}
						if ($flexParsingOptions['tableparsing_delimiter']['vDEF']) {
							$tempContent = str_replace(
								chr($flexParsingOptions['tableparsing_delimiter']['vDEF']),
								' ', $tempContent
							);
						}
					}
				}
				break;
			case 'templavoila_pi1':
				if (method_exists($this, 'getTemplavoilaElementContent')) {
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
	 * Shall we break the indexing for the current data?
	 *
	 * when an indexer is configured for more than one table
	 * the index process may be different for the tables.
	 * overwrite this method in your child class to stop processing and
	 * do something different like putting a record into the queue
	 * if it's not the table that should be indexed
	 *
	 * @param string $tableName
	 * @param array $rawData
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 * @return bool
	 */
	protected function stopIndexing($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		if ($tableName == 'pages') {
			$this->handlePagesChanged($rawData);
			return TRUE;
		}

		return parent::stopIndexing($tableName, $rawData, $indexDoc, $options);
	}


	/**
	 * Adds all given models to the queue
	 *
	 * @param array $aRawData
	 * @return void
	 */
	protected function handlePagesChanged(array $aRawData) {
		// every tt_content element on this page or it's
		// subpages has to be put into the queue.
		// @todo support deleted pages, too
		$aPidList = explode(',', $this->_getPidList($aRawData['uid'], 999));

		if (!empty($aPidList)) {
			$oIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
			$aFrom = array('tt_content', 'tt_content');

			foreach ($aPidList as $iPid) {
				// if the site is not existent we have one empty entry.
				if (!empty($iPid)) {
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

					foreach ($aRows as $aRow) {
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
	private function checkCTypes($sourceRecord, $options) {
		$ctypes = $this->getConfigValue('ignoreCTypes', $options);
		if (is_array($ctypes) && count($ctypes)) {
			// Wenn das Element eines der definierten ContentTypen ist,
			// NICHT indizieren
			if (in_array($sourceRecord['CType'], $ctypes)) {
				return FALSE;
			}
		} else {
			// Jetzt alternativ auf die includeCTypes prüfen
			$ctypes = $this->getConfigValue('includeCTypes', $options);
			if (is_array($ctypes) && count($ctypes)) {
				// Wenn das Element keines der definierten ContentTypen ist,
				// NICHT indizieren
				if (!in_array($sourceRecord['CType'], $ctypes)) {
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Sets the index doc to deleted if neccessary
	 *
	 * @param tx_rnbase_IModel $model
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param array $aOptions
	 * @return bool
	 */
	protected function hasDocToBeDeleted(
		tx_rnbase_IModel $model,
		tx_mksearch_interface_IndexerDocument $oIndexDoc,
		$aOptions = array()
	) {
		// checkPageRights() considers deleted and parent::hasDocToBeDeleted() takes
		// care of all possible hidden parent pages
		return (!$this->getPageContent($model->record['pid'])
			|| parent::hasDocToBeDeleted($model, $oIndexDoc, $aOptions));
	}

	/**
	 * Prüft ob das Element speziell in einem Seitenbaum oder auf einer Seite liegt,
	 * der/die inkludiert oder ausgeschlossen werden soll.
	 * Der Entscheidungsbaum dafür ist relativ, sollte aber durch den Code
	 * illustriert werden.
	 *
	 * @param array $sourceRecord
	 * @param array $options
	 * @return bool
	 */
	protected function isIndexableRecord(array $sourceRecord, array $options) {
		if (
			!isset($sourceRecord['tx_mksearch_is_indexable']) ||
			($sourceRecord['tx_mksearch_is_indexable'] == self::USE_INDEXER_CONFIGURATION)
		) {
			return
				$this->isOnIndexablePage($sourceRecord, $options) &&
				$this->checkCTypes($sourceRecord, $options);
		} else {
			$isIndexable = ($sourceRecord['tx_mksearch_is_indexable'] == self::IS_INDEXABLE);
			return $isIndexable ? TRUE : FALSE;
		}

		return FALSE;
	}

	/**
	 * wir brauchen auch noch die enable columns der page
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	protected function indexEnableColumns(
		tx_rnbase_IModel $model, $tableName,
		tx_mksearch_interface_IndexerDocument $indexDoc
	) {
		$indexDoc = parent::indexEnableColumns($model, $tableName, $indexDoc);

		$page = $this->getPageContent($model->record['pid']);
		$pageModel = tx_rnbase::makeInstance('tx_rnbase_model_base', $page);
		$indexDoc = parent::indexEnableColumns($pageModel, 'pages', $indexDoc, 'page_');

		return $indexDoc;
	}

	/**
	 * Return content type identification
	 *
	 * @return array([extension key], [key of content type])
	 */
	public static function getContentType() {
		return array();
	}

	/**
	 * Return the default Typoscript configuration for this indexer
	 *
	 * @return string
	 */
	public function getDefaultTSConfig() {
		return '';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Normal.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Normal.php']);
}
