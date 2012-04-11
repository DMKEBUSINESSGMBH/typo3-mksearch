<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_indexer
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
 * benötigte Klassen einbinden
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_indexer_Base');

/**
 * Indexer service for irfaq.question called by the "mksearch" extension.
 * 
 * 
 * @package tx_mksearch
 * @subpackage tx_mksearch_indexer
 */
class tx_mksearch_indexer_Irfaq extends tx_mksearch_indexer_Base {
	
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
		return array('irfaq', 'question');
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	public function indexData(tx_rnbase_model_base $oModel, $sTableName, $aRawData, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions) {
		//check if at least one category of the current faq
		//is in the given include categories if this option is used
		//we could also extend the isIndexableRecord method but than
		//we would need to get the faq model and the categories
		//twice
		$aCategories = tx_mksearch_util_ServiceRegistry::getIrfaqCategoryService()->getByQuestion($oModel);
		if(!$this->checkInOrExcludeOptions($aCategories,$aOptions))
			return null;
		//else go one with indexing
		
		//index everything about the categories
		$this->indexArrayOfModelsByMapping(
			$aCategories,
			$this->getCategoryMapping(),
			$oIndexDoc,'category_'
		);
			
		// index everything about the question
		$this->indexModelByMapping($oModel,$this->getQuestionMapping(),$oIndexDoc);
		
		//index everything about the expert
		$this->indexModelByMapping(
			tx_mksearch_util_ServiceRegistry::getIrfaqExpertService()->get($oModel->record['expert']),
			$this->getExpertMapping(),
			$oIndexDoc,'expert_'
		);
		
		//done
		return $oIndexDoc;
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
		if($sTableName == 'tx_irfaq_expert') {
			$this->handleRelatedTableChanged($aRawData,'Expert');
			return true;
		}else if($sTableName == 'tx_irfaq_cat') {
			$this->handleRelatedTableChanged($aRawData,'Category');
			return true;
		}
	}
	
	/**
	 * Adds all given models to the queue
	 * @param array $aModels
	 */
	protected function handleRelatedTableChanged(array $aRawData, $sCallback) {
		$oSrv = tx_mksearch_util_ServiceRegistry::getIrfaqQuestionService();
		$sCallback = 'getBy'.$sCallback;
		
		$this->addModelsToIndex(
				$oSrv->$sCallback($aRawData['uid']),
				'tx_irfaq_q'
			);
	}
	
	/**
	 * Returns the mapping of the record fields to the
	 * solr doc fields
	 * @return array
	 */
	protected function getQuestionMapping() {
		return array(
			'sorting' => 'sorting_i',
			'q' => 'q_s',
			'a' => 'a_s',
			'related' => 'related_s',
			'related_links' => 'related_links_s',
			'faq_files' => 'faq_files_s',
		);
	}
	
	/**
	 * Returns the mapping of the record fields to the
	 * solr doc fields
	 * @return array
	 */
	protected function getExpertMapping() {
		return array(
			'uid' => 'i',
			'name' => 'name_s',
			'email' => 'email_s',
			'url' => 'url_s',
		);
	}
	
	/**
	 * Returns the mapping of the record fields to the
	 * solr doc fields
	 * @return array
	 */
	protected function getCategoryMapping() {
		return array(
			'uid' => 'mi',
			'sorting' => 'sorting_mi',
			'title' => 'title_ms',
			'shortcut' => 'shortcut_ms',
		);
	}
	
	/**
	 * Returns the model to be indexed
	 * 
	 * @param array $aRawData
	 * 
	 * @return tx_mksearch_model_irfaq_Question
	 */
	protected function createModel(array $aRawData) {
		return tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Question', $aRawData);
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
	public function getDefaultTSConfig() {
		return <<<CONFIG
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
# include{
#	as array
#	categories{
#		0 = 1
#		1 = 23
#	}
#	or as string
#	categories = 1,23
# }
CONFIG;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php']);
}