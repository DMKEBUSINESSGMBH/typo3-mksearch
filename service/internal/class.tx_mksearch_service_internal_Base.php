<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
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
 * Service for accessing models from database.
 */
class tx_mksearch_service_internal_Base extends \Sys25\RnBase\Typo3Wrapper\Service\AbstractService
{
    /**
     * Search class - set this to the search class name.
     *
     * @var string
     */
    protected $searchClass;

    /**
     * @return \Sys25\RnBase\Search\SearchBase
     */
    public function getSearcher()
    {
        return \Sys25\RnBase\Search\SearchBase::getInstance($this->searchClass);
    }

    /**
     * Search database.
     *
     * @param array $fields
     * @param array $options
     *
     * @return array[tx_mksearch_model_internal_Index]
     */
    public function search(array $fields, array $options)
    {
        return $this->getSearcher()->search($fields, $options);
    }

    /**
     * Check if a indexer is defined to index data into a given core.
     *
     * @param tx_mksearch_model_internal_Index $core
     * @param tx_mksearch_interface_Indexer    $indexer keys: extKey and contentType
     *
     * @return bool
     */
    public function isIndexerDefined($core, tx_mksearch_interface_Indexer $indexer)
    {
        $ret = false;
        $cfg = $core->getIndexerOptions();
        $indexerType = $indexer->getContentType();
        list($extKey, $contentType) = $indexer->getContentType();
        $indexerData = ['extKey' => $extKey, 'contentType' => $contentType];

        if (array_key_exists($indexerData['extKey'].'.', $cfg)) {
            if (array_key_exists($indexerData['contentType'].'.', $cfg[$indexerData['extKey'].'.'])) {
                $ret = true;
            }
        }

        return $ret;
    }

    /**
     * Search database for all configurated Indices.
     *
     * @param array $fields
     * @param array $options
     *
     * @return array[tx_mksearch_model_internal_Index]
     */
    public function findAll()
    {
        $fields = $options = [];
        // $options['debug'] = 1;
        $options['enablefieldsfe'] = 1;

        return $this->search($fields, $options);
    }

    /**
     * Search database for all configurated Indices.
     *
     * @param array $fields
     * @param array $options
     *
     * @return array[tx_mksearch_model_internal_Index]
     */
    public function getByPageId($pageId)
    {
        $alias = $this->getSearcher()->getBaseTableAlias();
        $fields = [];
        if (intval($pageId)) {
            $fields[$alias.'.pid'][OP_EQ_INT] = $pageId;
        }
        $options['enablefieldsfe'] = 1;

        return $this->search($fields, $options);
    }

    /**
     * Get model from database by its uid.
     *
     * @param array $fields
     * @param array $options
     *
     * @return tx_mksearch_model_*
     */
    public function get($uid)
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->getSearcher()->getWrapperClass(), $uid);
    }

    public function init(): bool
    {
        return true;
    }
}
