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
 * Just a wrapper for the different tt_content indexers.
 * it's a facade.
 * @author Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
 */
class tx_mksearch_indexer_TtContent implements tx_mksearch_interface_Indexer {

	/**
	 * the appropriate indexer depending on templavoila
	 * @var tx_mksearch_indexer_Base
	 */
	protected $oIndexer;

	/**
	 * load the appropriate indexer depending on templavoila
	 */
	public function __construct() {
		if(t3lib_extMgm::isLoaded('templavoila')){
			$this->oIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_ttcontent_Templavoila');
		}else{
			$this->oIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_ttcontent_Normal');
		}
	}


	/**
	* Prepare a searchable document from a source record.
	*
	* @param tx_mksearch_interface_IndexerDocument		$indexDoc	Indexer document to be "filled", instantiated based on self::getContentType()
	* @return null|tx_mksearch_interface_IndexerDocument or null if nothing should be indexed.
	*/
	public function prepareSearchData($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options){
		return $this->oIndexer->prepareSearchData($tableName, $sourceRecord, $indexDoc, $options);
	}

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
	* Return the default Typoscript configuration for this indexer
	*
	* This config is not used for actual indexing but serves only as assistance
	* when actually configuring an indexer via Typo3 backend by creating
	* a new indexer configuration record!
	* Hence all possible configuration options should be set or at least
	* be mentioned (i.e. commented out) to provide an easy-to-access inline documentation!
	*
	* @return string
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
includeCTypes = text,textpic,bullets
#ignoreCTypes {
#	0 = search
#	1 = mailform
#	2 = login
#	3 = list
#    4 = powermail_pi1
#    5 = templavoila_pi1
#    6 = html
#}

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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtContent.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtContent.php']);
}