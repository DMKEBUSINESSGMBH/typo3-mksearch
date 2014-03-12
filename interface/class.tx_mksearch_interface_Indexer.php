<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Lars Heber <lars.heber@das-medienkombinat.de>
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
***************************************************************/

/**
 * Interface for indexer in the "mksearch" extension.
 *
 * @author	René Nitzsche <nitzsche@das-medienkombinat.de>, Lars Heber
 * @package	TYPO3
 * @subpackage	tx_mksearch
 */
interface tx_mksearch_interface_Indexer {

	/**
	 * Prepare a searchable document from a source record.
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 *        Indexer document to be "filled",
	 *        instantiated based on self::getContentType()
	 * @param array $options
	 * @return tx_mksearch_interface_IndexerDocument|null
	 *         return null if nothing should be indexed!
	 */
	public function prepareSearchData(
		$tableName, $sourceRecord,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$options
	);


	/**
	 * Return content type identification
	 *
	 * This identification is part of the indexed data
	 * and is used on later searches to identify the search results.
	 * You're completely free in the range of values, but take care
	 * as you at the same time are responsible for
	 * uniqueness (i.e. no overlapping with other content types) and
	 * consistency (i.e. recognition) on indexing and searching data.
	 *
	 * @return array([extension key], [key of content type])
	 */
	public static function getContentType();

	/**
	 * Return the default Typoscript configuration for this indexer
	 *
	 * This config is not used for actual indexing but serves only as assistance
	 * when actually configuring an indexer via Typo3 backend by creating
	 * a new indexer configuration record!
	 * Hence all possible configuration options should be set or at least
	 * be mentioned (i.e. commented out) to provide an
	 * easy-to-access inline documentation!
	 *
	 * @return string
	 */
	public function getDefaultTSConfig();

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_Indexer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_Indexer.php']);
}