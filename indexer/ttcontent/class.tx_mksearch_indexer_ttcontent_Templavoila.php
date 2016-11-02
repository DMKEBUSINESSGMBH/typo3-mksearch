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

tx_rnbase::load('tx_mksearch_indexer_ttcontent_Normal');

/**
 * takes care of tt_content with templavoila support.
 *
 * @todo wenn templavoila installiert ist, sollten allen elemente, die in einem tv
 * container liegen in die queue gelegt werden. beim indizieren muss dann noch geprüft
 * werden ob das element parents hat und diese nicht hidden sind.
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
 */
class tx_mksearch_indexer_ttcontent_Templavoila extends tx_mksearch_indexer_ttcontent_Normal {
	/**
	 * The references (pids) of this element. if templavoila is
	 * not installed it contains only the pid of the element it self
	 * @var array
	 */
	protected $aReferences = array();

	/**
	 * The indexable references (pids) of this element. These references
	 * are taken to check if the element has to be deleted.
	 * @var array
	 */
	protected $aIndexableReferences = array();

	/**
	 * Sets the index doc to deleted if neccessary
	 *
	 * @param tx_rnbase_IModel $oModel
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param array $aOptions
	 * @return bool
	 */
	protected function hasDocToBeDeleted(
		tx_rnbase_IModel $oModel,
		tx_mksearch_interface_IndexerDocument $oIndexDoc,
		$aOptions = array()
	) {

		// if we got not a single reference,
		// even not the element itself, it should be deleted
		if (empty($this->aIndexableReferences)) {
			return TRUE;
		}

		// now we have to check if at least one reference
		// remains that has not to be deleted.
		$aStillIndexableReferences = array();
		foreach ($this->aIndexableReferences as $iPid) {
				// set the pid
			$oModel->record['pid'] = $iPid;

			if (!parent::hasDocToBeDeleted($oModel, $oIndexDoc, $aOptions)) {
					// set value as key to avoid doubles
				$aStillIndexableReferences[$iPid] = $iPid;
			}
		}

			// any valid references left?
		if (empty($aStillIndexableReferences)) {
			return TRUE;
		}
			// finally set the pid of the first reference to our element
			// as we can not know which array index is the first we simply use
			// array_shift which returns the first off shifted element,
			// our desired first one
		else {
			$oModel->record['pid'] = array_shift($aStillIndexableReferences);
		}

		return FALSE;
	}

	/**
	 * Returns all references (pids)  this element has. if templavoila is
	 * not installed we simply return the pid of the element
	 *
	 * @param tx_rnbase_IModel $oModel
	 * @return array
	 */
	protected function getReferences(tx_rnbase_IModel $oModel) {
			// so we have to fetch all references
			// we just need to check this table for entries for this element
		$aSqlOptions = array(
			'where' => 'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('tt_content', 'sys_refindex') .
					' AND ref_uid=' . (int) $oModel->getUid() .
					' AND deleted=0',
			'enablefieldsoff' => TRUE,
		);
		$aFrom = array('sys_refindex', 'sys_refindex');
		$aRows = tx_rnbase_util_DB::doSelect('tablename, recuid', $aFrom, $aSqlOptions);

			// now we need to collect the pids of all references. either a
			// reference is a page than we simply use it's pid or the
			// reference is another tt_content.
			// in the last case we take the pid of this element
		$aReferences = array();
		if (!empty($aRows)) {
			foreach ($aRows as $aRow) {
				if ($aRow['tablename'] == 'pages') {
					$aReferences[] = $aRow['recuid'];
				}
				elseif ($aRow['tablename'] == 'tt_content') {
					$aSqlOptions = array(
						'where' => 'tt_content.uid=' . $aRow['recuid'],
							// checks for being hidden/deleted are made later
						'enablefieldsoff' => TRUE,
					);
					$aFrom = array('tt_content', 'tt_content');
					$aNewRows = tx_rnbase_util_DB::doSelect('tt_content.pid', $aFrom, $aSqlOptions);
					$aReferences[] = $aNewRows[0]['pid'];
				}
			}
		}

		return $aReferences;
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
		$this->aIndexableReferences = array();

			// we have to do the checks for all references and collect the indexable ones
		$return = FALSE;
		if (!empty($this->aReferences)) {
			foreach ($this->aReferences as $iPid) {
					// set the pid
				$sourceRecord['pid'] = $iPid;

				if (parent::isIndexableRecord($sourceRecord, $options)) {
						// as soon as we have a indexable reference we are fine
					$return = TRUE;
						// collect this pid as indexable
					$this->aIndexableReferences[$iPid] = $iPid;
				}
			}
		} else {
				// without any references this element is indexable but will be
				// deleted later
			$return = TRUE;
		}

		return $return;
	}

