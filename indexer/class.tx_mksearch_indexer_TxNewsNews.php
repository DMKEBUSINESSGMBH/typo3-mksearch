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

/**
 * Indexer service for tx_news.news called by the "mksearch" extension.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_indexer_TxNewsNews extends tx_mksearch_indexer_Base
{
    /**
     * @var bool
     */
    protected $loadFrontendForLocalization = true;

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
        return ['tx_news', 'news'];
    }

    /**
     * check if related data has changed.
     *
     * @param string                                $tableName
     * @param array                                 $rawData
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return bool
     */
    protected function stopIndexing(
        $tableName,
        $rawData,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        $stopIndexing = parent::stopIndexing($tableName, $rawData, $indexDoc, $options);

        if ('sys_category' == $tableName) {
            $this->handleCategoryChanged($rawData);

            $stopIndexing = true;
        } elseif ('tx_news_domain_model_tag' == $tableName) {
            $this->handleTagChanged($rawData);

            $stopIndexing = true;
        }

        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'indexer_TxNews_afterStopIndexing',
            [
                'tableName' => &$tableName,
                'rawData' => &$rawData,
                'indexDoc' => $indexDoc,
                'options' => &$options,
                'stopIndexing' => &$stopIndexing,
            ],
            $this
        );

        return $stopIndexing;
    }

    /**
     * Handle data change for category. All connected news should be updated.
     *
     * @param array $catRecord
     */
    private function handleCategoryChanged($catRecord)
    {
        $rows = $this->getDatabaseConnection()->doSelect(
            'NEWS.uid AS uid',
            [
                'tx_news_domain_model_news AS NEWS'.
                    ' JOIN sys_category_record_mm AS CATMM ON NEWS.uid = CATMM.uid_foreign',
                'tx_news_domain_model_news',
                'NEWS',
            ],
            [
                'where' => 'CATMM.tablenames = "tx_news_domain_model_news" AND CATMM.uid_local = '.(int) $catRecord['uid'],
                'orderby' => 'sorting_foreign DESC',
            ]
        );

        // Alle gefundenen News für die Neuindizierung anmelden.
        $srv = $this->getIntIndexService();
        foreach ($rows as $row) {
            $srv->addRecordToIndex('tx_news_domain_model_news', (int) $row['uid']);
        }
    }

    /**
     * Handle data change for tags. All related news for that tag should be updated.
     *
     * @param array $tagRecord
     */
    private function handleTagChanged($tagRecord)
    {
        $rows = $this->getDatabaseConnection()->doSelect(
            'NEWS.uid AS uid',
            [
                'tx_news_domain_model_news AS NEWS'.
                ' JOIN tx_news_domain_model_news_tag_mm AS TAGMM ON NEWS.uid = TAGMM.uid_local',
                'tx_news_domain_model_news',
                'NEWS',
            ],
            [
                'where' => 'TAGMM.uid_foreign = '.(int) $tagRecord['uid'],
            ]
        );
        // Alle gefundenen News für die Neuindizierung anmelden.
        $srv = $this->getIntIndexService();
        foreach ($rows as $row) {
            $srv->addRecordToIndex('tx_news_domain_model_news', (int) $row['uid']);
        }
    }

    /**
     * Do the actual indexing for the given model.
     *
     * @param \Sys25\RnBase\Domain\Model\DataInterface                      $oModel
     * @param string                                $tableName
     * @param array                                 $rawData
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return tx_mksearch_interface_IndexerDocument|null
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    public function indexData(
        \Sys25\RnBase\Domain\Model\DataInterface $model,
        $tableName,
        $rawData,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        // @codingStandardsIgnoreEnd
        $abort = false;

        $news = $this->createLocalizedExtbaseDomainModel(
            $rawData,
            $tableName,
            'GeorgRinger\\News\\Domain\\Repository\\NewsRepository'
        );

        // Hook to append indexer data
        if (!$news) {
            \Sys25\RnBase\Utility\Misc::callHook(
                'mksearch',
                'indexer_TxNews_prepareDataBeforeAddFields',
                [
                    'news' => $news,
                    'rawData' => &$rawData,
                    'options' => $options,
                    'indexDoc' => $indexDoc,
                    'abort' => &$abort,
                ],
                $this
            );
        }

        if (!$news) {
            $abort = true;
        }

        // At least one of the news' categories was found on black list
        if ($abort) {
            \Sys25\RnBase\Utility\Logger::info(
                'News wurde nicht indiziert, weil das Signal von einem Hook gegeben wurde.',
                'mksearch',
                [
                    'uid' => $rawData['uid'],
                ]
            );
            if ($options['deleteOnAbort'] ?? false) {
                $indexDoc->setDeleted(true);

                return $indexDoc;
            } else {
                return null;
            }
        }

        $this->indexNews($rawData, $news, $indexDoc, $options);
        $this->indexNewsTags($rawData, $news, $indexDoc);
        $this->indexRelatedLinks($news, $indexDoc);
        $this->indexNewsCategories($rawData, $news, $indexDoc);

        // Hook to extend indexer
        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'indexer_TxNews_prepareDataAfterAddFields',
            [
                'news' => &$news,
                'rawData' => &$rawData,
                'options' => $options,
                'indexDoc' => &$indexDoc,
            ],
            $this
        );

        return $indexDoc;
    }

    /**
     * Add data of the News to the index.
     *
     * @param array                                 $rawData
     * @param \GeorgRinger\News\Domain\Model\News   $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     */
    // @codingStandardsIgnoreStart (interface/abstract/unittest mistake)
    protected function indexNews(
        array $rawData,
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        array $options = []
    ) {
        // @codingStandardsIgnoreEnd
        /* @var $news \GeorgRinger\News\Domain\Model\News */

        $indexDoc->addField('pid', $news->getPid());
        $indexDoc->setTitle($news->getTitle());
        $timestamp = $news->getTstamp() instanceof DateTime ? $news->getTstamp()->getTimestamp() : $news->getTstamp();
        $indexDoc->setTimestamp($timestamp);

        $content = trim(
            implode(
                CRLF.CRLF,
                [
                    $news->getTeaser(),
                    $news->getBodytext(),
                    $news->getDescription(),
                    // Add contentelements to indexer content
                    $this->indexNewsContentElements($news, $indexDoc, $options),
                ]
            )
        );

        $this->indexNewsMedia($news, $indexDoc);

        $abstract = $news->getTeaser();
        if (empty($abstract)) {
            $abstract = $content;
        }

        $bodyText = '';
        foreach ([
                $news->getBodytext(),
                $news->getTeaser(),
                $news->getTitle(),
                $content,
                $abstract,
            ] as $html) {
            if (empty($html)) {
                continue;
            }
            $bodyText = $html;
            break;
        }

        // html entfernen
        if (empty($options['keepHtml'])) {
            foreach ([&$content, &$abstract, &$bodyText] as &$html) {
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
        $indexDoc->addField('news_type_i', $news->getType(), 'keyword');

        $this->addExternalUrlToIndex($news, $indexDoc);
        $this->addInternalUrlToIndex($news, $indexDoc);

        if ($news->getDatetime()) {
            $indexDoc->addField(
                'datetime_dt',
                tx_mksearch_util_Misc::getIsoDate($news->getDatetime()),
                'keyword',
                1.0,
                'date'
            );
        }
    }

    /**
     * Adds the external URL if news type is external.
     *
     * @param \GeorgRinger\News\Domain\Model\News   $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     *
     * @return void
     */
    protected function addExternalUrlToIndex(
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc
    ) {
        if (2 == $news->getType()) {
            $indexDoc->addField('news_external_url_s', $news->getExternalurl());
        }
    }

    /**
     * @param \GeorgRinger\News\Domain\Model\News   $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     *
     * @return void
     */
    protected function addInternalUrlToIndex(
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc
    ) {
        if (1 == $news->getType()) {
            $indexDoc->addField('news_internal_url_s', $news->getInternalurl());
        }
    }

    /**
     * Add tag data of the News to the index.
     *
     * @param array                                 $rawData
     * @param \GeorgRinger\News\Domain\Model\News   $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     */
    // @codingStandardsIgnoreStart (interface/abstract/unittest mistake)
    protected function indexNewsTags(
        array $rawData,
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        array $options = []
    ) {
        // @codingStandardsIgnoreEnd
        $tags = [];
        foreach ($news->getTags() as $tag) {
            $tags[$tag->getUid()] = $tag->getTitle();
        }
        $indexDoc->addField('keywords_ms', array_values($tags), 'keyword');
    }

    /**
     * Add category data of the News to the index.
     *
     * @param array                                 $rawData
     * @param \GeorgRinger\News\Domain\Model\News   $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     */
    // @codingStandardsIgnoreStart (interface/abstract/unittest mistake)
    protected function indexNewsCategories(
        array $rawData,
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        array $options = []
    ) {
        /* @var $category \GeorgRinger\News\Domain\Model\Category */
        $categories = [];
        $singlePid = 0;

        foreach ($news->getCategories() as $category) {
            $categories[$category->getUid()] = $category->getTitle();
            if (!$singlePid) {
                $singlePid = $category->getSinglePid();
            }
        }

        $indexDoc->addField(
            'categorySinglePid_i',
            $singlePid ?: (int) ($options['defaultSinglePid'] ?? 0)
        );

        $indexDoc->addField('categories_mi', array_keys($categories));
        $indexDoc->addField('categoriesTitle_ms', array_values($categories));

        // add field with the combined tags uids and names
        $dfs = tx_mksearch_util_KeyValueFacet::getInstance();
        $tagDfs = $dfs->buildFacetValues(array_keys($categories), array_values($categories));
        $indexDoc->addField('categories_dfs_ms', $tagDfs, 'keyword');
    }

    /**
     * Add tag data of the News to the index.
     *
     * @param \GeorgRinger\News\Domain\Model\News   $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     */
    // @codingStandardsIgnoreStart (interface/abstract/unittest mistake)
    protected function indexRelatedLinks($news, tx_mksearch_interface_IndexerDocument $indexDoc)
    {
        // @codingStandardsIgnoreEnd
        if ($relatedLinks = $news->getRelatedLinks()) {
            $linksData = [];
            $mapping = [
                'related_links_title_ms' => 'getTitle',
                'related_links_title_mt' => 'getTitle',
                'related_links_description_ms' => 'getDescription',
                'related_links_description_mt' => 'getDescription',
                'related_links_uri_ms' => 'getUri',
            ];
            foreach ($relatedLinks as $link) {
                foreach ($mapping as $solrField => $getterMethod) {
                    $linksData[$solrField][] = $link->$getterMethod();
                }
            }
            foreach ($linksData as $solrField => $data) {
                $indexDoc->addField($solrField, $data);
            }
        }
    }

    /**
     * Add category data of the News to the index.
     *
     * @param \GeorgRinger\News\Domain\Model\News   $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     */
    protected function indexNewsContentElements(
        /* \GeorgRinger\News\Domain\Model\News */ $news,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        array $options = []
    ) {
        if (empty($options['indexInlineContentElements'])) {
            return '';
        }
        tx_mksearch_util_Indexer::prepareTSFE($options['defaultSinglePid'], $options['lang']);

        $ce = [];
        $contentElements = $news->getContentElements();

        /** @var \GeorgRinger\News\Domain\Model\TtContent $contentElement */
        foreach ($contentElements as $contentElement) {
            $contentUid = $contentElement->getUid();
            $cObj = tx_mktools_util_T3Loader::getContentObject($contentUid);

            // render gridelment or default content element
            $ce[$contentUid] = 'gridelements_pi1' === $contentElement->getCType()
                ? trim($this->renderGridelement($cObj, $contentElement))
                : trim($this->renderContentElement($cObj, $contentElement));
        }

        return implode(CRLF.CRLF, $ce);
    }

    /**
     * Render Default tt_content element.
     *
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj
     * @param \GeorgRinger\News\Domain\Model\TtContent                $contentElement
     *
     * @return string
     */
    protected function renderContentElement(
        $cObj,
        /* \GeorgRinger\News\Domain\Model\TtContent */ $contentElement
    ) {
        $cObj->start($contentElement->_getProperties(), 'tt_content');

        return $cObj->cObjGetSingle('<tt_content', []);
    }

    /**
     * Render gridelement.
     *
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj
     * @param \GeorgRinger\News\Domain\Model\TtContent                $contentElement
     *
     * @return string
     */
    protected function renderGridelement(
        $cObj,
        /* \GeorgRinger\News\Domain\Model\TtContent */ $contentElement
    ) {
        // we need to complete record to render the gridelement correctly
        $rawData = $this->getDatabaseConnection()->doSelect(
            '*',
            'tt_content',
            [
                'where' => 'uid='.$contentElement->getUid(),
            ]
        );
        if (isset($rawData[0])) {
            $cObj->start($rawData[0], 'tt_content');
        }

        return $cObj->cObjGetSingle(
            $GLOBALS['TSFE']->tmpl->setup['tt_content.']['gridelements_pi1'],
            $GLOBALS['TSFE']->tmpl->setup['tt_content.']['gridelements_pi1.']
        );
    }

    /**
     * Index media data.
     *
     * @param \GeorgRinger\News\Domain\Model\News   $news
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     */
    public function indexNewsMedia(
        $news,
        $indexDoc
    ) {
        if (!$news->getMedia() || !$news->getMedia()->count()) {
            return;
        }

        $titles = $descriptions = [];
        foreach ($news->getMedia() as $media) {
            $titles[] = $media->getTitle();
            $descriptions[] = $media->getDescription();
        }

        if ($news->getFirstPreview()) {
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
     * @return \Sys25\RnBase\Database\Connection
     */
    protected function getDatabaseConnection()
    {
        return \Sys25\RnBase\Database\Connection::getInstance();
    }

    /**
     * The index Service.
     *
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

### if one page in the rootline of an element has the no_search flag the element won't be indexed
respectNoSearchFlagInRootline = 1

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

# When the news records are not inside a page tree with a site configuration and translations are used we need
# to configure a pid for which the FE is loaded.
#localizationPid = 123
CONF;
    }
}
