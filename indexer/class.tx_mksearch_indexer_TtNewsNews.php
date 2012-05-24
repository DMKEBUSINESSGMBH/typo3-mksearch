<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 René Nitzsche <nitzsche@das-medienkombinat.de>
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_indexer_Base');

/**
 * Indexer service for tt_news.news called by the "mksearch" extension.
 *
 * Ich denke diese Optionen sind überflüssig!
 *
 * Expected option for indexer configuration:
 * * [array] indexedFields (mandatory - set by default):
 *		Define which fields are used for building the index
 *		E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['tt_news.news']['indexedFields'] = array('imagealttext', 'imagetitletext', 'short', 'bodytext', 'keywords');
 * * [int] lang (optional):
 *		$sys_language_uid of the desired language
 * 		E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['tt_news.news']['lang'] = 1;
 * * exclude (optional):
 *		Several black lists: Exclude news from indexing by various conditions
 * 		* [array] categories:
 * 			Exclude single categories from indexing.
 * 		 	E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['tt_news.news']['exclude']['categories'] = array(2, 4);
 * 		* [array] categoryTrees:
 * 			Exclude complete category trees (i. e. categories with all their children) from indexing
 * 		 	E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['tt_news.news']['exclude']['categoryTrees'] = array(3, 5, 8);
 *
 */
class tx_mksearch_indexer_TtNewsNews extends tx_mksearch_indexer_Base {
	
	/**
	 * Return content type identification.
	 * This identification is part of the indexed data
	 * and is used on later searches to identify the search results.
	 * You're completely free in the range of values, but take care
	 * as you at the same time are responsible for
	 * uniqueness (i.e. no overlapping with other content types) and
	 * consistency (i.e. recognition) on indexing and searching data.
	 *
	 * @return array('extKey' => [extension key], 'name' => [key of content type]
	 */
	public static function getContentType() {
		return array('tt_news', 'news');
	}
	
	/**
	 * get all categories of the news record
	 * @param tx_rnbase_model_base $oModel
	 */
	private function getCategories($oModel) {
		$aOptions = array(
			'where' => 'tt_news_cat_mm.uid_local=' . $oModel->getUid(),
			//as there is no tca for tt_news_cat_mm
			'wrapperclass' => 'tx_rnbase_model_Base',
			'orderby' => 'tt_news_cat_mm.sorting ASC'
		);
		$sJoin = ' JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_foreign=tt_news_cat.uid AND tt_news_cat.deleted=0 ';
		$aFrom = array('tt_news_cat'.$sJoin, 'tt_news_cat');
		$aRows = tx_rnbase_util_DB::doSelect(
			'tt_news_cat_mm.uid_foreign, tt_news_cat.uid, tt_news_cat.title, tt_news_cat.single_pid', 
			$aFrom, $aOptions
		);
		
		return $aRows;
	}

