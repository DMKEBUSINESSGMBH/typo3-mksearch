<?php
/**
 * @author Hannes Bochmann
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
 * Indexer service for irfaq.question called by the "mksearch" extension.
 */
class tx_mksearch_indexer_Irfaq extends tx_mksearch_indexer_Base
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
        return array('irfaq', 'question');
    }

    /**
     * {@inheritdoc}
     *
     * @see tx_mksearch_indexer_Base::hasDocToBeDeleted($model, $indexDoc, $options)
     *
     * @todo support exclude option, too
     */
    protected function hasDocToBeDeleted(
        tx_rnbase_IModel $model,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options = array()
    ) {
        return    parent::hasDocToBeDeleted($model, $indexDoc, $options) ||
                !$this->checkInOrExcludeOptions($this->getIrfaqCategoryService()->getByQuestion($model), $options);
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_Indexer::prepareSearchData()
     */
    protected function indexData(
        tx_rnbase_IModel $model,
        $tableName,
        $rawData,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        //check if at least one category of the current faq
        //is in the given include categories if this option is used
        //we could also extend the isIndexableRecord method but than
        //we would need to get the faq model and the categories
        //twice
        $categories = $this->getIrfaqCategoryService()->getByQuestion($model);
        if (!$this->checkInOrExcludeOptions($categories, $options)) {
            return null;
        }
        // else go one with indexing

        //index everything about the categories
        $this->indexArrayOfModelsByMapping(
            $categories,
            $this->getCategoryMapping(),
            $indexDoc,
            'category_'
        );

        // irfaq-Kategorien hinzufügen, wenn Option gesetzt ist
        $this->addCategoryData($indexDoc, $categories, $options);

        // set title, content and abstract as default
        // can be overwritten by the mapping
        $indexDoc->setTitle($model->getQ());
        $indexDoc->setContent(
            tx_mksearch_util_Misc::html2plain(
                $model->getA(),
                array('lineendings' => true)
            )
        );
        $indexDoc->setAbstract(
            tx_mksearch_util_Misc::html2plain(
                $model->getA(),
                array('lineendings' => true)
            ),
            $indexDoc->getMaxAbstractLength()
        );

        // index everything about the question
        $this->indexModelByMapping($model, $this->getQuestionMapping(), $indexDoc);

        $indexDoc = $this->indexAnswerTextKeepingHtml($model, $indexDoc);
        $indexDoc = $this->indexShortcutOfFirstCategory($categories, $indexDoc);

        //index everything about the expert
        $this->indexModelByMapping(
            $this->getIrfaqExpertService()->get($model->record['expert']),
            $this->getExpertMapping(),
            $indexDoc,
            'expert_'
        );

        //done
        return $indexDoc;
    }

    /**
     * @return tx_mksearch_service_irfaq_Category
     */
    protected function getIrfaqCategoryService()
    {
        return tx_mksearch_util_ServiceRegistry::getIrfaqCategoryService();
    }

    /**
     * @return tx_mksearch_service_irfaq_Expert
     */
    protected function getIrfaqExpertService()
    {
        return tx_mksearch_util_ServiceRegistry::getIrfaqExpertService();
    }

    /**
     * @param tx_rnbase_IModel                      $model
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     *
     * @return tx_mksearch_interface_IndexerDocument
     */
    private function indexAnswerTextKeepingHtml(
        tx_rnbase_IModel $model,
        tx_mksearch_interface_IndexerDocument $indexDoc
    ) {
        $indexDoc->addField('a_with_html_s', $model->record['a']);

        return $indexDoc;
    }

    /**
     * @param array                                 $categories
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     *
     * @return tx_mksearch_interface_IndexerDocument
     */
    private function indexShortcutOfFirstCategory(
        array $categories,
        tx_mksearch_interface_IndexerDocument $indexDoc
    ) {
        if (isset($categories[0]) &&
            $categories[0] instanceof tx_mksearch_model_irfaq_Category
        ) {
            $indexDoc->addField('category_first_shortcut_s', $categories[0]->record['shortcut']);
        }

        return $indexDoc;
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

        $categoryNames = array();
        foreach ($categories as $category) {
            $categoryNames[$category->record['uid']] = $category->record['title'];
        }

        $indexDoc->addField('categories_mi', array_keys($categoryNames));
        $indexDoc->addField('categoriesTitle_ms', array_values($categoryNames));

        // add field with the combined tags uids and names
        $dfs = tx_mksearch_util_KeyValueFacet::getInstance();
        $tagDfs = $dfs->buildFacetValues(array_keys($categoryNames), array_values($categoryNames));
        $indexDoc->addField('faq_categories_dfs_ms', $tagDfs, 'keyword');
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
    protected function stopIndexing($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options)
    {
        if ('tx_irfaq_expert' == $tableName) {
            $this->handleRelatedTableChanged($rawData, 'Expert');

            return true;
        } elseif ('tx_irfaq_cat' == $tableName) {
            $this->handleRelatedTableChanged($rawData, 'Category');

            return true;
        }

        return parent::stopIndexing($tableName, $rawData, $indexDoc, $options);
    }

    /**
     * Adds all given models to the queue.
     *
     * @param array $aModels
     */
    protected function handleRelatedTableChanged(array $rawData, $callback)
    {
        $callback = 'getBy'.$callback;

        $this->addModelsToIndex(
            $this->getIrfaqQuestionService()->$callback($rawData['uid']),
            'tx_irfaq_q'
        );
    }

    /**
     * @return tx_mksearch_service_irfaq_Question
     */
    protected function getIrfaqQuestionService()
    {
        return tx_mksearch_util_ServiceRegistry::getIrfaqQuestionService();
    }

    /**
     * Returns the mapping of the record fields to the
     * solr doc fields.
     *
     * @return array
     */
    protected function getQuestionMapping()
    {
        return array(
            'sorting' => 'sorting_i',
            'q' => 'q_s',
            'a' => 'a_s',
            'related' => 'related_s',
            'related_links' => 'related_links_s',
            'faq_files' => 'faq_files_s',
            'tstamp' => 'tstamp',
        );
    }

    /**
     * Returns the mapping of the record fields to the
     * solr doc fields.
     *
     * @return array
     */
    protected function getExpertMapping()
    {
        return array(
            'uid' => 'i',
            'name' => 'name_s',
            'email' => 'email_s',
            'url' => 'url_s',
        );
    }

    /**
     * Returns the mapping of the record fields to the
     * solr doc fields.
     *
     * @return array
     */
    protected function getCategoryMapping()
    {
        return array(
            'uid' => 'mi',
            'sorting' => 'sorting_mi',
            'title' => 'title_ms',
            'shortcut' => 'shortcut_ms',
        );
    }

    /**
     * Returns the model to be indexed.
     *
     * @param array  $rawData
     * @param string $tableName
     * @param array  $options
     *
     * @return tx_mksearch_model_irfaq_Question
     */
    protected function createModel(array $rawData, $tableName = null, $options = array())
    {
        return tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Question', $rawData);
    }

    /**
     * Return the default Typoscript configuration for an indexer.
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
        return <<<CONFIG
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields{
#   my_fixed_field_singlevalue = first
#   my_fixed_field_multivalue{
#      0 = first
#      1 = second
#   }
# }

### if one page in the rootline of an element has the no_search flag the element won't be indexed
respectNoSearchFlagInRootline = 1

# include {
#   as array
#   categories{
#     0 = 1
#     1 = 23
#   }
# or as string
#   categories = 1,23
# }
#
# you should always configure the root pageTree for this indexer in the includes. mostly the domain
# include.pageTrees {
#   0 = \$pid-of-domain
# }

# should a special workspace be indexed?
# default is the live workspace (ID = 0)
# comma separated list of workspace IDs
#workspaceIds = 1,2,3
CONFIG;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php'];
}