	/**
	 * Returns the model to be indexed
	 *
	 * @param array $rawData
	 * @param string $tableName
	 * @param array $options
	 * @return tx_mksearch_model_irfaq_Question
	 */
	protected function createModel(array $rawData, $tableName = NULL, $options = array()) {
		$model = parent::createModel($rawData, $tableName, $options);

			// we need all references this element has for later checks
		$this->aReferences = $this->getReferences($model);

		return $model;
	}

	/**
	 * fetches the content of an tv element
	 *
	 * @return string
	 */
	protected function getTemplavoilaElementContent() {
		$ttContentModel = $this->getModelToIndex();
		$record = $ttContentModel->getRecord();
		$this->initTsForFrontend($record['pid']);
		$this->adjustIncludeLibsPathForBe();

		$templavoilaPlugin = tx_rnbase::makeInstance('tx_templavoila_pi1');
		$templavoilaPlugin->cObj = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getContentObjectRendererClass());
		$templavoilaPlugin->cObj->data = $record;
		$templavoilaPlugin->cObj->currentRecord = 'tt_content:' . $ttContentModel->getUid();

		return $templavoilaPlugin->renderElement($record, 'tt_content');
	}

	/**
	 * initializes the tsfe for the tv elements
	 *
	 * @param int $pid
	 * @return void
	 */
	private function initTsForFrontend($pid) {
		$tsfe = tx_rnbase_util_Misc::prepareTSFE(
			array(
				'force' => TRUE,
				'pid'	=> $pid
			)
		);

		// add cobject for some plugins like tt_news
		$tsfe->cObj = tx_rnbase::makeInstance(
			tx_rnbase_util_Typo3Classes::getContentObjectRendererClass()
		);

		tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
		$rootlineByPid = tx_mksearch_service_indexer_core_Config::getRootLine($pid);

			// disable cache for be indexing!
		$tsfe->no_cache = TRUE;
		$tsfe->tmpl->start($rootlineByPid);
		$tsfe->rootLine = $rootlineByPid;
	}

	/**
	 * Die Indizierung wird von verschiedenen Pfaden aus aufgerufen.
	 * (CLI, BE Modul, Scheduler)
	 * Im FE ist der Pfad immer tslib
	 * worauf alle includeLibs Pfade ausgerichtet sind.
	 * Wir haben aber nicht den immer gleichen Pfad beim
	 * TV rendering im BE und daher müssen wir die relativen includeLibs Pfade
	 * um einen Prefix ergänzen damit das inkludieren fkt.
	 *
	 * @return void
	 */
	private function adjustIncludeLibsPathForBe() {
		$ts = &$GLOBALS['TSFE']->tmpl->setup['plugin.'];
		foreach ($ts as $pluginConfig) {
			if (
				!is_array($pluginConfig) ||
				!isset($pluginConfig['includeLibs']) ||
				// solche pfade werden später von TYPO3 korrekt aufgelöst
				strpos($pluginConfig['includeLibs'],'EXT:') !== false
			) {
				continue;
			}

				// vom fileCache werden Dateien zu erst abgerufen
			$filehash = md5($pluginConfig['includeLibs']);
			$GLOBALS['TSFE']->tmpl->fileCache[$filehash] =
				$this->getRelativePathPrefixFromCurrentExecutionDirToWebroot() . $pluginConfig['includeLibs'];
		}
	}

	/**
	 * get the relative path prefix from current execution dir to webroot
	 *
	 * @return string
	 */
	private function getRelativePathPrefixFromCurrentExecutionDirToWebroot() {
		// indexing via Scheduler in CLI
		if (defined('TYPO3_cliMode'))
		{
			// somewhere inside typo3 but not in webroot
			if(strlen(getcwd()) > strlen(PATH_site)) {
				$relativePathInsideTypo3 = str_replace(PATH_site, '', getcwd());
				// we need to find out how many levels we need to go up from here
				// to the webroot
				$pathParts = explode('/', $relativePathInsideTypo3);
				$relativePathPrefixFromExecutionDir = str_repeat('../', count($pathParts));
			}
			// somewhere outside typo3
			elseif(strlen(getcwd()) < strlen(PATH_site)) {
				$relativePathPrefixFromExecutionDir = str_replace(getcwd(), '', PATH_site);
			}
			// inside typo3 webroot
			else {
				$relativePathPrefixFromExecutionDir = '';
			}
		}
		// indexing via Scheduler in BE
		// script executed in /typo3
		elseif(strpos(PATH_thisScript,'typo3/mod.php') !== false)
		{
			$relativePathPrefixFromExecutionDir = '../';
		}
		// indexing via mksearch BE module
		// script executed in /typo3/sysext/cms/tslib
		else
		{
			$relativePathPrefixFromExecutionDir = '../../../../';
		}

		return $relativePathPrefixFromExecutionDir;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Templavoila.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Templavoila.php']);
}
