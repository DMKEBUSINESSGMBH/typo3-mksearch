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
 * Indexer service for core.tt_content called by the "mksearch" extension.
 * takes care of normal tt_content without gridelements support.
 *
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
 */
class tx_mksearch_indexer_ttcontent_Normal extends tx_mksearch_indexer_Base
{
    /**
     * @var int
     */
    const USE_INDEXER_CONFIGURATION = 0;

    /**
     * @var int
     */
    const IS_INDEXABLE = 1;

    /**
     * @var int
     */
    const IS_NOT_INDEXABLE = -1;

    /**
     * Do the actual indexing for the given model.
     *
     * @param \Sys25\RnBase\Domain\Model\DataInterface                      $model
     * @param string                                $tableName
     * @param array                                 $rawData
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return tx_mksearch_interface_IndexerDocument|null
     */
    public function indexData(
        \Sys25\RnBase\Domain\Model\DataInterface $model,
        $tableName,
        $rawData,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        // @todo indexing via mapping so we dont have all field in the content

        // Set uid. Take care for localized records where uid of original record
        // is stored in $rawData['l18n_parent'] instead of $rawData['uid']!
        $indexDoc->setUid(\Sys25\RnBase\Backend\Utility\TCA::getUid($tableName, $rawData));

        $title = $this->getTitle($options);
        $indexDoc->setTitle($title);

        $indexDoc->setTimestamp($rawData['tstamp']);

        $indexDoc->addField('pid', $model->getProperty('pid'), 'keyword');
        $indexDoc->addField('CType', $rawData['CType'], 'keyword');

        if ($options['addPageMetaData']) {
            // @TODO: keywords werden doch immer kommasepariert angegeben,
            // warum mit leerzeichen trennen, das macht die keywords kaputt.
            $separator = (!empty($options['addPageMetaData.']['separator'])) ? $options['addPageMetaData.']['separator'] : ' ';
            // @TODO:
            //        konfigurierbar machen: description, author, etc.
            //        könnte wichtig werden!?
            $pageData = $this->getPageContent($model->getProperty('pid'), $options);
            if (!empty($pageData['keywords'])) {
                $keywords = explode($separator, $pageData['keywords']);
                foreach ($keywords as $key => $keyword) {
                    $keywords[$key] = trim($keyword);
                }
                $indexDoc->addField('keywords_ms', $keywords, 'keyword');
            }
        }

        if ($options['indexPageData']) {
            $this->indexPageData($indexDoc, $options);
        }

        // Try to call hook for the current CType.
        // A hook MUST call both $indexDoc->setContent and
        // $indexDoc->setAbstract (respect $indexDoc->getMaxAbstractLength())!
        $hookKey = 'indexer_core_TtContent_prepareData_CType_'.$rawData['CType'];
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookKey])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch'][$hookKey])
        ) {
            \Sys25\RnBase\Utility\Misc::callHook(
                'mksearch',
                $hookKey,
                [
                    'rawData' => &$rawData,
                    'options' => isset($options['CType.'][$rawData['CType'].'.']) ? $options['CType'][$rawData['CType'].'.'] : [],
                    'indexDoc' => &$indexDoc,
                ]
            );
        } else {
            // No hook found - we have to take care for content and abstract by ourselves...
            $fields = isset($options['CType.'][$rawData['CType'].'.']['indexedFields.']) ? $options['CType.'][$rawData['CType'].'.']['indexedFields.'] : $options['CType.']['_default_.']['indexedFields.'];

            $content = $this->getContentByContentType($rawData, $options);
            // Dieser Content-String ist deprecated!
            if (is_array($fields)) {
                foreach ($fields as $sDocKey => $sRecordKey) {
                    $content .= $this->getContentByFieldAndCType($sRecordKey, $rawData).' ';
                }
            }

            // Decode HTML
            $content = trim(tx_mksearch_util_Misc::html2plain($content));

            // Support für normale IndexedFields
            if (isset($options['indexedFields.'])) {
                $aIndexedFields = $options['indexedFields.'];
                if (is_array($aIndexedFields) && !empty($aIndexedFields)) {
                    foreach ($aIndexedFields as $sDocKey => $sRecordKey) {
                        // makes only sense if we have content
                        if (!empty($rawData[$sRecordKey])) {
                            // Enable field conversions...
                            $value = $options['keepHtml'] ? $rawData[$sRecordKey] : tx_mksearch_util_Misc::html2plain($rawData[$sRecordKey]);
                            $value = tx_mksearch_util_Indexer::getInstance()->doValueConversion($value, $sDocKey, $rawData, $sRecordKey, $options);
                            $indexDoc->addField(
                                $sDocKey,
                                $value
                            );
                        }
                    }
                }
            }

            // kein inhalt zum indizieren
            if (empty($title) && empty($content)) {
                $indexDoc->setDeleted(true);

                return $indexDoc;
            }

            // Include $title into indexed content
            $indexDoc->setContent($title.' '.$content);
            $indexDoc->setAbstract(empty($content) ? $title : $content, $indexDoc->getMaxAbstractLength());
        }

        return $indexDoc;
    }

    /**
     * returns the title for the element to index.
     *
     * @param array $options
     *
     * @return string
     */
    protected function getTitle($options)
    {
        $title = '';
        $model = $this->getModelToIndex();
        // we added our own header_layout (101). as long as there is no
        // additional config for this type (101) it isn't displayed in the FE
        // but indexed for Solr. so use header_layout == 100 if the title
        // should neither be indexed nor displayed in ther FE. use header_layout == 101
        // if the title shouldn't be displayed in the FE but indexed
        if (100 != $model->getHeaderLayout()) {
            // Decode HTML
            $title = trim(tx_mksearch_util_Misc::html2plain($model->getHeader()));
        }

        // optional fallback to page title, if the content title is empty
        if (empty($title) && empty($options['leaveHeaderEmpty'])) {
            $pageData = $this->getPageContent($model->getPid(), $options);
            $title = $pageData['title'];
        }

        return $title;
    }

    /**
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     */
    protected function indexPageData(tx_mksearch_interface_IndexerDocument $indexDoc, array $options)
    {
        $pageRecord = $this->getPageContent($this->getModelToIndex()->getProperty('pid'), $options);
        $pageModel = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, $pageRecord);
        $pageModel->setTableName('pages');

        if (!empty($options['pageDataFieldMapping.'])) {
            $this->indexModelByMapping(
                $pageModel,
                $options['pageDataFieldMapping.'],
                $indexDoc,
                'page_',
                $options
            );
        }
    }

    /**
     * Get the content by CType.
     *
     * This can be overridden by special types like gridelements.
     *
     * @param array $rawData
     * @param array $options
     *
     * @return string
     */
    protected function getContentByContentType(array $rawData, array $options)
    {
        return '';
    }

    /**
     * get the content by field and CType.
     *
     * @param mixed $field
     * @param array $rawData
     *
     * @return string
     */
    protected function getContentByFieldAndCType($field, array $rawData)
    {
        switch ($rawData['CType']) {
            case 'table':
                $tempContent = $rawData[$field];
                // explode bodytext containing table cells separated
                // by the character defined in flexform
                if ('bodytext' == $field) {
                    // Get table parsing options from flexform
                    $flex = \Sys25\RnBase\Utility\Arrays::xml2array($rawData['pi_flexform']);
                    if (is_array($flex)) {
                        $flexParsingOptions = $flex['data']['s_parsing']['lDEF'];
                        // Replace special parsing characters
                        if ($flexParsingOptions['tableparsing_quote']['vDEF']) {
                            $tempContent = str_replace(
                                chr($flexParsingOptions['tableparsing_quote']['vDEF']),
                                '',
                                $tempContent
                            );
                        }
                        if ($flexParsingOptions['tableparsing_delimiter']['vDEF']) {
                            $tempContent = str_replace(
                                chr($flexParsingOptions['tableparsing_delimiter']['vDEF']),
                                ' ',
                                $tempContent
                            );
                        }
                    }
                }
                break;
            default:
                $tempContent = $rawData[$field];
        }

        return $tempContent;
    }

    /**
     * Shall we break the indexing for the current data?
     *
     * when an indexer is configured for more than one table
     * the index process may be different for the tables.
     * overwrite this method in your child class to stop processing and
     * do something different like putting a record into the queue
     * if it's not the table that should be indexed
     *
     * @param string                                $tableName
     * @param array                                 $rawData
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return bool
     */
    protected function stopIndexing($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options)
    {
        if ('pages' == $tableName) {
            $this->handlePagesChanged($rawData);

            return true;
        }

        return parent::stopIndexing($tableName, $rawData, $indexDoc, $options);
    }

    /**
     * Adds all given models to the queue.
     *
     * @param array $aRawData
     */
    protected function handlePagesChanged(array $aRawData)
    {
        // every tt_content element on this page or it's
        // subpages has to be put into the queue.

        $aPidList = $this->_getPidList($aRawData['uid'], 999);

        if (!empty($aPidList)) {
            $oIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
            $aFrom = ['tt_content', 'tt_content'];

            foreach ($aPidList as $iPid) {
                // if the site is not existent we have one empty entry.
                if (!empty($iPid)) {
                    // hidden/deleted datasets can be excluded as they are not indexed
                    // see isIndexableRecord()
                    $aOptions = [
                        'where' => 'tt_content.pid='.$iPid,
                        'enablefieldsoff' => true,
                    ];
                    // as the pid list can be very long, we don't risk to create a sql
                    // statement that is too long. we are fine with a database access
                    // for each pid in the list as we are in the BE and performance shouldn't
                    // be a big concern!
                    $aRows = \Sys25\RnBase\Database\Connection::getInstance()->doSelect('tt_content.uid', $aFrom, $aOptions);

                    foreach ($aRows as $aRow) {
                        $oIndexSrv->addRecordToIndex('tt_content', $aRow['uid']);
                    }
                }
            }
        }
    }

    /**
     * Prüft ob das Element anhand des CType inkludiert oder ignoriert werden soll.
     *
     * @param array $sourceRecord
     * @param array $options
     *
     * @return bool
     */
    protected function checkCTypes($sourceRecord, $options)
    {
        $ctypes = $this->getConfigValue('ignoreCTypes', $options);
        if (is_array($ctypes) && count($ctypes)) {
            // Wenn das Element eines der definierten ContentTypen ist,
            // NICHT indizieren
            if (in_array($sourceRecord['CType'], $ctypes)) {
                return false;
            }
        } else {
            // Jetzt alternativ auf die includeCTypes prüfen
            $ctypes = $this->getConfigValue('includeCTypes', $options);
            if (is_array($ctypes) && count($ctypes)) {
                // Wenn das Element keines der definierten ContentTypen ist,
                // NICHT indizieren
                if (!in_array($sourceRecord['CType'], $ctypes)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sets the index doc to deleted if neccessary.
     *
     * @param \Sys25\RnBase\Domain\Model\DataInterface                      $model
     * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
     * @param array                                 $aOptions
     *
     * @return bool
     */
    protected function hasDocToBeDeleted(
        \Sys25\RnBase\Domain\Model\DataInterface $model,
        tx_mksearch_interface_IndexerDocument $oIndexDoc,
        $aOptions = []
    ) {
        // checkPageRights() considers deleted,
        // isPageSetIncludeInSearchDisable() checks the no_search field of page
        // and parent::hasDocToBeDeleted() takes
        // care of all possible hidden parent pages
        $sysPage = \Sys25\RnBase\Utility\TYPO3::getSysPage();

        return
            !($pageData = $this->getPageContent($model->getProperty('pid'), $aOptions))
            || !in_array($pageData['doktype'], $this->getSupportedDokTypes($aOptions))
            || $this->isPageSetIncludeInSearchDisable($model, $aOptions)
            || parent::hasDocToBeDeleted($model, $oIndexDoc, $aOptions);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function getSupportedDokTypes(array $options)
    {
        $sysPage = \Sys25\RnBase\Utility\TYPO3::getSysPage();
        $supportedDokTypes = $this->getConfigValue('supportedDokTypes', $options);
        if (!$supportedDokTypes) {
            $supportedDokTypes[] = $sysPage::DOKTYPE_DEFAULT;
        }

        return $supportedDokTypes;
    }

    /**
     *  Checks if the field "Include in Search" of current models page
     *  is set to "Disable".
     *
     * @param \Sys25\RnBase\Domain\Model\DataInterface $model
     * @param array            $options
     *
     * @return bool
     */
    protected function isPageSetIncludeInSearchDisable($model, $options)
    {
        if ($this->shouldRespectIncludeInSearchDisable($options)) {
            $page = $this->getPageContent($model->getProperty('pid'), $options);
            if (array_key_exists('no_search', $page) && 1 == $page['no_search']) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Checks if the indexer configuration "respectIncludeInSearchDisable" is set.
     *
     * @param array $options
     *
     * @return bool
     */
    protected function shouldRespectIncludeInSearchDisable($options)
    {
        $config = $this->getConfigValue('respectIncludeInSearchDisable', $options);

        return is_array($config) && !empty($config) && 1 == reset($config);
    }

    /**
     * Prüft ob das Element speziell in einer Spalte, einem Seitenbaum oder auf einer Seite liegt,
     * der/die inkludiert oder ausgeschlossen werden soll.
     * Der Entscheidungsbaum dafür ist relativ, sollte aber durch den Code
     * illustriert werden.
     *
     * @param array $sourceRecord
     * @param array $options
     *
     * @return bool
     */
    protected function isIndexableRecord(array $sourceRecord, array $options)
    {
        if (!isset($sourceRecord['tx_mksearch_is_indexable']) ||
            (self::USE_INDEXER_CONFIGURATION == $sourceRecord['tx_mksearch_is_indexable'])
        ) {
            $isIndexablePage =
                $this->isOnIndexablePage($sourceRecord, $options) &&
                $this->checkCTypes($sourceRecord, $options) &&
                $this->isIndexableColumn($sourceRecord, $options);
        } else {
            $isIndexablePage = (self::IS_INDEXABLE == $sourceRecord['tx_mksearch_is_indexable']);
        }

        return $isIndexablePage;
    }

    /**
     * Prüft ob das Element anhand der Spalte inkludiert oder ausgeschlossen werden soll.
     *
     * @param array $sourceRecord
     * @param array $options
     *
     * @return bool
     */
    protected function isIndexableColumn($sourceRecord, $options)
    {
        $columns = $this->getConfigValue('columns', $options['include.']);
        $isIndexableColumn = true;

        if (is_array($columns) && count($columns)) {
            $isIndexableColumn = in_array($sourceRecord['colPos'], $columns);
        }

        return $isIndexableColumn;
    }

    /**
     * wir brauchen auch noch die enable columns der page.
     *
     * @param \Sys25\RnBase\Domain\Model\DataInterface                      $model
     * @param string                                $tableName
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     *
     * @return tx_mksearch_interface_IndexerDocument
     */
    protected function indexEnableColumns(
        \Sys25\RnBase\Domain\Model\DataInterface $model,
        $tableName,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $indexDocFieldsPrefix = ''
    ) {
        $indexDoc = parent::indexEnableColumns($model, $tableName, $indexDoc, $indexDocFieldsPrefix);

        $page = $this->getPageContent($model->getProperty('pid'));
        $pageModel = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, $page);
        $indexDoc = parent::indexEnableColumns($pageModel, 'pages', $indexDoc, 'page_');

        return $indexDoc;
    }

    /**
     * Return content type identification.
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
     * @return string
     */
    public function getDefaultTSConfig()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @see tx_mksearch_indexer_Base::getGroupFieldValue()
     */
    protected function getGroupFieldValue(
        tx_mksearch_interface_IndexerDocument $indexDoc
    ) {
        $parts = $this->getContentType();
        $parts[] = $this->getModelToIndex()->getPid();

        return join(':', $parts);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Normal.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Normal.php'];
}
