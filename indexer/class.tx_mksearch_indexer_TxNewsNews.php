<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2017 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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

tx_rnbase::load('tx_mksearch_indexer_Base');

/**
 * Indexer service for tx_news.news called by the "mksearch" extension.
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_indexer
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_indexer_TxNewsNews extends tx_mksearch_indexer_Base
{
    /**
     * Return content type identification.
     *
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
        return array('tx_news', 'news');
    }

    /**
     * Creates the extbase model of the raw datas uid.
     *
     * @param array $rawData
     *
     * @return \GeorgRinger\News\Domain\Model\News
     */
    protected function createNewsModel(array $rawData)
    {
        $uid = (int) $rawData['uid'];

        if (!$uid) {
            return null;
        }

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
        );
        $repository = $objectManager->get(
            'GeorgRinger\\News\\Domain\\Repository\\NewsRepository'
        );

        return $repository->findByIdentifier($uid);
    }

    /**
     * check if related data has changed
     *
     * @param string $tableName
     * @param array $rawData
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array $options
     * @return bool
     */
    protected function stopIndexing(
        $tableName,
        $rawData,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        if ($tableName == 'sys_category') {
            $this->handleCategoryChanged($rawData);

            return true;
        } elseif ($tableName == 'tx_news_domain_model_tag') {
            $this->handleTagChanged($rawData);

            return true;
        }

        return parent::stopIndexing($tableName, $rawData, $indexDoc, $options);
    }

    /**
     * Handle data change for category. All connected news should be updated
     *
     * @param array $catRecord
     *
     * @return void
     */
    private function handleCategoryChanged($catRecord)
    {
        $rows = $this->getDatabaseConnection()->doSelect(
            'NEWS.uid AS uid',
            array(
                'tx_news_domain_model_news AS NEWS' .
                    ' JOIN sys_category_record_mm AS CATMM ON NEWS.uid = CATMM.uid_foreign',
                'tx_news_domain_model_news',
                'NEWS',
            ),
            array(
                'where' => 'CATMM.uid_local = ' . (int) $catRecord['uid'],
                'orderby' => 'sorting_foreign DESC',
            )
        );

        // Alle gefundenen News für die Neuindizierung anmelden.
        $srv = $this->getIntIndexService();
        foreach ($rows as $row) {
            $srv->addRecordToIndex('tx_news_domain_model_news', (int) $row['uid']);
        }
    }

    /**
     * Handle data change for category. All connected news should be updated
     *
     * @param array $catRecord
     *
     * @return void
     */
    private function handleTagChanged($catRecord)
    {
        // @TODO: find all news for the tag and add to index.
    }

    /**
     * Do the actual indexing for the given model
     *
     * @param tx_rnbase_IModel $oModel
     * @param string $tableName
     * @param array $rawData
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array $options
     *
     * @return tx_mksearch_interface_IndexerDocument|null
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    public function indexData(
        tx_rnbase_IModel $oModel,
        $tableName,
        $rawData,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        // @codingStandardsIgnoreEnd
        $abort = false;

        $news = $this->createNewsModel($rawData);

        // Hook to append indexer data
        tx_rnbase_util_Misc::callHook(
            'mksearch',
            'indexer_TxNews_prepareDataBeforeAddFields',
            array(
                'news' => &$news,
                'rawData' => &$rawData,
                'options' => $options,
                'indexDoc' => &$indexDoc,
                'abort' => &$abort,
            ),
            $this
        );

        if (!$news){
            $abort = true;
        }

        // At least one of the news' categories was found on black list
        if ($abort) {
            tx_rnbase::load('tx_rnbase_util_Logger');
            tx_rnbase_util_Logger::info(
                'News wurde nicht indiziert, weil das Signal von einem Hook gegeben wurde.',
                'mksearch'
            );
            if ($options['deleteOnAbort']) {
                $indexDoc->setDeleted(true);

                return $indexDoc;
            } else {
                return null;
            }
        }

        $this->indexNews($rawData, $news, $indexDoc, $options);
        $this->indexNewsTags($rawData, $news, $indexDoc);
        $this->indexNewsCategories($rawData, $news, $indexDoc);

        // Hook to extend indexer
        tx_rnbase_util_Misc::callHook(
            'mksearch',
            'indexer_TxNews_prepareDataAfterAddFields',
            array(
                'news' => &$news,
                'rawData' => &$rawData,
                'options' => $options,
                'indexDoc' => &$indexDoc,
            ),
            $this
        );

        return $indexDoc;
    }

    /**
     * Add data of the News to the index
     *
     * @param array $rawData
     * @param \GeorgRinger\News\Domain\Model\News $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array $options
     *
     * @return void
     */
    // @codingStandardsIgnoreStart (interface/abstract/unittest mistake)
    protected function indexNews(
        array $rawData,
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        array $options = array()
    ) {
        // @codingStandardsIgnoreEnd
        /* @var $news \GeorgRinger\News\Domain\Model\News */

        $indexDoc->addField('pid', $news->getPid());
        $indexDoc->setTitle($news->getTitle());
        $indexDoc->setTimestamp($news->getTstamp());

        $content = trim(
            implode(
                CRLF . CRLF,
                array(
                    $news->getTeaser(),
                    $news->getBodytext(),
                    $news->getDescription(),
                    // Add contentelements to indexer content
                    $this->indexNewsContentElements($news, $indexDoc, $options),
                )
            )
        );

        $this->indexNewsMedia($news, $indexDoc);

        $abstract = $news->getTeaser();
        if (empty($abstract)) {
            $abstract = $content;
        }

        $bodyText = '';
        foreach (array(
                $news->getBodytext(),
                $news->getTeaser(),
                $news->getTitle(),
                $content,
                $abstract,
            ) as $html) {
            if (empty($html)) {
                continue;
            }
            $bodyText = $html;
            break;
        }

        // html entfernen
        if (empty($options['keepHtml'])) {
            foreach (array(&$content, &$abstract, &$bodyText) as &$html) {
                $html = tx_mksearch_util_Misc::html2plain($html);
            }
        }

        // wir indizieren nicht, wenn kein content für die news da ist.
        if (empty($content)) {
            $indexDoc->setDeleted(true);

            return;
        }

        $indexDoc->setContent($content);
        $indexDoc->setAbstract($abstract, $indexDoc->getMaxAbstractLength());

        $indexDoc->addField('news_text_s', $bodyText, 'keyword');
        $indexDoc->addField('news_text_t', $bodyText, 'keyword');

        $indexDoc->addField(
            'datetime_dt',
            tx_mksearch_util_Misc::getIsoDate($news->getDatetime()),
            'keyword',
            1.0,
            'date'
        );
    }

    /**
     * Add tag data of the News to the index
     *
     * @param array $rawData
     * @param unknown $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array $options
     */
    // @codingStandardsIgnoreStart (interface/abstract/unittest mistake)
    protected function indexNewsTags(
        array $rawData,
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        array $options = array()
    ) {
        // @codingStandardsIgnoreEnd
        $tags = array();
        foreach ($news->getTags() as $tag) {
            $tags[$tag->getUid()] = $tag->getTitle();
        }
        $indexDoc->addField('keywords_ms', array_values($tags), 'keyword');
    }

    /**
     * Add category data of the News to the index
     *
     * @param array $rawData
     * @param unknown $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array $options
     */
    // @codingStandardsIgnoreStart (interface/abstract/unittest mistake)
    protected function indexNewsCategories(
        array $rawData,
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        array $options = array()
    ) {
        /* @var $category \GeorgRinger\News\Domain\Model\Category */
        $categories = array();
        $singlePid = 0;

        foreach ($news->getCategories() as $category) {
            $categories[$category->getUid()] = $category->getTitle();
            if (!$singlePid) {
                $singlePid = $category->getSinglePid();
            }
        }

        $indexDoc->addField(
            'categorySinglePid_i',
            $singlePid ?: (int) $options['defaultSinglePid']
        );

        $indexDoc->addField('categories_mi', array_keys($categories));
        $indexDoc->addField('categoriesTitle_ms', array_values($categories));

        // add field with the combined tags uids and names
        tx_rnbase::load('tx_mksearch_util_KeyValueFacet');
        $dfs = tx_mksearch_util_KeyValueFacet::getInstance();
        $tagDfs = $dfs->buildFacetValues(array_keys($categories), array_values($categories));
        $indexDoc->addField('categories_dfs_ms', $tagDfs, 'keyword');
    }

    /**
     * Add category data of the News to the index
     *
     * @param \GeorgRinger\News\Domain\Model\News $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array $options
     */
    protected function indexNewsContentElements(
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        array $options = array()
    ) {
        if (empty($options['indexInlineContentElements'])) {
            return '';
        }
        tx_mksearch_util_Indexer::prepareTSFE($options['defaultSinglePid']);

        $ce = array();
        $contentElements = $news->getContentElements();
        foreach ($contentElements as $contentElement) {
            $cObj = tx_mktools_util_T3Loader::getContentObject($contentElement->getUid());

            // jetzt das contentelement parsen
            $cObj->start($contentElement->_getProperties(), 'tt_content');
            $ce[$contentElement->getUid()] = $cObj->cObjGetSingle('<tt_content', array());
            $ce[] = trim($ce[$contentElement->getUid()]);
        }

        return implode(CRLF . CRLF, $ce);
    }

    /**
     * Index media data
     *
     * @param \GeorgRinger\News\Domain\Model\News $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     */
    public function indexNewsMedia(
        $news,
        $indexDoc
    ) {
        if (!$news->getMedia()->count()) {
            return;
        }

        $titles = $descriptions = array();
        foreach ($news->getMedia() as $media) {
            $titles[] = $media->getTitle();
            $descriptions[] = $media->getDescription();
        }

        if($news->getFirstPreview()) {
            $indexDoc->addField('news_listimage_ref_uid_i', $news->getFirstPreview()->getUid());
            $indexDoc->addField('news_listimage_ref_title_s', $news->getFirstPreview()->getTitle());
            $indexDoc->addField('news_listimage_ref_desc_s', $news->getFirstPreview()->getDescription());
        }
        $indexDoc->addField('news_images_ref_title_ms', $titles);
        $indexDoc->addField('news_images_ref_desc_ms', $descriptions);
    }

    /**
     * The conection to the db.
     *
     * @return Tx_Rnbase_Database_Connection
     */
    protected function getDatabaseConnection()
    {
        return Tx_Rnbase_Database_Connection::getInstance();
    }

    /**
     * The index Service
     * @return tx_mksearch_service_internal_Index
     */
    protected function getIntIndexService()
    {
        return tx_mksearch_util_ServiceRegistry::getIntIndexService();
    }

    /**
     * Return the default Typoscript configuration for this indexer.
     *
     * Overwrite this method to return the indexer's TS config.
     * Note that this config is not used for actual indexing
     * but only serves as assistance when actually configuring an indexer!
     * Hence all possible configuration options should be set or
     * at least be mentioned to provide an easy-to-access inline documentation!
     *
     * @return string
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    public function getDefaultTSConfig()
    {
        // @codingStandardsIgnoreEnd
        return <<<CONF
# Should the HTML Markup in indexed fields, the abstract and the content be kept?
# by default every HTML Markup is removed
# keepHtml = 1

### delete from or abort indexing for the record if isIndexableRecord or no record?
deleteIfNotIndexable = 0
### delete from indexing or abort indexing for the record?
deleteOnAbort = 0

### should the Root Page of the current records page be indexed?
# indexSiteRootPage = 0

### should inline contentelements be indexed?
# indexInlineContentElements = 0

### news detail pid to get TSFE in indexer
# defaultSinglePid = 0

# you should always configure the root pageTree for this indexer in the includes. mostly the domain
# include.pageTrees {
#   0 = pid-of-domain
# }

# should a special workspace be indexed?
# default is the live workspace (ID = 0)
# comma separated list of workspace IDs
#workspaceIds = 1,2,3
CONF;
    }
}

if ((
    defined('TYPO3_MODE') &&
    $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']
        ['ext/mksearch/indexer/class.tx_mksearch_indexer_TxNewsNews.php']
)) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']
        ['ext/mksearch/indexer/class.tx_mksearch_indexer_TxNewsNews.php'];
}
