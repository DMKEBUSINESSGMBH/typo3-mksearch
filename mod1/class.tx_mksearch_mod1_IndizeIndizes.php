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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Mksearch backend module.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_IndizeIndizes extends Sys25\RnBase\Backend\Module\BaseModFunc
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

    public function main(?ServerRequestInterface $request = null)
    {
        return tx_mksearch_mod1_util_Misc::getSubModuleContent(
            parent::main($request),
            $this,
            'web_MksearchM1_indices'
        );
    }

    /**
     * Return the actual html content.
     *
     * Actually, just the list view of the defined storage folder
     * is displayed within an iframe.
     *
     * @param string                                  $template
     * @param Sys25\RnBase\Configuration\Processor    $configurations
     * @param Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param Sys25\RnBase\Backend\Form\ToolBox       $formTool
     *
     * @return string
     */
    protected function getContent($template, &$configurations, &$formatter, $formTool)
    {
        $status = [];
        if (!$GLOBALS['BE_USER']->isAdmin()) {
            return '';
        }

        $oIntIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();

        if ($GLOBALS['TYPO3_REQUEST']->getParsedBody()['updateIndex'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['updateIndex'] ?? null) {
            $status[] = $this->handleResetBeingIndexed($oIntIndexSrv);
            $status[] = $this->handleReset($oIntIndexSrv);
            $status[] = $this->handleClear($oIntIndexSrv);
            $status[] = $this->handleTrigger($oIntIndexSrv);
        }

        $markerArray = [];
        $bIsStatus = (bool) count($status = array_filter($status));
        $markerArray['###ISSTATUS###'] = $bIsStatus ? 'block' : 'none';
        $markerArray['###STATUS###'] = $bIsStatus ? implode('<br />', $status) : '';
        $markerArray['###QUEUESIZE###'] = $oIntIndexSrv->countItemsInQueue();
        $markerArray['###QUEUE_SIZE_BEING_INDEXED###'] = $oIntIndexSrv->countItemsInQueueBeingIndexed();
        $beingIndexedOlderThan = intval($GLOBALS['TYPO3_REQUEST']->getParsedBody()['beingIndexedOlderThan'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['beingIndexedOlderThan'] ?? null);
        $markerArray['###BEINGINDEXEDOLDERTHAN###'] = $beingIndexedOlderThan > 0 ? $beingIndexedOlderThan : 120;
        $indexItems = intval($GLOBALS['TYPO3_REQUEST']->getParsedBody()['triggerIndexingQueueCount'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['triggerIndexingQueueCount'] ?? null);
        $markerArray['###INDEXITEMS###'] = $indexItems > 0 ? $indexItems : 100;

        $markerArray['###CORE_STATUS###'] = tx_mksearch_mod1_util_IndexStatusHandler::getInstance()->handleRequest(['pid' => $this->getPid()]);

        $this->showTables($template, $configurations, $formTool, $markerArray, $oIntIndexSrv);

        return Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($template, $markerArray);
    }

    /**
     * Returns search form.
     *
     * @param string                               $template
     * @param Sys25\RnBase\Configuration\Processor $configurations
     * @param Sys25\RnBase\Backend\Form\ToolBox    $formTool
     * @param tx_mksearch_service_internal_Index   $oIntIndexSrv
     */
    protected function showTables($template, $configurations, $formTool, array &$markerArray, $oIntIndexSrv)
    {
        $aIndexers = tx_mksearch_util_Config::getIndexers();
        $aIndices = $oIntIndexSrv->getByPageId($this->getPid());

        $aDefinedTables = [];

        foreach ($aIndices as $oIndex) {
            foreach ($aIndexers as $indexer) {
                if (!$oIntIndexSrv->isIndexerDefined($oIndex, $indexer)) {
                    continue;
                }

                [$extKey, $contentType] = $indexer->getContentType();
                $aTables = tx_mksearch_util_Config::getDatabaseTablesForIndexer($extKey, $contentType);
                $aRecord = [];
                $aRecord['name'] = array_shift($aTables);
                // Die Tabelle nur einmal darstellen, auch wenn Sie in mehreren Indexern definiert ist.
                if (!in_array($aRecord, $aDefinedTables)) {
                    $aDefinedTables[] = $aRecord;
                }
            }
        }

        $decor = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_Indizes', $this->getModule());
        $columns = [
            'name' => ['title' => 'label_table_name', 'decorator' => $decor],
            'queuecount' => ['title' => 'label_table_queuecount', 'decorator' => $decor],
            'clear' => ['title' => 'label_table_clear', 'decorator' => $decor],
            'resetG' => ['title' => 'label_table_resetG', 'decorator' => $decor],
            'reset' => ['title' => 'label_table_reset', 'decorator' => $decor],
        ];

        if ([] !== $aDefinedTables) {
            /* @var $tables \Sys25\RnBase\Backend\Utility\Tables */
            $tables = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Backend\Utility\Tables::class);
            [$tableData, $tableLayout] = $tables->prepareTable(
                $aDefinedTables,
                $columns,
                $this->getModule()->getFormTool(),
                []
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

        return $pidList;
    }

    /**
     * Handle clear command from request. This means all record of selected tables have to be removed from
     * indexing queue.
     *
     * @param tx_mksearch_service_internal_Index $oIntIndexSrv
     */
    private function handleClear($oIntIndexSrv): string
    {
        $aTables = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['clearTables'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['clearTables'] ?? null;
        if (!(is_array($aTables) && ([] !== $aTables))) {
            return '';
        }

        $status = '###LABEL_QUEUE_CLEARED###';
        foreach ($aTables as $sTable) {
            $oIntIndexSrv->clearIndexingQueueForTable($sTable);
        }

        return $status.('<ul><li>'.implode('</li><li/>', $aTables).'</li></ul>');
    }

    /**
     * Handle reset command from request.
     *
     * @param tx_mksearch_service_internal_Index $oIntIndexSrv
     */
    private function handleReset($oIntIndexSrv): string
    {
        $aTables = [];
        // reset aller elemente im siteroot
        $aTables['pid'] = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['resetTables'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['resetTables'] ?? null;
        // global, alle elemente
        $aTables['global'] = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['resetTablesG'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['resetTablesG'] ?? null;
        if ((!is_array($aTables['pid']) && empty($aTables['pid']))
            && (!is_array($aTables['global']) && empty($aTables['global']))
        ) {
            return '';
        }

        $where = [];
        $options = [];
        // deleted aussschließen, wenn nicht gesetzt
        if (!($GLOBALS['TYPO3_REQUEST']->getParsedBody()['resetDeleted'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['resetDeleted'] ?? null)) {
            $where[] = 'deleted = 0';
        }

        // hidden aussschließen, wenn nicht gesetzt
        if (!($GLOBALS['TYPO3_REQUEST']->getParsedBody()['resetHidden'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['resetHidden'] ?? null)) {
            $where[] = 'hidden = 0';
        }

        if ([] !== $where) {
            $options['where'] = implode(' AND ', $where);
        }

        $status = '###LABEL_QUEUE_RESETED###';
        foreach ($aTables as $key => $aResets) {
            if (is_array($aResets)) {
                foreach ($aResets as $sTable) {
                    $tableOptions = $options;

                    if ('pid' === $key) {
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

        return $status.('<ul><li>'.implode(
            '</li><li/>',
            array_unique(
                array_merge(
                    is_array($aTables['pid']) ? $aTables['pid'] : [],
                    is_array($aTables['global']) ? $aTables['global'] : []
                )
            )
        ).'</li></ul>');
    }

    /**
     * Handle trigger command from request.
     *
     * @param tx_mksearch_service_internal_Index $oIntIndexSrv
     */
    private function handleTrigger($oIntIndexSrv): string
    {
        $triggerIndexingQueue = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['triggerIndexingQueue'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['triggerIndexingQueue'] ?? null;
        if (!$triggerIndexingQueue) {
            return '';
        }

        // wir entfernen das time limit, da die indizierung ziemlich lange dauern kann.
        set_time_limit(0);

        $status = '';
        $indexItems = intval($GLOBALS['TYPO3_REQUEST']->getParsedBody()['triggerIndexingQueueCount'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['triggerIndexingQueueCount'] ?? null);

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
            $countAll = count(array_merge(...array_values($rows)));
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

    /**
     * Handle reset being indexed command from request.
     * Reset entries older then time given in parameter "beingIndexedOlderThan" in minutes.
     *
     * @param tx_mksearch_service_internal_Index $indexSrv
     */
    private function handleResetBeingIndexed($indexSrv): string
    {
        $triggerResetBeingIndexed = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['triggerResetBeingIndexed'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['triggerResetBeingIndexed'] ?? null;
        if (!$triggerResetBeingIndexed) {
            return '';
        }

        $status = '';
        $rows = $indexSrv->resetItemsBeingIndexed();

        if ($rows) {
            $status .= '<ul><li>###LABEL_ITEMS_BEING_INDEXED_RESETTED###: '.$rows.'</li></ul>';
        } else {
            $status .= '###LABEL_QUEUE_BEING_INDEXED_EMPTY###';
        }

        return $status;
    }

    public function getModuleIdentifier()
    {
        return 'mksearch';
    }
}
