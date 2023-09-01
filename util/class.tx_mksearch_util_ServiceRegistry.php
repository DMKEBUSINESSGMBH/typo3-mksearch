<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 das Medienkombinat
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
 * Access a service instance.
 */
class tx_mksearch_util_ServiceRegistry
{
    /**
     * Return best search engine service which implements tx_mksearch_interface_SearchEngine.
     *
     * @param tx_mksearch_model_internal_Index $index
     *
     * @return tx_mksearch_interface_SearchEngine
     */
    public static function getSearchEngine($index)
    {
        $type = $index->getEngineType();
        if (!$type) {
            throw new Exception('No engine type configured in search index. Check your index configuration!', 100);
        }
        $srv = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('mksearch_engine', $type);
        if (!is_object($srv)) {
            throw new Exception('Service mksearch_engine not found for type: '.$type);
        }
        if (!$srv instanceof tx_mksearch_interface_SearchEngine) {
            throw new Exception('Service "'.$srv->info['className'].'" does not implement tx_mksearch_interface_SearchEngine!');
        }

        $srv->setIndexModel($index);

        return $srv;
    }

    /**
     * Return best search engine service which implements tx_mksearch_interface_SearchEngine.
     *
     * @return tx_mksearch_interface_SearchEngine
     *
     * @deprecated request search engine via configured index
     */
    public static function getSearchEngineService()
    {
        $srv = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('mksearch_engine', 'zend_lucene');
        if (!is_object($srv)) {
            throw new Exception('Service mksearch_engine not found!');
        }
        // else
        if (!$srv instanceof tx_mksearch_interface_SearchEngine) {
            throw new Exception('Service "'.$srv->info['className'].'" does not implement tx_mksearch_interface_SearchEngine!');
        }

        // else
        return $srv;
    }

    /**
     * Return best search engine service which implements tx_mksearch_interface_Indexer.
     *
     * This method does NOT use \Sys25\RnBase\Utility\Misc::getService intentionally,
     * as we do not want to "mayday" in case of an error!
     *
     * @param string $extKey
     * @param string $contentType
     *
     * @return tx_mksearch_interface_Indexer
     */
    public static function getIndexerService($extKey, $contentType)
    {
        $subType = $extKey.'.'.$contentType;
        $srv = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('mksearch_indexer', $subType);
        if (!is_object($srv)) {
            throw new Exception('Service "mksearch_indexer.'.$subType.'" not found!');
        }
        // else
        if (!$srv instanceof tx_mksearch_interface_Indexer) {
            throw new Exception('Service "'.$srv->info['className'].'" does not implement tx_mksearch_interface_Indexer!');
        }

        // else
        return $srv;
    }

    /**
     * Return internal index service.
     *
     * @return tx_mksearch_service_internal_Index
     */
    public static function getIntIndexService()
    {
        return \Sys25\RnBase\Utility\Misc::getService('mksearch', 'int_index');
    }

    /**
     * Return internal indexer configuration composite service.
     *
     * @return tx_mksearch_service_internal_Composite
     */
    public static function getIntCompositeService()
    {
        return \Sys25\RnBase\Utility\Misc::getService('mksearch', 'int_composite');
    }

    /**
     * Return internal indexer configuration service.
     *
     * @return tx_mksearch_service_internal_Config
     */
    public static function getIntConfigService()
    {
        return \Sys25\RnBase\Utility\Misc::getService('mksearch', 'int_config');
    }

    /**
     * Return internal indexer configuration service.
     *
     * @return tx_mksearch_service_internal_Keyword
     */
    public static function getKeywordService()
    {
        return \Sys25\RnBase\Utility\Misc::getService('mksearch', 'keyword');
    }

    /**
     * Return tx_irfaq expert service
     * as the extension offers none.
     *
     * @return tx_mksearch_service_irfaq_Expert
     */
    public static function getIrfaqExpertService()
    {
        return \Sys25\RnBase\Utility\Misc::getService('mksearch', 'irfaq_expert');
    }

    /**
     * Return tx_irfaq category service
     * as the extension offers none.
     *
     * @return tx_mksearch_service_irfaq_Category
     */
    public static function getIrfaqCategoryService()
    {
        return \Sys25\RnBase\Utility\Misc::getService('mksearch', 'irfaq_category');
    }

    /**
     * Return tx_irfaq category service
     * as the extension offers none.
     *
     * @return tx_mksearch_service_irfaq_Question
     */
    public static function getIrfaqQuestionService()
    {
        return \Sys25\RnBase\Utility\Misc::getService('mksearch', 'irfaq_question');
    }
}