	/**
	 * @see tx_mksearch_indexer_Base::indexData()
	 */
	public function indexData(tx_rnbase_model_base $oModel, $tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		$aCategories = $this->getCategories($oModel);
		// first check if certain categories should be included/excluded
		if(!$this->checkInOrExcludeOptions($aCategories,$options) || !$this->checkInOrExcludeOptions($aCategories,$options,1))
			$abort = true;
			
		// Hook to append indexer
		tx_rnbase_util_Misc::callHook('mksearch','indexer_TtNews_prepareDataBeforeAddFields',
								array(
									'rawData' => &$rawData,
									'options' => $options,
									'indexDoc' => &$indexDoc,
									'boost' => &$boost,
									'abort' => &$abort,
								), $this);

		// At least one of the news' categories was found on black list
		if ($abort) {
			tx_rnbase::load('tx_rnbase_util_Logger');
			tx_rnbase_util_Logger::info('News wurde nicht indiziert, weil Kategorie (Include/Exclude) nicht gesetzt ist.', 'mksearch');
			if($options['deleteOnAbort']) {
				$indexDoc->setDeleted(true);
				return $indexDoc;
			} else return null;
		}

		// Instantiate indexer document
		$indexDoc->setTitle($rawData['title']);
		$indexDoc->addField('pid', $rawData['pid']);
		$indexDoc->setFeGroups(t3lib_div::intExplode(',', $rawData['fe_group']));
		
		// Keywords
		// @TODO: Page Meta data?
		// laut code werden nur die keywords der news mit indiziert.
		// das hätte sich über die indexedFields konfigurieren lassen!
		// wieder entfernen!
		if($options['addPageMetaData']) {
			$separator = (!empty($options['addPageMetaData.']['separator'])) ? $options['addPageMetaData.']['separator'] : ' ';
			if(!empty($rawData['keywords'])) {
				$keywords = explode($separator, $rawData['keywords']);
				foreach($keywords as $key => $keyword) $keywords[$key] = trim($keyword);
				$indexDoc->addField('keywords_ms', $keywords, 'keyword');
			}
		}
		
		// Build content
		$content = '';
		
		//comma-separated list
		$bAddFields = false;
		if(!empty($options['indexedFields'])) $aIndexedFields = explode(',', $options['indexedFields']);
		//array
		else{
			$aIndexedFields = $options['indexedFields.'];
			$bAddFields = true;//only in this case it is possible to have a mapping for the doc key and record key
		}
		
		//extra fields to index besides bodytext, timstamp and short?
		if(is_array($aIndexedFields) && !empty($aIndexedFields)){
			foreach ($aIndexedFields as $sDocKey => $sRecordKey){
				if(!empty($rawData[$sRecordKey])){//makes only sense if we have content
					$content .= $rawData[$sRecordKey] . ' ';
					//we add those fields also as own field and not just put it into content
					if($bAddFields)//do we have a mapping?
						$indexDoc->addField(
							$sDocKey,
							$options['keepHtml'] ? $rawData[$sRecordKey] : tx_mksearch_util_Misc::html2plain($rawData[$sRecordKey])
						);
				}
			}
		}
		// Decode HTML
		$content = $options['keepHtml'] ? $content : tx_mksearch_util_Misc::html2plain($content);
		
		// content besorgen
		$bodyText = $rawData['bodytext'];
		$bodyText = empty($bodyText) ? $rawData['short'] : $bodyText;
		$bodyText = empty($bodyText) ? $rawData['title'] : $bodyText;
		$bodyText = empty($bodyText) ? $content : $bodyText;
		
		// html entfernen
		$bodyText = $options['keepHtml'] ? $bodyText : tx_mksearch_util_Misc::html2plain($bodyText);
		
		// wir indizieren nicht, wenn kein content für die news da ist.
		if(empty($bodyText)){
			$indexDoc->setDeleted(true);
			return $indexDoc;
		}
		
		// Feed the indexer document
		$indexDoc->setTimestamp($rawData['tstamp']);
		
		//bei leerem content gibts in Solr Fehler
		if(!empty($content))
			$indexDoc->setContent($content);
		
		// You are strongly encouraged to use $indexDoc->getMaxAbstractLength() to limit the length of your abstract!
		// You are indeed free to ignore that limit if you have good reasons to do so, but always
		// keep in mind that the abstract data is stored with the indexed document - so think about your data traffic!
		// You may define the limit for your content type centrally with the key
		// "abstractMaxLength_[your extkey]_[your content type]" as defined at $indexDoc constructor
		$sAbstract = empty($rawData['short']) ? $bodyText : $rawData['short'];
		$indexDoc->setAbstract(
			$options['keepHtml'] ? $sAbstract : tx_mksearch_util_Misc::html2plain($sAbstract),
			$indexDoc->getMaxAbstractLength()
		);
		
		// Kategorieen hinzufügen, wenn Option gesetzt ist
		$this->addCategoryData($indexDoc, $aCategories, $options);
		
		// Indexing of categories disabled for now, as "localisation" of tt_news categories REALLY sucks...
//		$searcher = tx_rnbase_util_SearchBase::getInstance('tx_rnbase_util_SearchGeneric');
//		$fields = array();
//		$options = array(
//						'what' => 'NCMM.uid',
//						'searchdef' => array(
//							'basetable' => 'tt_news_cat_mm',
//							'basetablealias' => 'NCMM',
//							'usealias' => 1,
//						)
//					);
//		$categories = $searcher->search($fields, $options);
//
//		$fields = array();
//		$options = array(
//						'what' => 'CAT.uid',
//						'searchdef' => array(
//							'basetable' => 'tt_news_cat',
//							'basetablealias' => 'CAT',
//							'usealias' => 1,
//						)
//					);
//		$config = array(
//						'keyField' => 'uid',
//						'parentField' => 'parent_category',
//					);
//
		// Additional data
		$addData = array();
//
//		foreach ($categories as $c) {
//			$config['startingPoint'] = $c['uid'];
//			$parents = tx_mksearch_util_Tree::getAncestors($fields, $options, $config);
//			foreach (array_reverse($parents) as $p)
//				// Localise records - is this even possible within the first db query cycle (via adjustment of $options)?
//				;
//			$parentPath = '';
//		}

		$offset = 0; // TODO: einen offset aus der Config lesen
		$indexDoc->addField('datetime_dt',
							tx_mksearch_util_Misc::getISODateFromTimestamp($rawData['datetime'], $offset),
							'keyword', 1.0, 'date');

		// Index datetime of news item so news can be filtered by datetime
//		$indexDoc->addField('datetime', $rawData['datetime'], 'keyword');
		$indexDoc->addField('news_text_s', $bodyText, 'keyword');
		$indexDoc->addField('news_text_t', $bodyText, 'keyword');
		
		return $indexDoc;
	}
	
