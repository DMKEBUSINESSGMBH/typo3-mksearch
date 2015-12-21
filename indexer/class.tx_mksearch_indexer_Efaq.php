<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_indexer
 *  @author Hannes Bochmann
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

tx_rnbase::load('tx_mksearch_interface_Indexer');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * Indexer service for irfaq.question called by the "mksearch" extension.
 *
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_indexer
 */
class tx_mksearch_indexer_Efaq implements tx_mksearch_interface_Indexer {

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
	public static function getContentType() {
		return array('efaq', 'faq');
	}

	/**
	 * Prepare a searchable document from a source record.
	 *
	 * @param tx_mksearch_interface_IndexerDocument		$indexDoc	Indexer document to be "filled", instantiated based on self::getContentType()
	 * @return null|tx_mksearch_interface_IndexerDocument or null if nothing should be indexed.
	 */
	public function prepareSearchData($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {

		$indexDoc->setUid($sourceRecord['sys_language_uid'] ? $sourceRecord['l18n_parent'] : $sourceRecord['uid']);

		$title = empty($sourceRecord['kat'])
			? '' : (
				isset($options['titlePrefix']) ? $options['titlePrefix'].' ' : ''
			) . trim($sourceRecord['kat']);
		$content = trim(trim($sourceRecord['question']) . PHP_EOL . trim($sourceRecord['answer']));

		// indizieren?
		if ($sourceRecord['deleted'] || $sourceRecord['hidden'] || (empty($title) && empty($content))) {
			$indexDoc->setDeleted(true);
			return $indexDoc;
		}

		$indexDoc->setContent($content);
		$indexDoc->setAbstract(empty($content) ? $title : $content, $indexDoc->getMaxAbstractLength());
		$indexDoc->setTitle($title);
		$indexDoc->setTimestamp($sourceRecord['tstamp']);
		$indexDoc->addField('pid', $sourceRecord['pid'], 'keyword');


		foreach ($this->getFieldMapping() as $recordKey => $indexKey) {
			if(empty($sourceRecord[$recordKey])) {
				continue;
			}
			$indexDoc->addField(
				$indexKey,
				$options['keepHtml'] ? $sourceRecord[$recordKey]
					: tx_mksearch_util_Misc::html2plain($sourceRecord[$recordKey])
			);
		}

		//done
		return $indexDoc;
	}

	protected function getFieldMapping() {
		static $mapping = false;
		if (!is_array($mapping)) {
			$mapping = array(
				'question' => 'question_s',
				'answer' => 'answer_s',
				'author' => 'author_s',
				'url' => 'url_s',
				'kat' => 'kat_s',
				'subkat' => 'subkat_s',
			);
		}
		return $mapping;
	}

	/**
	 * Return the default Typoscript configuration for an indexer.
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
		return <<<CONFIG
keepHtml = 0
titlePrefix = FAQ:
CONFIG;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Efaq.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Efaq.php']);
}