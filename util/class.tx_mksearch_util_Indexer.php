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

class tx_mksearch_util_Indexer
{
    /**
     * @return tx_mksearch_util_Indexer
     */
    public static function getInstance()
    {
        static $instance = null;
        if (!is_object($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Liefert die UID des Datensatzes.
     *
     * @param string $tableName
     * @param array  $rawData
     * @param array  $options
     *
     * @return int
     *
     * @deprecated use getUid of the rnbase model as it will return the correct uid in respect of localisation
     */
    public function getRecordsUid($tableName, array $rawData, array $options)
    {
        return tx_rnbase_util_TCA::getUid($tableName, $rawData);
    }

    /**
     * Liefert eine Datumsfeld für Solr.
     *
     * @param string $date | insert "@" before timestamps
     *
     * @return string
     */
    public function getDateTime($date)
    {
        $dateTime = tx_rnbase::makeInstance('DateTime', $date, tx_rnbase::makeInstance('DateTimeZone', 'GMT-0'));
        $dateTime->setTimeZone(tx_rnbase::makeInstance('DateTimeZone', 'UTC'));

        return $dateTime->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Indexes all fields of the model according to the given mapping.
     *
     * @param tx_rnbase_IModel                      $model
     * @param array                                 $aMapping
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param string                                $prefix
     * @param array                                 $options
     * @param bool                                  $dontIndexHidden
     *
     * @return tx_mksearch_interface_IndexerDocument
     */
    public function indexModelByMapping(
        tx_rnbase_IModel $model,
        array $recordIndexMapping,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $prefix = '',
        array $options = array(),
        $dontIndexHidden = true
    ) {
        // get the record from the model, so the new rnbase models are supportet, who do not have access to ->record!
        $record = $model->getRecord();
        foreach ($recordIndexMapping as $recordKey => $indexDocKey) {
            if ($dontIndexHidden && $model->isHidden()) {
                continue;
            }
            if (!empty($record[$recordKey]) || $options['keepEmpty']) {
                $indexDoc->addField(
                    $prefix.$indexDocKey,
                    $options['keepHtml'] ? $record[$recordKey] :
                        tx_mksearch_util_Misc::html2plain($record[$recordKey])
                );
            }
        }

        return $indexDoc;
    }

    /**
     * Collects the values of all models inside the given array
     * and adds them as multivalue (array).
     *
     * @param array[tx_rnbase_IModel]               $model
     * @param array                                 $aMapping
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param string                                $prefix
     * @param array                                 $options
     * @param bool                                  $dontIndexHidden
     *
     * @return tx_mksearch_interface_IndexerDocument
     */
    public function indexArrayOfModelsByMapping(
        array $models,
        array $recordIndexMapping,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $prefix = '',
        array $options = array(),
        $dontIndexHidden = true
    ) {
        //collect values
        $tempIndexDoc = [];
        /* @var $model tx_rnbase_IModel */
        foreach ($models as $model) {
            if (!$model) {
                continue;
            }
            foreach ($recordIndexMapping as $recordKey => $indexDocKey) {
                if ($dontIndexHidden && $model->isHidden()) {
                    continue;
                }
                if (!empty($model->getProperty($recordKey))) {
                    // Attributes can be commaseparated to index values into different fields
                    $indexDocKeys = Tx_Rnbase_Utility_Strings::trimExplode(',', $indexDocKey);
                    foreach ($indexDocKeys as $indexDocKey) {
                        $value = $model->getProperty($recordKey);
                        $tempIndexDoc[$prefix.$indexDocKey][] = $this->doValueConversion($value, $indexDocKey, $model->getProperty(), $recordKey, $options);
                    }
                }
            }
        }

        //and now add the fields
        if (!empty($tempIndexDoc)) {
            foreach ($tempIndexDoc as $indexDocKey => $values) {
                $indexDoc->addField($indexDocKey, $values);
            }
        }

        return $indexDoc;
    }

    /**
     * Handle fieldsConversion from indexer configuration.
     *
     * @param string $value
     * @param string $indexDocKey
     * @param array  $rawData
     * @param string $sRecordKey
     * @param array  $options
     *
     * @return array
     */
    public function doValueConversion($value, $indexDocKey, $rawData, $sRecordKey, $options)
    {
        if (!(array_key_exists('fieldsConversion.', $options) &&
            array_key_exists($indexDocKey.'.', $options['fieldsConversion.']))) {
            return $value;
        }

        $cfg = $options['fieldsConversion.'][$indexDocKey.'.'];

        if (isset($cfg['unix2isodate']) && intval($cfg['unix2isodate']) > 0) {
            // Datum konvertieren
            // einen offset aus der Config lesen
            $offset = isset($cfg['unix2isodate_offset']) ? intval($cfg['unix2isodate_offset']) : 0;
            $value = tx_mksearch_util_Misc::getISODateFromTimestamp($value, $offset);
        }
        // stdWrap ausführen
        tx_rnbase_util_TYPO3::getTSFE(); // TSFE wird für cObj benötigt
        $cObj = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getContentObjectRendererClass());
        $cObj->data = $rawData;

        if ($options['fieldsConversion.'][$indexDocKey]) {
            $cObj->setCurrentVal($value);
            $value = $cObj->cObjGetSingle($options['fieldsConversion.'][$indexDocKey], $options['fieldsConversion.'][$indexDocKey.'.']);
            $cObj->setCurrentVal(false);
        } else {
            $value = $cObj->stdWrap($value, $options['fieldsConversion.'][$indexDocKey.'.']);
        }
        // userFunc can return serialized strings to support arrays as return value
        if (false !== ($data = @unserialize($value))) {
            $value = $data;
        }

        return $value;
    }

    /**
     * Adds a element to the queue.
     *
     * @TODO refactor to tx_mksearch_service_internal_Index::addModelsToIndex
     * @TODO remove static indexSrv cache
     *
     * @param tx_rnbase_IModel $model
     * @param string           $tableName
     * @param bool             $prefer
     * @param string           $resolver  class name of record resolver
     * @param array            $data
     * @param array            $options
     */
    public function addModelToIndex(
        Tx_Rnbase_Domain_Model_RecordInterface $model,
        $tableName,
        $prefer = false,
        $resolver = false,
        $data = false,
        array $options = array()
    ) {
        static $indexSrv;
        if (!empty($model) && $model->isValid()) {
            if (!$indexSrv) {
                $indexSrv = $this->getInternalIndexService();
            }
            $indexSrv->addRecordToIndex(
                $tableName,
                $model->getUid(),
                $prefer,
                $resolver,
                $data,
                $options
            );
        }
    }

    /**
     * @return tx_mksearch_service_internal_Index
     */
    protected function getInternalIndexService()
    {
        return tx_mksearch_util_ServiceRegistry::getIntIndexService();
    }

    /**
     * just a wrapper for addModelToIndex and an array of models.
     *
     * @TODO refactor to tx_mksearch_service_internal_Index::addModelsToIndex
     *
     * @param array  $models
     * @param string $tableName
     * @param bool   $prefer
     * @param string $resolver  class name of record resolver
     * @param array  $data
     * @param array  $options
     */
    public function addModelsToIndex(
        $models,
        $tableName,
        $prefer = false,
        $resolver = false,
        $data = false,
        array $options = array()
    ) {
        if (!empty($models)) {
            foreach ($models as $model) {
                $this->addModelToIndex(
                    $model,
                    $tableName,
                    $prefer,
                    $resolver,
                    $data,
                    $options
                );
            }
        }
    }

    /**
     * Checks if a include or exclude option was set for a
     * given option key
     * if set we check if at least one of the uids given in the options
     * is found in the given models
     * for the include option returning true means no option set or we have a hit
     * for the exclude option returning true means no option was set or we have no hit.
     *
     * @param array $models  | array of models
     * @param array $options
     * @param int   $mode    | 0 stands for "include" and 1 "exclude"
     *
     * @return bool
     *
     * @todo move function to a helper class as the method has nothing to do
     * with actual indexing. it's just a helper.
     */
    public function checkInOrExcludeOptions($models, $options, $mode = 0, $optionKey = 'categories')
    {
        //set base returns depending on the mode
        switch ($mode) {
            case 0:
            default:
                $mode = 'include';
                $noHit = false; //if we have no hit
                $isHit = true; //if we have a hit
                break;
            case 1:
                $mode = 'exclude';
                $noHit = true; //if we have no hit
                $isHit = false; //if we have a hit
                break;
        }

        //should only elements with a special category etc. be indexed?
        if (!empty($options[$mode.'.'][$optionKey.'.']) || !empty($options[$mode.'.'][$optionKey])) {
            $isValid = $noHit; //no option given
            if (!empty($models)) {
                //include categories as array like
                //include.categories{
                // 0 = 1
                // 1 = 2
                //}
                if (!empty($options[$mode.'.'][$optionKey.'.'])) {
                    $includeCategories = $options[$mode.'.'][$optionKey.'.'];
                } //include categories as string like
                //include.categories = 1,2
                elseif (!empty($options[$mode.'.'][$optionKey])) {
                    $includeCategories = explode(',', $options[$mode.'.'][$optionKey]);
                }

                //if config is empty nothing to do and everything is alright
                if (empty($includeCategories)) {
                    return true;
                }

                //check if at least one category of the current element
                //is in the given include categories
                foreach ($models as $model) {
                    if (in_array($model->getUid(), $includeCategories)) {
                        $isValid = $isHit;
                    }
                }
            }
        } else {
            $isValid = true;
        }

        return $isValid;
    }

    /**
     * Prüft ob das Element speziell in einem Seitenbaum oder auf einer Seite liegt,
     * der/die inkludiert oder ausgeschlossen werden soll.
     * Der Entscheidungsbaum dafür ist relativ, sollte aber durch den Code
     * illustriert werden.
     *
     * @param array $sourceRecord
     * @param array $options
     *
     * @return bool
     */
    public function isOnIndexablePage($sourceRecord, $options)
    {
        $pid = $sourceRecord['pid'];
        $includePages = $this->getConfigValue('pages', $options['include.']);

        if (in_array($pid, $includePages)) {
            return true;
        } else {
            return $this->pageIsNotInIncludePages($pid, $options);
        }
    }

    /**
     * entweder sind die include pages leer oder es wurde kein eintrag gefunden.
     * was der fall wird in.
     *
     * @param int   $pid
     * @param array $options
     *
     * @return bool
     */
    private function pageIsNotInIncludePages($pid, array $options)
    {
        $includePageTrees = $this->getConfigValue('pageTrees', $options['include.']);

        if (empty($includePageTrees)) {
            return $this->includePageTreesNotSet($pid, $options);
        } else {
            return $this->includePageTreesSet($pid, $options);
        }
    }

    /**
     * @param int   $pid
     * @param array $options
     *
     * @return bool
     */
    private function includePageTreesNotSet($pid, array $options)
    {
        $excludePageTrees = $this->getConfigValue('pageTrees', $options['exclude.']);

        if (false !== $this->getFirstRootlineIndexInPageTrees($pid, $excludePageTrees)) {
            return false;
        } else {
            return $this->pageIsNotInExcludePageTrees($pid, $options);
        }
    }

    /**
     * @param int   $pid
     * @param array $options
     *
     * @return bool
     */
    private function pageIsNotInExcludePageTrees($pid, array $options)
    {
        if ($this->pageIsNotInExcludePages($pid, $options)) {
            $includePages = $this->getConfigValue('pages', $options['include.']);

            return empty($includePages);
        } else {
            return false;
        }
    }

    /**
     * @param int   $pid
     * @param array $options
     *
     * @return bool
     */
    private function pageIsNotInExcludePages($pid, array $options)
    {
        $excludePages = $this->getConfigValue('pages', $options['exclude.']);

        return !in_array($pid, $excludePages);
    }

    /**
     * @param int   $pid
     * @param array $options
     *
     * @return bool
     */
    private function includePageTreesSet($pid, array $options)
    {
        $includePageTrees = $this->getConfigValue('pageTrees', $options['include.']);
        $firstRootlineIndexInIncludePageTrees =
            $this->getFirstRootlineIndexInPageTrees($pid, $includePageTrees);

        if (false === $firstRootlineIndexInIncludePageTrees) {
            return false;
        } else {
            return $this->pageIsInIncludePageTrees($pid, $options, $firstRootlineIndexInIncludePageTrees);
        }
    }

    /**
     * @param int            $pid
     * @param array          $options
     * @param int || boolean $firstRootlineIndexInIncludePageTrees
     *
     * @return bool
     */
    private function pageIsInIncludePageTrees($pid, array $options, $firstRootlineIndexInIncludePageTrees)
    {
        $excludePageTrees = $this->getConfigValue('pageTrees', $options['exclude.']);
        $firstRootlineIndexInExcludePageTrees =
            $this->getFirstRootlineIndexInPageTrees($pid, $excludePageTrees);

        if ($this->isExcludePageTreeCloserToThePidThanAnIncludePageTree(
            $firstRootlineIndexInExcludePageTrees,
            $firstRootlineIndexInIncludePageTrees
        )
        ) {
            return false;
        } else {
            return $this->pageIsNotInExcludePages($pid, $options);
        }
    }

    /**
     * Desto höher der index desto näher sind wir an der pid dran.
     * warum? siehe doc von getRootlineByPid!
     *
     * @param int $excludePageTreesIndex
     * @param int $includePageTreesIndex
     *
     * @return bool
     */
    private function isExcludePageTreeCloserToThePidThanAnIncludePageTree(
        $excludePageTreesIndex,
        $includePageTreesIndex
    ) {
        return $excludePageTreesIndex > $includePageTreesIndex;
    }

    /**
     * @param int   $pid
     * @param array $pageTrees
     *
     * @return int the index in the rootline of the hit || boolean
     */
    private function getFirstRootlineIndexInPageTrees($pid, $pageTrees)
    {
        $rootline = $this->getRootlineByPid($pid);

        foreach ($rootline as $index => $page) {
            if (in_array($page['uid'], $pageTrees)) {
                return $index;
            }
        }

        return false;
    }

    /**
     * eigene methode damit wir mocken können.
     *
     * die reihenfolge der einträge beginnt mit der pid selbst
     * und geht dann den seitenbaum nach oben. das heißt desto größer
     * der index desto näher sind wir an der pid dran.
     *
     * @param int $pid
     *
     * @return array
     */
    public function getRootlineByPid($pid)
    {
        return tx_rnbase_util_TYPO3::getSysPage()->getRootLine($pid);
    }

    /**
     * Returns the siteroot page of the given page.
     *
     * @param int $uid
     *
     * @return array
     */
    public function getSiteRootPage($uid)
    {
        foreach ($this->getRootlineByPid($uid) as $page) {
            if ($page['is_siteroot']) {
                return $page;
            }
        }

        return array();
    }

    /**
     * Returns al list of page ids from the siteroot page of the given page.
     *
     * @param int $uid
     *
     * @return string
     */
    public function getPidListFromSiteRootPage($uid, $recursive = 0)
    {
        $rootPage = $this->getSiteRootPage($uid);
        if (empty($rootPage)) {
            return '';
        }

        return tx_rnbase_util_Misc::getPidList($rootPage['uid'], $recursive);
    }

    /**
     * Liefert einen Wert aus der Konfig
     * Beispiel: $key = test
     * Dann wird geprüft ob test eine kommaseparierte Liste liegt
     * Ist das nicht der Fall wird noch geprüft ob test. ein array ist.
     *
     * @param string $key
     * @param array  $options
     *
     * @return array
     */
    public function getConfigValue($key, $options)
    {
        $config = array();
        if (is_array($options)) {
            if (isset($options[$key]) && strlen(trim($options[$key]))) {
                $config = tx_rnbase_util_Strings::trimExplode(',', $options[$key]);
            } elseif (isset($options[$key.'.']) && is_array($options[$key.'.'])) {
                $config = $options[$key.'.'];
            }
        }

        return $config;
    }

    /**
     * Get's the page of the content element if it's not hidden/deleted.
     *
     * @param int $pid
     *
     * @return array
     */
    public function getPageContent($pid)
    {
        $pid = (int) $pid;
        if (!$pid) {
            return array();
        }
        //first of all we have to check if the page is not hidden/deleted
        $sqlOptions = array(
            'where' => 'pages.uid='.$pid.' AND hidden=0 AND deleted=0',
            'enablefieldsoff' => true, //ignore fe_group and so on
            'limit' => 1,
        );
        $from = array('pages', 'pages');
        $page = tx_rnbase_util_DB::doSelect('*', $from, $sqlOptions);

        return !empty($page[0]) ? $page[0] : array();
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
     * @param array                                 $sourceRecord
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return bool
     */
    public function stopIndexing(
        $tableName,
        $sourceRecord,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        // Wir prüfen, ob die zu indizierende Sprache stimmt.
        $sysLanguageUidField = tx_mksearch_util_TCA::getLanguageFieldForTable($tableName);
        if (isset($sourceRecord[$sysLanguageUidField])) {
            // @TODO: getTransOrigPointerFieldForTable abprüfen, wenn $lang!=0 !
            $languages = array(0);
            if (isset($options['lang'])) {
                $languages = Tx_Rnbase_Utility_Strings::intExplode(',', $options['lang'], true);
            }

            // stop if not set to "All languages" and lang doesn't match
            if (
                $sourceRecord[$sysLanguageUidField] >= 0 &&
                !in_array($sourceRecord[$sysLanguageUidField], $languages)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Initializes the tsfe for the tv elements.
     *
     * @param int $pid
     * @param int $sysLanguage
     */
    public static function prepareTSFE($pid, $sysLanguage = 0)
    {
        $tsfe = tx_rnbase_util_Misc::prepareTSFE(
            array(
                'force' => true,
                'pid' => $pid,
                // usually 0 is the normal page type
                // @todo make configurable
                'type' => 0,
            )
        );

        // add cobject for some plugins like tt_news
        $tsfe->cObj = tx_rnbase::makeInstance(
            tx_rnbase_util_Typo3Classes::getContentObjectRendererClass()
        );

        // disable cache for be indexing!
        $tsfe->no_cache = true;

        // load TypoScript templates
        if ($pid) {
            $rootlineByPid = self::getInstance()->getRootlineByPid($pid);

            $tsfe->rootLine = $rootlineByPid;
            $tsfe->forceTemplateParsing = true;
            $GLOBALS['TSFE']->getConfigArray();
        }

        // handle language
        $tsfe->sys_language_content = intval($sysLanguage);
    }
}
