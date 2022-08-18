<?php

/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_action_ShowHit extends \Sys25\RnBase\Frontend\Controller\AbstractAction
{
    /**
     * @var tx_mksearch_model_internal_Index
     */
    private $index = false;

    protected function handleRequest(\Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        $item = $this->findItem();
        $request->getViewContext()->offsetSet('item', $item);

        return null;
    }

    /**
     * @return tx_mksearch_interface_SearchHit
     *
     * @throws InvalidArgumentException
     * @throws Ambigous                 <Exception, LogicException, LogicException, tx_mksearch_service_engine_SolrException>
     */
    protected function findItem()
    {
        $item = null;
        $configurations = $this->getConfigurations();
        $confId = $this->getConfId();

        $extKey = $configurations->get($confId.'extkey');
        $contentType = $configurations->get($confId.'contenttype');
        $uid = $configurations->get($confId.'uid');
        if (!$uid) {
            $uidParamName = $configurations->get($confId.'uidParamName');
            $uidParamName = $uidParamName ? $uidParamName : 'item';
            $uid = $configurations->getParameters()->getInt($uidParamName);
        }

        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'action_showhit_finditem_pre',
            [
                'ext_key' => &$extKey,
                'content_type' => &$contentType,
                'uid' => &$uid,
            ],
            $this
        );

        $item = $this->searchByContentUid($uid, $extKey, $contentType);

        \Sys25\RnBase\Utility\Misc::callHook(
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
            throw new LogicException('No hit found for "'.$extKey.':'.$contentType.':'.$uid.'" in index "'.$this->getIndex()->getUid().'".', 1377774172);
        }
        if (!$item instanceof tx_mksearch_interface_SearchHit) {
            throw new LogicException('The hit has to be an object instance of "tx_mksearch_interface_SearchHit",'.'"'.(is_object($item) ? get_class($item) : gettype($item)).'" given.', 1377774178);
        }

        return $item;
    }

    /**
     * @param int    $uid
     * @param string $extKey
     * @param string $contentType
     *
     * @return
     *
     * @throws Ambigous <Exception, InvalidArgumentException, tx_mksearch_service_engine_SolrException>
     */
    protected function searchByContentUid($uid, $extKey, $contentType)
    {
        $item = null;

        if (!($extKey && $contentType && $uid)) {
            throw new InvalidArgumentException('Missing Parameters. extkey, contenttype and item are required.', 1370429706);
        }

        try {
            // in unserem fall sollte es der solr service sein!
            /* @var $searchEngine tx_mksearch_service_engine_Solr */
            $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($this->getIndex());
            $searchEngine->openIndex($this->getIndex());
            $item = $searchEngine->getByContentUid($uid, $extKey, $contentType);
            $searchEngine->closeIndex();
        } catch (Exception $e) {
            $lastUrl = $e instanceof tx_mksearch_service_engine_SolrException ? $e->getLastUrl() : '';
            // Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
            // also machen wir das manuell
            if ($addr = \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
                \Sys25\RnBase\Utility\Misc::sendErrorMail($addr, 'tx_mksearch_action_SearchSolr_searchSolr', $e);
            }
            \Sys25\RnBase\Utility\Logger::fatal(
                'Solr search failed with Exception!',
                'mksearch',
                [
                    'Exception' => $e->getMessage(),
                    'URL' => $lastUrl,
                ]
            );
            $configurations = $this->getConfigurations();
            if ($configurations->getBool($this->getConfId().'throwSolrSearchException')) {
                throw $e;
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
    protected function getIndex()
    {
        if (false === $this->index) {
            $indexUid = $this->getConfigurations()->get($this->getConfId().'usedIndex');
            // let's see if we got a index to use via parameters
            if (empty($indexUid)) {
                $indexUid = $this->getConfigurations()->getParameters()->get('usedIndex');
            }

            $index = tx_mksearch_util_ServiceRegistry::getIntIndexService()->get($indexUid);

            if (!$index->isValid()) {
                throw new Exception('Configured search index not found!');
            }

            $this->index = $index;
        }

        return $this->index;
    }

    public function getTemplateName()
    {
        return 'showhit';
    }

    public function getViewClassName()
    {
        return 'tx_mksearch_view_ShowHit';
    }
}
