<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 René Nitzsche <dev@dmk-ebusiness.de>
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
 * Indexer service for tt_news.news called by the "mksearch" extension.
 *
 * Ich denke diese Optionen sind überflüssig!
 *
 * Expected option for indexer configuration:
 */
class tx_mksearch_indexer_TtNewsNews extends tx_mksearch_indexer_Base
{
    /**
     * Return content type identification.
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
        return ['tt_news', 'news'];
    }

    /**
     * Get all categories of the news record.
     *
     * @param tx_rnbase_IModel $model
     *
     * @return array
     *
     * @todo support parent categories
     */
    private function getCategories(tx_rnbase_IModel $model)
    {
        $options = [
            'where' => 'tt_news_cat_mm.uid_local='.$model->getUid(),
            // as there is no tca for tt_news_cat_mm
            'wrapperclass' => 'tx_rnbase_model_Base',
            'orderby' => 'tt_news_cat_mm.sorting ASC',
        ];
        $join = ' JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_foreign=tt_news_cat.uid AND tt_news_cat.deleted=0 ';
        $from = ['tt_news_cat'.$join, 'tt_news_cat'];
        $rows = tx_rnbase_util_DB::doSelect(
            'tt_news_cat_mm.uid_foreign, tt_news_cat.uid, tt_news_cat.title, tt_news_cat.single_pid',
            $from,
            $options
        );

        return $rows;
    }

