<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2015 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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

/**
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_indexer_A21Glossary extends tx_mksearch_indexer_Base
{
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
        return array('a21glossary', 'main');
    }

    /**
     * Do the actual indexing for the given model.
     *
     * @param tx_rnbase_IModel                      $model
     * @param string                                $tableName
     * @param array                                 $rawData
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return tx_mksearch_interface_IndexerDocument|null
     */
    protected function indexData(
        tx_rnbase_IModel $model,
        $tableName,
        $rawData,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        // set title, content and abstract as default
        // can be overwritten by the mapping
        $indexDoc->setTitle(
            $model->getLongversion()
        );
        $content = tx_mksearch_util_Misc::html2plain(
            $model->getDescription(),
            array('lineendings' => true)
        );
        $indexDoc->setContent($content);
        $indexDoc->setAbstract(
            $content,
            $indexDoc->getMaxAbstractLength()
        );

        // index everything about the question
        $this->indexModelByMapping(
            $model,
            array(
                'short' => 'short_s',
                'shortcut' => 'shortcut_s',
                'longversion' => 'longversion_s',
                'shorttype' => 'shorttype_s',
                'description' => 'description_t',
            ),
            $indexDoc
        );

        //done
        return $indexDoc;
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
    public function getDefaultTSConfig()
    {
        return <<<CONFIG
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

### if one page in the rootline of an element has the no_search flag the element won't be indexed
respectNoSearchFlagInRootline = 1

# you should always configure the root pageTree for this indexer in the includes. mostly the domain
# include.pageTrees {
#   0 = \$pid-of-domain
# }


# should a special workspace be indexed?
# default is the live workspace (ID = 0)
# comma separated list of workspace IDs
#workspaceIds = 1,2,3
CONFIG;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php'];
}