	/**
	 * check if related data has changed
	 * @param string $sTableName
	 * @param array $aRawData
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param array $aOptions
	 *
	 * @return bool
	 */
	protected function stopIndexing($sTableName, $aRawData, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions) {
		if($sTableName == 'tt_news_cat') {
			// Eine Kategorie wurde verändert
			// Alle News müssen neu indiziert werden.
			$this->handleCategoryChanged($aRawData);
			return true;
		}
	}
	
	/**
	 * Handle data change for category. All connected news should be updated
	 * @param array $catRecord
	 */
	private function handleCategoryChanged($catRecord) {
		$catUid = $catRecord['uid'];
		// Die UIDs aller betroffenen News holen
		$options = array();
		$options['where'] = 'tt_news_cat_mm.uid_foreign=' . $catUid;
		$from = array('tt_news_cat_mm JOIN tt_news ON tt_news.uid=tt_news_cat_mm.uid_local AND tt_news.deleted=0', 'tt_news_cat_mm');
		$rows = tx_rnbase_util_DB::doSelect('tt_news.uid AS uid', $from, $options);
		// Alle gefundenen News für die Neuindizierung anmelden.
		$srv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
		foreach($rows As $row) {
			$srv->addRecordToIndex('tt_news', $row['uid']);
		}
	}
	
	/**
	 * Returns the model to be indexed
	 *
	 * @param array $aRawData
	 *
	 * @return tx_mksearch_model_irfaq_Question
	 */
	protected function createModel(array $aRawData) {
		return tx_rnbase::makeInstance('tx_rnbase_model_Base', $aRawData);
	}
	
	/**
	 * Adds Categories
	 * @param $indexDoc
	 * @param $aCategories
	 */
	private function addCategoryData($indexDoc, $aCategories, $aOptions) {
		if(empty($aOptions['addCategoryData']) || empty($aCategories)) return;
		
		foreach($aCategories as $categorie) {
			$aCategoryUid[] = $categorie->record['uid_foreign'];
			$aCategoryTitle[] = $categorie->record['title'];
			if(!$iCategorySinglePid) $iCategorySinglePid = intval($categorie->record['single_pid']);
		}
		if(!$iCategorySinglePid) $iCategorySinglePid = intval($aOptions['defaultSinglePid']);
		
		$indexDoc->addField('categories_mi', $aCategoryUid);
		$indexDoc->addField('categoriesTitle_ms', $aCategoryTitle);
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
	public function getDefaultTSConfig() {
		return <<<CONF
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields{
#	my_fixed_field_singlevalue = first
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
#	image_s = image
# }
### delete from or abort indexing for the record if isIndexableRecord or no record?
 deleteIfNotIndexable = 0
### delete from indexing or abort indexing for the record?
 deleteOnAbort = 0
#
# Sometimes news with a certain category have specially to
# be in- or excluded
#
# include{
#	categories = 1,2
#   categories{
#      0 = 1
#      1 = 2
#   }
# }
# exclude{
#	categories = 1,2
#   categories{
#      0 = 1
#      1 = 2
#   }
# }
CONF;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtNewsNews.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtNewsNews.php']);
}