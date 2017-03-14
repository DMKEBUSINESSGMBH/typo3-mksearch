<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2017 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

tx_rnbase::load('tx_mksearch_indexer_Base');

/**
 * Indexer service for tx_news.news called by the "mksearch" extension.
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_indexer
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_indexer_TxNewsNews
	extends tx_mksearch_indexer_Base {

	/**
	 * Return content type identification.
	 * This identification is part of the indexed data
	 * and is used on later searches to identify the search results.
	 * You're completely free in the range of values, but take care
	 * as you at the same time are responsible for
	 * uniqueness (i.e. no overlapping with other content types) and
	 * consistency (i.e. recognition) on indexing and searching data.
	 *
	 * @return array('extKey' => [extension key], 'name' => [key of content type]
	 */
	public static function getContentType()
	{
		return array('tx_news', 'news');
	}

	/**
	 * Do the actual indexing for the given model
	 *
	 * @param tx_rnbase_IModel $oModel
	 * @param string $tableName
	 * @param array $rawData
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 *
	 * @return tx_mksearch_interface_IndexerDocument|null
	 */
	public function indexData(
		tx_rnbase_IModel $oModel,
		$tableName,
		$rawData,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$options
	) {
		$abort = false;

		// Hook to append indexer
		tx_rnbase_util_Misc::callHook(
			'mksearch',
			'indexer_TxNews_prepareDataBeforeAddFields',
			array(
				'rawData' => &$rawData,
				'options' => $options,
				'indexDoc' => &$indexDoc,
				'abort' => &$abort,
			),
			$this
		);

		// At least one of the news' categories was found on black list
		if ($abort) {
			tx_rnbase::load('tx_rnbase_util_Logger');
			tx_rnbase_util_Logger::info(
				'News wurde nicht indiziert, weil das Signal von einem Hook gegeben wurde.',
				'mksearch'
			);
			if ($options['deleteOnAbort']) {
				$indexDoc->setDeleted(TRUE);
				return $indexDoc;
			} else {
				return null;
			}
		}

		$indexDoc->setTitle($rawData['title']);
		$indexDoc->addField('pid', $rawData['pid']);
		$indexDoc->setTimestamp($rawData['tstamp']);

		$content = $rawData['bodytext'];
		$abstract = empty($rawData['teaser']) ? $content : $rawData['teaser'];

		// html entfernen
		if (empty($options['keepHtml'])) {
			$content = tx_mksearch_util_Misc::html2plain($content);
			$abstract = tx_mksearch_util_Misc::html2plain($abstract);
		}

		// wir indizieren nicht, wenn kein content für die news da ist.
		if (empty($content)) {
			$indexDoc->setDeleted(TRUE);
			return $indexDoc;
		}

		$indexDoc->setContent($content);
		$indexDoc->setAbstract($abstract, $indexDoc->getMaxAbstractLength());

		return $indexDoc;
	}

	/**
	 * Return the default Typoscript configuration for this indexer.
	 *
	 * Overwrite this method to return the indexer's TS config.
	 * Note that this config is not used for actual indexing
	 * but only serves as assistance when actually configuring an indexer!
	 * Hence all possible configuration options should be set or
	 * at least be mentioned to provide an easy-to-access inline documentation!
	 *
	 * @return string
	 */
	public function getDefaultTSConfig() {
		return <<<CONF
indexSiteRootPage = 0

# Should the HTML Markup in indexed fields, the abstract and the content be kept?
# by default every HTML Markup is removed
# keepHtml = 1

### delete from or abort indexing for the record if isIndexableRecord or no record?
deleteIfNotIndexable = 0
### delete from indexing or abort indexing for the record?
deleteOnAbort = 0

### should the Root Page of the current records page be indexed?
# indexSiteRootPage = 0

# you should always configure the root pageTree for this indexer in the includes. mostly the domain
# include.pageTrees {
# 	0 = pid-of-domain
# }

# should a special workspace be indexed?
# default is the live workspace (ID = 0)
# comma separated list of workspace IDs
#workspaceIds = 1,2,3
CONF;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TxNewsNews.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TxNewsNews.php']);
}
