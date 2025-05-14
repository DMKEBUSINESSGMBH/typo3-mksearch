<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_action_ShowHit extends Sys25\RnBase\Frontend\Controller\AbstractAction
{
    /**
     * @var tx_mksearch_model_internal_Index
     */
    private bool $index = false;

    protected function handleRequest(Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        $item = $this->findItem($request);
        $request->getViewContext()->offsetSet('item', $item);

        return null;
    }

    /**
     * @return tx_mksearch_interface_SearchHit
     *
     * @throws InvalidArgumentException
     * @throws Exception|LogicException|LogicException|tx_mksearch_service_engine_SolrException
     */
    protected function findItem(Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        $item = null;
        $configurations = $request->getConfigurations();
        $confId = $this->getConfId();

        $extKey = $configurations->get($confId.'extkey');
        $contentType = $configurations->get($confId.'contenttype');
        $uid = $configurations->get($confId.'uid');
        if (!$uid) {
            $uidParamName = $configurations->get($confId.'uidParamName');
            $uidParamName = $uidParamName ?: 'item';
            $uid = $configurations->getParameters()->getInt($uidParamName);
        }

        Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'action_showhit_finditem_pre',
            [
                'ext_key' => &$extKey,
                'content_type' => &$contentType,
                'uid' => &$uid,
            ],
            $this
        );

        $item = $this->searchByContentUid($uid, $extKey, $contentType, $request);

        Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'action_showhit_finditem_post',
            [
                'ext_key' => &$extKey,
                'content_type' => &$contentType,
                'uid' => &$uid,
                'item' => &$item,
            ],
            $this
        );

        if (is_null($item)) {
            throw new LogicException('No hit found for "'.$extKey.':'.$contentType.':'.$uid.'" in index "'.$this->getIndex($request)->getUid().'".', 1377774172);
        }

        if (!$item instanceof tx_mksearch_interface_SearchHit) {
            throw new LogicException('The hit has to be an object instance of "tx_mksearch_interface_SearchHit","'.get_debug_type($item).'" given.', 1377774178);
        }

        return $item;
    }

    /**
     * @param int    $uid
     * @param string $extKey
     * @param string $contentType
     *
     * @throws Exception|InvalidArgumentException|tx_mksearch_service_engine_SolrException
     */
    protected function searchByContentUid($uid, $extKey, $contentType, Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        $item = null;

        if (!($extKey && $contentType && $uid)) {
            throw new InvalidArgumentException('Missing Parameters. extkey, contenttype and item are required.', 1370429706);
        }

        try {
            // in unserem fall sollte es der solr service sein!
            /* @var $searchEngine tx_mksearch_service_engine_Solr */
            $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($this->getIndex($request));
            $searchEngine->openIndex($this->getIndex($request));
            $item = $searchEngine->getByContentUid($uid, $extKey, $contentType);
            $searchEngine->closeIndex();
        } catch (Exception $exception) {
            $lastUrl = $exception instanceof tx_mksearch_service_engine_SolrException ? $exception->getLastUrl() : '';
            // Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
            // also machen wir das manuell
            if ($addr = Sys25\RnBase\Configuration\Processor::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
                Sys25\RnBase\Utility\Misc::sendErrorMail($addr, 'tx_mksearch_action_SearchSolr_searchSolr', $exception);
            }

            Sys25\RnBase\Utility\Logger::fatal(
                'Solr search failed with Exception!',
                'mksearch',
                [
                    'Exception' => $exception->getMessage(),
                    'URL' => $lastUrl,
                ]
            );
            $configurations = $request->getConfigurations();
            if ($configurations->getBool($this->getConfId().'throwSolrSearchException')) {
                throw $exception;
            }
        }

        return $item;
    }

    /**
     * returns the dataset for the current used index.
     *
     * @return tx_mksearch_model_internal_Index
     *
     * @throws Exception
     */
    protected function getIndex(Sys25\RnBase\Frontend\Request\RequestInterface $request): bool
    {
        if (false === $this->index) {
            $indexUid = $request->getConfigurations()->get($this->getConfId().'usedIndex');
            // let's see if we got a index to use via parameters
            if (empty($indexUid)) {
                $indexUid = $request->getConfigurations()->getParameters()->get('usedIndex');
            }

            $index = tx_mksearch_util_ServiceRegistry::getIntIndexService()->get($indexUid);

            if (!$index->isValid()) {
                throw new Exception('Configured search index not found!');
            }

            $this->index = $index;
        }

        return $this->index;
    }

    protected function getTemplateName()
    {
        return 'showhit';
    }

    protected function getViewClassName()
    {
        return 'tx_mksearch_view_ShowHit';
    }
}
