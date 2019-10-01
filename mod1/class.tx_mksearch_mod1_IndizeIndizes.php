<?php

/**
 * Mksearch backend module.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_IndizeIndizes extends tx_rnbase_mod_BaseModFunc
{
    /**
     * Return function id (used in page typoscript etc.).
     *
     * @return string
     */
    protected function getFuncId()
    {
        return 'indizeindizes';
    }

    public function getPid()
    {
        return $this->getModule()->getPid();
    }

    public function main()
    {
        $out = parent::main();
        $out = tx_mksearch_mod1_util_Template::parseBasics($out, $this);

        return $out;
    }

    /**
     * Return the actual html content.
     *
     * Actually, just the list view of the defined storage folder
     * is displayed within an iframe.
     *
     * @param string                    $template
     * @param tx_rnbase_configurations  $configurations
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param tx_rnbase_util_FormTool   $formTool
     *
     * @return string
     */
    protected function getContent($template, &$configurations, &$formatter, $formTool)
    {
        $status = array();
        if (!$GLOBALS['BE_USER']->isAdmin()) {
            return '';
        }

        $oIntIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();

        if (\Tx_Rnbase_Utility_T3General::_GP('updateIndex')) {
            $status[] = $this->handleReset($oIntIndexSrv, $configurations);
            $status[] = $this->handleClear($oIntIndexSrv, $configurations);
            $status[] = $this->handleTrigger($oIntIndexSrv, $configurations);
        }

        $markerArray = [];
        $bIsStatus = count($status = array_filter($status)) ? true : false;
        $markerArray['###ISSTATUS###'] = $bIsStatus ? 'block' : 'none';
        $markerArray['###STATUS###'] = $bIsStatus ? implode('<br />', $status) : '';
        $markerArray['###QUEUESIZE###'] = $oIntIndexSrv->countItemsInQueue();
        $indexItems = intval(\Tx_Rnbase_Utility_T3General::_GP('triggerIndexingQueueCount'));
        $markerArray['###INDEXITEMS###'] = $indexItems > 0 ? $indexItems : 100;

        $markerArray['###CORE_STATUS###'] = tx_mksearch_mod1_util_IndexStatusHandler::getInstance()->handleRequest(array('pid' => $this->getPid()));

        $this->showTables($template, $configurations, $formTool, $markerArray, $oIntIndexSrv);

        $out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray);

        return $out;
    }

    /**
     * Returns search form.
     *
     * @param string                             $template
     * @param tx_rnbase_configurations           $configurations
     * @param tx_rnbase_util_FormTool            $formTool
     * @param array                              $markerArray
     * @param tx_mksearch_service_internal_Index $oIntIndexSrv
     */
    protected function showTables($template, $configurations, $formTool, &$markerArray, $oIntIndexSrv)
    {
        $aIndexers = tx_mksearch_util_Config::getIndexers();
        $aIndices = $oIntIndexSrv->getByPageId($this->getPid());

        $aDefinedTables = [];

        foreach ($aIndices as $oIndex) {
            foreach ($aIndexers as $indexer) {
                if (!$oIntIndexSrv->isIndexerDefined($oIndex, $indexer)) {
                    continue;
                }

                list($extKey, $contentType) = $indexer->getContentType();
                $aTables = tx_mksearch_util_Config::getDatabaseTablesForIndexer($extKey, $contentType);
                $aRecord = [];
                $aRecord['name'] = array_shift($aTables);
                // Die Tabelle nur einmal darstellen, auch wenn Sie in mehreren Indexern definiert ist.
                if (!in_array($aRecord, $aDefinedTables)) {
                    $aDefinedTables[] = $aRecord;
                }
            }
        }

        $decor = tx_rnbase::makeInstance('tx_mksearch_mod1_decorator_Indizes', $this->getModule());
        $columns = [
            'name' => array('title' => 'label_table_name', 'decorator' => $decor),
            'queuecount' => array('title' => 'label_table_queuecount', 'decorator' => $decor),
            'clear' => array('title' => 'label_table_clear', 'decorator' => $decor),
            'resetG' => array('title' => 'label_table_resetG', 'decorator' => $decor),
            'reset' => array('title' => 'label_table_reset', 'decorator' => $decor),
        ];

        if (!empty($aDefinedTables)) {
            /* @var $tables Tx_Rnbase_Backend_Utility_Tables */
            $tables = tx_rnbase::makeInstance('Tx_Rnbase_Backend_Utility_Tables');
            list($tableData, $tableLayout) = $tables->prepareTable(
                $aDefinedTables,
                $columns,
                $this->getModule()->getFormTool(),
                array()
            );

            $content = $tables->buildTable($tableData, $tableLayout);
        } else {
            $content = '<p><strong>###LABEL_NO_INDEXERS_FOUND###</strong></p><br/>';
        }

        $markerArray['###TABLES_TABLE###'] = $content;
        $markerArray['###TABLES_COUNT###'] = count($aDefinedTables);
    }

    private function getPidList()
    {
        // wir holen uns eine liste von page ids
        // nur elemente dieser page id dürfen in der Queue landen
        $pidList = tx_mksearch_util_Indexer::getInstance()->getPidListFromSiteRootPage($this->getPid(), 999);

        if (empty($pidList)) {
            return $pidList;
        }

        return $pidList;
    }

    /**
     * Handle clear command from request. This means all record of selected tables have to be removed from
     * indexing queue.
     *
     * @param tx_mksearch_service_internal_Index $oIntIndexSrv
     * @param tx_rnbase_configurations           $configurations
     *
     * @return string
     */
    private function handleClear($oIntIndexSrv, &$configurations)
    {
        $aTables = \Tx_Rnbase_Utility_T3General::_GP('clearTables');
        if (!(is_array($aTables) && (!empty($aTables)))) {
            return '';
        }

        $status = '###LABEL_QUEUE_CLEARED###';
        foreach ($aTables as $sTable) {
            $oIntIndexSrv->clearIndexingQueueForTable($sTable);
        }

        $status .= '<ul><li>'.implode('</li><li/>', $aTables).'</li></ul>';

        return $status;
    }

    /**
     * Handle reset command from request.
     *
     * @param tx_mksearch_service_internal_Index $oIntIndexSrv
     * @param tx_rnbase_configurations           $configurations
     *
     * @return string
     */
    private function handleReset($oIntIndexSrv, &$configurations)
    {
        $aTables = [];
        // reset aller elemente im siteroot
        $aTables['pid'] = \Tx_Rnbase_Utility_T3General::_GP('resetTables');
        // global, alle elemente
        $aTables['global'] = \Tx_Rnbase_Utility_T3General::_GP('resetTablesG');
        if ((!is_array($aTables['pid']) && empty($aTables['pid']))
            && (!is_array($aTables['global']) && empty($aTables['global']))
        ) {
            return '';
        }

        $where = $options = [];
        // deleted aussschließen, wenn nicht gesetzt
        if (!\Tx_Rnbase_Utility_T3General::_GP('resetDeleted')) {
            $where[] = 'deleted = 0';
        }
        // hidden aussschließen, wenn nicht gesetzt
        if (!\Tx_Rnbase_Utility_T3General::_GP('resetHidden')) {
            $where[] = 'hidden = 0';
        }
        if (!empty($where)) {
            $options['where'] = implode(' AND ', $where);
        }
        $status = '###LABEL_QUEUE_RESETED###';
        foreach ($aTables as $key => $aResets) {
            if (is_array($aResets)) {
                foreach ($aResets as $sTable) {
                    $tableOptions = $options;

                    if ('pid' == $key) {
                        // wir holen uns eine liste von page ids
                        // nur elemente dieser page id dürfen in der Queue landen
                        $pidList = $this->getPidList();
                        if (!empty($pidList)) {
                            $tableOptions['where'] = 'pid IN ('.$pidList.')';
                        }
                    }
                    $oIntIndexSrv->resetIndexingQueueForTable($sTable, $tableOptions);
                }
            }
        }

        $status .= '<ul><li>'.
                    implode(
                        '</li><li/>',
                        array_unique(
                            array_merge(
                                is_array($aTables['pid']) ? $aTables['pid'] : [],
                                is_array($aTables['global']) ? $aTables['global'] : []
                            )
                        )
                    ).
                    '</li></ul>';

        return $status;
    }

    /**
     * Handle trigger command from request.
     *
     * @param tx_mksearch_service_internal_Index $oIntIndexSrv
     * @param tx_rnbase_configurations           $configurations
     *
     * @return string
     */
    private function handleTrigger($oIntIndexSrv, &$configurations)
    {
        $triggerIndexingQueue = \Tx_Rnbase_Utility_T3General::_GP('triggerIndexingQueue');
        if (!$triggerIndexingQueue) {
            return '';
        }

        // wir entfernen das time limit, da die indizierung ziemlich lange dauern kann.
        set_time_limit(0);

        $status = '';
        $indexItems = intval(\Tx_Rnbase_Utility_T3General::_GP('triggerIndexingQueueCount'));

        $options = [
            'limit' => $indexItems > 0 ? $indexItems : 100,
            'pid' => $this->getPid(),
        ];
        $runTime = microtime(true);
        $rows = $oIntIndexSrv->triggerQueueIndexing($options);
        $runTime = microtime(true) - $runTime;
        if ($rows) {
            $status .= '###LABEL_QUEUE_INDEXED###';
            // Komplette Anzahl ausgeben.
            $countAll = count(call_user_func_array('array_merge', array_values($rows)));
            $status .= sprintf(' %d ###LABEL_QUEUE_RUNTIME###: %01.2fs (%01.2f/sec)', $countAll, $runTime, $countAll / $runTime);
            // Anzahl einzelner Tabellen ausgeben
            foreach ($rows as $table => $row) {
                $rows[$table] = $table.': '.count($row);
            }
            $status .= '<ul><li>'.implode('</li><li/>', array_values($rows)).'</li></ul>';
        } else {
            $status .= '###LABEL_QUEUE_INDEXED_EMPTY###';
        }

        return $status;
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_IndizeIndizes.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_IndizeIndizes.php'];
}