    /**
     * Do the actual indexing for the given model.
     *
     * @param tx_rnbase_IModel                      $oModel
     * @param string                                $tableName
     * @param array                                 $rawData
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return tx_mksearch_interface_IndexerDocument|null
     *
     * @todo index FE Group of category
     * @todo index target page of category for single view
     */
    public function indexData(
        tx_rnbase_IModel $oModel,
        $tableName,
        $rawData,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        $categories = $this->getCategories($oModel);
        // first check if certain categories should be included/excluded
        if (!$this->checkInOrExcludeOptions($categories, $options)
            || !$this->checkInOrExcludeOptions($categories, $options, 1)
        ) {
            $abort = true;
        }

        // Hook to append indexer
        tx_rnbase_util_Misc::callHook(
            'mksearch',
            'indexer_TtNews_prepareDataBeforeAddFields',
            [
                'rawData' => &$rawData,
                'options' => $options,
                'indexDoc' => &$indexDoc,
                'boost' => &$boost,
                'abort' => &$abort,
            ],
            $this
        );

        // At least one of the news' categories was found on black list
        if ($abort) {
            tx_rnbase_util_Logger::info(
                'News wurde nicht indiziert, weil Kategorie (Include/Exclude) nicht gesetzt ist'.
                ' oder das Signal von einem Hook gegeben wurde.',
                'mksearch'
            );
            if ($options['deleteOnAbort']) {
                $indexDoc->setDeleted(true);

                return $indexDoc;
            } else {
                return null;
            }
        }

        // Instantiate indexer document
        $indexDoc->setTitle($rawData['title']);
        $indexDoc->addField('pid', $rawData['pid']);

        // Keywords
        // @TODO: Page Meta data?
        // laut code werden nur die keywords der news mit indiziert.
        // das hätte sich über die indexedFields konfigurieren lassen!
        // wieder entfernen!
        if ($options['addPageMetaData']) {
            $separator = !empty($options['addPageMetaData.']['separator']) ? $options['addPageMetaData.']['separator'] : ' ';
            if (!empty($rawData['keywords'])) {
                $keywords = explode($separator, $rawData['keywords']);
                foreach ($keywords as $key => $keyword) {
                    $keywords[$key] = trim($keyword);
                }
                $indexDoc->addField('keywords_ms', $keywords, 'keyword');
            }
        }

        // Build content
        $content = '';

        // comma-separated list
        $bAddFields = false;
        if (!empty($options['indexedFields'])) {
            $aIndexedFields = explode(',', $options['indexedFields']);
        } // array
        else {
            $aIndexedFields = $options['indexedFields.'];
            // only in this case it is possible to have a mapping for the doc key and record key
            $bAddFields = true;
        }
        // extra fields to index besides bodytext, timstamp and short?
        if (is_array($aIndexedFields) && !empty($aIndexedFields)) {
            foreach ($aIndexedFields as $sDocKey => $sRecordKey) {
                // makes only sense if we have content
                if (!empty($rawData[$sRecordKey])) {
                    $content .= $rawData[$sRecordKey].' ';
                    // we add those fields also as own field and not just put it into content
                    // do we have a mapping?
                    if ($bAddFields) {
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

        // Decode HTML
        $content = $options['keepHtml'] ? $content : tx_mksearch_util_Misc::html2plain($content);

        // content besorgen
        foreach (['bodytext', 'short', 'title'] as $bodyTextField) {
            $bodyText = $options['keepHtml'] ? $rawData[$bodyTextField] : tx_mksearch_util_Misc::html2plain($rawData[$bodyTextField]);
            if (!empty($bodyText)) {
                break;
            }
        }
        $bodyText = empty($bodyText) ? $content : $bodyText;

        // wir indizieren nicht, wenn kein content für die news da ist.
        if (empty($bodyText)) {
            $indexDoc->setDeleted(true);

            return $indexDoc;
        }

        // Feed the indexer document
        $indexDoc->setTimestamp($rawData['tstamp']);

        // bei leerem content gibts in Solr Fehler
        if (!empty($content)) {
            $indexDoc->setContent($content);
        }

        // You are strongly encouraged to use $indexDoc->getMaxAbstractLength()
        // to limit the length of your abstract!
        // You are indeed free to ignore that limit
        // if you have good reasons to do so, but always
        // keep in mind that the abstract data is stored with the indexed document
        // - so think about your data traffic!
        // You may define the limit for your content type centrally with the key
        // "abstractMaxLength_[your extkey]_[your content type]"
        // as defined at $indexDoc constructor
        $sAbstract = empty($rawData['short']) ? $bodyText : $rawData['short'];
        $indexDoc->setAbstract(
            $options['keepHtml'] ? $sAbstract : tx_mksearch_util_Misc::html2plain($sAbstract),
            $indexDoc->getMaxAbstractLength()
        );

        // News-Kategorien hinzufügen, wenn Option gesetzt ist
        $this->addCategoryData($indexDoc, $categories, $options);

        // Indexing of categories removed for now,
        // as 'localisation' of tt_news categories REALLY sucks ...
        // see Commit on 12.03.2014 from mwagner

        // TODO: einen offset aus der Config lesen
        $offset = 0;
        $indexDoc->addField(
            'datetime_dt',
            tx_mksearch_util_Misc::getISODateFromTimestamp($rawData['datetime'], $offset),
            'keyword',
            1.0,
            'date'
        );

        // Index datetime of news item so news can be filtered by datetime
        $indexDoc->addField('news_text_s', $bodyText, 'keyword');
        $indexDoc->addField('news_text_t', $bodyText, 'keyword');

        return $indexDoc;
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
        if ('tt_news_cat' == $tableName) {
            // Eine Kategorie wurde verändert
            // Alle News müssen neu indiziert werden.
            $this->handleCategoryChanged($rawData);

            return true;
        }
        // else
        return parent::stopIndexing($tableName, $rawData, $indexDoc, $options);
    }

    /**
     * Handle data change for category. All connected news should be updated.
     *
     * @param array $catRecord
     */
    private function handleCategoryChanged($catRecord)
    {
        $catUid = $catRecord['uid'];
        // Die UIDs aller betroffenen News holen
        $options = [];
        $options['where'] = 'tt_news_cat_mm.uid_foreign='.$catUid;
        $options['enablefieldsoff'] = true;
        $from = ['tt_news_cat_mm JOIN tt_news ON tt_news.uid=tt_news_cat_mm.uid_local AND tt_news.deleted=0', 'tt_news_cat_mm'];
        $rows = $this->doSelect('tt_news.uid AS uid', $from, $options);
        // Alle gefundenen News für die Neuindizierung anmelden.
        $srv = $this->getIntIndexService();
        foreach ($rows as $row) {
            $srv->addRecordToIndex('tt_news', $row['uid']);
        }
    }

    /**
     * DB-Zugriff kapseln, damit er testbar wird.
     *
     * @param string $what
     * @param array  $from
     * @param array  $options
     */
    protected function doSelect($what, $from, $options)
    {
        return tx_rnbase_util_DB::doSelect($what, $from, $options);
    }

    /**
     * wird hier nicht mehr verwendet. Die tx_rnbase_util_DB bitte direkt verwenden.
     *
     * @return string
     *
     * @deprecated
     */
    protected function getDbUtil()
    {
        return tx_rnbase_util_DB;
    }

    /**
     * @return tx_mksearch_service_internal_Index
     */
    protected function getIntIndexService()
    {
        return tx_mksearch_util_ServiceRegistry::getIntIndexService();
    }

    /**
     * {@inheritdoc}
     *
     * @see tx_mksearch_indexer_Base::createModel()
     */
    protected function createModel(array $rawData, $tableName = null, $options = [])
    {
        return tx_rnbase::makeInstance('tx_rnbase_model_Base', $rawData);
    }

    /**
     * Adds Categories.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $categories
     * @param array                                 $options
     */
    private function addCategoryData(
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $categories,
        $options
    ) {
        if (empty($options['addCategoryData'])
            || empty($categories)) {
            return;
        }

        $categoryNames = [];
        foreach ($categories as $category) {
            $categoryNames[$category->record['uid_foreign']] = $category->record['title'];
            // Die erste Kategorie mit einer Single-PID wird gewinnen
            if (!$iCategorySinglePid) {
                $iCategorySinglePid = (int) $category->record['single_pid'];
            }
        }
        if (!$iCategorySinglePid) {
            $iCategorySinglePid = (int) $options['defaultSinglePid'];
        }

        $indexDoc->addField('categories_mi', array_keys($categoryNames));
        $indexDoc->addField('categoriesTitle_ms', array_values($categoryNames));

        // add field with the combined tags uids and names
        $dfs = tx_mksearch_util_KeyValueFacet::getInstance();
        $tagDfs = $dfs->buildFacetValues(array_keys($categoryNames), array_values($categoryNames));
        $indexDoc->addField('categories_dfs_ms', $tagDfs, 'keyword');

        $indexDoc->addField('categorySinglePid_i', $iCategorySinglePid);
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
    public function getDefaultTSConfig()
    {
        return <<<CONF
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields{
#  my_fixed_field_singlevalue = first
#   my_fixed_field_multivalue{
#      0 = first
#      1 = second
#   }
# }

addCategoryData = 0

addPageMetaData = 0
addPageMetaData.separator = ,

# Should the HTML Markup in indexed fields, the abstract and the content be kept?
# by default every HTML Markup is removed
# keepHtml = 1

# Configuration which field from the record should be
# indexed. By default those fields are already filled:
# "abstract" is filled with either "short" or "bodytext"
# "news_text_s" and "news_text_t" are filled with "bodytext"
# Please take note that those fields have to be in the schema.xml
#
# in this case the content of the given fields is merged into the "content" field
# indexedFields = bodytext, short
#
# in this case the content of "image" is mapped to the solr field "image_s"
# besides the content of the given fields is also merged into "content"
# indexedFields {
#   image_s = image
#   short_t = short
#   keywords_s = keywords
#   author_s = author
# }
### delete from or abort indexing for the record if isIndexableRecord or no record?
deleteIfNotIndexable = 0
### delete from indexing or abort indexing for the record?
deleteOnAbort = 0

### if one page in the rootline of an element has the no_search flag the element won't be indexed
respectNoSearchFlagInRootline = 1


### should the Root Page of the current records page be indexed?
# indexSiteRootPage = 0

#
# Sometimes news with a certain category have specially to
# be in- or excluded
#
# include{
#   categories = 1,2
#   categories{
#      0 = 1
#      1 = 2
#   }
# }
# exclude{
#   categories = 1,2
#   categories{
#      0 = 1
#      1 = 2
#   }
# }

# you should always configure the root pageTree for this indexer in the includes. mostly the domain
# include.pageTrees {
#   0 = $pid-of-domain
# }

# should a special workspace be indexed?
# default is the live workspace (ID = 0)
# comma separated list of workspace IDs
#workspaceIds = 1,2,3
CONF;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtNewsNews.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtNewsNews.php'];
}
