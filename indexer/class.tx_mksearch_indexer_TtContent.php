<?php
/**
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * benötigte Klassen einbinden.
 */

/**
 * Just a wrapper for the different tt_content indexers.
 * it's a facade.
 *
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
 */
class tx_mksearch_indexer_TtContent implements tx_mksearch_interface_Indexer
{
    /**
     * the appropriate indexer depending on templavoila.
     *
     * @var tx_mksearch_indexer_Base
     */
    protected $actualIndexer;

    /**
     * load the appropriate indexer depending on templavoila or gridelements.
     */
    public function __construct()
    {
        if (tx_rnbase_util_Extensions::isLoaded('gridelements')) {
            $this->actualIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_ttcontent_Gridelements');
        } elseif (tx_rnbase_util_Extensions::isLoaded('templavoila')) {
            $this->actualIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_ttcontent_Templavoila');
        } else {
            $this->actualIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_ttcontent_Normal');
        }
    }

    /**
     * Prepare a searchable document from a source record.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc Indexer document to be "filled", instantiated based on self::getContentType()
     *
     * @return tx_mksearch_interface_IndexerDocument|null or null if nothing should be indexed
     */
    public function prepareSearchData($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options)
    {
        return $this->actualIndexer->prepareSearchData($tableName, $sourceRecord, $indexDoc, $options);
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
     * @return array
     */
    public static function getContentType()
    {
        return ['core', 'tt_content'];
    }

    /**
     * Return the default Typoscript configuration for this indexer.
     *
     * This config is not used for actual indexing but serves only as assistance
     * when actually configuring an indexer via Typo3 backend by creating
     *  a new indexer configuration record!
     * Hence all possible configuration options should be set or at least
     * be mentioned (i.e. commented out) to provide an easy-to-access inline documentation!
     *
     * @return string
     */
    public function getDefaultTSConfig()
    {
        return <<<CONF
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields{
#   my_fixed_field_singlevalue = first
#   my_fixed_field_multivalue{
#      0 = first
#      1 = second
#   }
# }

### if one page in the rootline of an element has the no_search flag the element won't be indexed
respectNoSearchFlagInRootline = 1

addPageMetaData = 0
addPageMetaData.separator = ,

### should the data of the page where the tt_content element resides be indexed?
### if so than you need to provide the mapping in the pageDataFieldMapping option like it
### is needed for the page indexer
indexPageData = 1
# "page_" is automatically prefixed. so the resulting fields will be page_title_s, page_nav_title_s...
pageDataFieldMapping {
    title = title_s
    nav_title = nav_title_s
#   my_record_field = my_solr_field
}

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
  gridelements_pi1.indexedFields {
  }
  templavoila_pi1.indexedFields {
    0 = tx_templavoila_flex
  }
}

# cTypes of content elements to be in-/excluded from indexing.
# Obviously, the respective "indexedFields" option is ignored in this case.
# templavoila_pi1 should in most cases be too, indexed, but requires some more configuration

includeCTypes = text,textpic,textmedia,bullets,image,table,gridelements_pi1,templavoila_pi1

#ignoreCTypes {
#  0 = search
#  1 = mailform
#  2 = login
#  3 = list
#  4 = powermail_pi1
#  5 = templavoila_pi1
#  6 = html
#}

# \$sys_language_uid of the desired language
# lang = 1

### delete from or abort indexing for the record if isIndexableRecord or no record?
deleteIfNotIndexable = 0

### if set, the field "Include in Search" of current items page is checked.
### If "Include in Search" is set to "Disable", the record will not be indexed
respectIncludeInSearchDisable = 1

### disable the fallback to page title, if the content title is empty
leaveHeaderEmpty = 0

# Note: you should always configure the root pageTree for this indexer in the includes. mostly the domain
# White lists: Explicitely include items in indexing by various conditions.
# Note that defining a white list deactivates implicite indexing of ALL pages,
# i.e. only white-listed pages are defined yet!
# May also be combined with option "exclude"
include {
  # Include several content elements pages in indexing:
#  elements {
    # Include tt_content #17
#    0 = 17
    # Include tt_content #26
#    1 = 26
#  }
# Include several pages in indexing:
#  # Include page #18 and #27
#  pages = 18,27
# Include complete page trees (i. e. pages with all their children) in indexing:
#  pageTrees {
#    # Include page tree with root page #19
#    0 = 19
#    # Include page  tree with root page #28
#    1 = 28
#  }
# Only Include specific Content Columns
#  # Include colPos value
#  columns = 0,1,2,3,-1
    columns = 0
}
# Black lists: Exclude pages from indexing by various conditions.
# May also be combined with option "include", while "exclude" option
# takes precedence over "include" option.
exclude {
  # Exclude several pages from indexing. @see respective include option
#  pages ...
  # Exclude complete page trees (i. e. pages with all their children) from indexing.
  # @see respective include option
#  pageTrees ...
}

# should a special workspace be indexed?
# default is the live workspace (ID = 0)
# comma separated list of workspace IDs
#workspaceIds = 1,2,3

# cTypes of content elements to be included in rendering from gridelements
includeCTypesInGridelementRendering = text,textpic,textmedia,shortcut,image,table,gridelements_pi1

# the dok types which are supported. If a tt_content element is on a page with another doktype
# it wont be indexed. If nothing is configured by default only standard pages are considered.
#supportedDokTypes = 1,2,3

CONF;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtContent.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtContent.php'];
}
