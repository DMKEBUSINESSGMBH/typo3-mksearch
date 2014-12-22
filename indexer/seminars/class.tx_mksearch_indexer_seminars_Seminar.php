<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_indexer_seminars
 *  @author Hannes Bochmann
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
 * benötigte Klassen einbinden
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_interface_Indexer');
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_mksearch_util_Indexer');

/**
 * Indexer service for seminars.seminar called by the "mksearch" extension.
 * This is just a wrapper for the different object types of seminars. This
 * class doesnt do the actual indexing. this happens in the classes which are
 * responsible for the diffenrent types
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_indexer_seminars
 */
class tx_mksearch_indexer_seminars_Seminar implements tx_mksearch_interface_Indexer {
	// In seminars wurden die Konstanten bei getSpeakerBag entfernt. Zur Sicherheit mal hier angelegt.
	const SEMINARS_TABLE_SEMINARS_SPEAKERS_MM = 'tx_seminars_seminars_speakers_mm';
	const SEMINARS_TABLE_SEMINARS_PARTNERS_MM = 'tx_seminars_seminars_speakers_mm_partners';
	const SEMINARS_TABLE_SEMINARS_TUTORS_MM = 'tx_seminars_seminars_speakers_mm_tutors';
	const SEMINARS_TABLE_SEMINARS_LEADERS_MM = 'tx_seminars_seminars_speakers_mm_leaders';
	const SEMINARS_TABLE_SPEAKERS = 'tx_seminars_speakers';

	
	/**
	 * Our seminar object
	 * @var tx_seminars_seminar
	 */
	protected $oSeminar;

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
		return array('seminars', 'seminar');
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	public function prepareSearchData($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		//was a related table changed?
		if($tableName == 'tx_seminars_categories') {
			$this->handleRelatedMmChanged(SEMINARS_TABLE_SEMINARS_CATEGORIES_MM,$rawData);
			return null;
		}elseif($tableName == 'tx_seminars_organizers') {
			$this->handleRelatedMmChanged(SEMINARS_TABLE_SEMINARS_ORGANIZERS_MM,$rawData);
			return null;
		}elseif($tableName == 'tx_seminars_sites') {
			$this->handleRelatedMmChanged(SEMINARS_TABLE_SEMINARS_SITES_MM,$rawData);
			return null;
		}elseif($tableName == 'tx_seminars_speakers') {
			$this->handleRelatedMmChanged(SEMINARS_TABLE_SEMINARS_SPEAKERS_MM,$rawData);
			return null;
		}elseif($tableName == 'tx_seminars_target_groups') {
			$this->handleRelatedMmChanged(SEMINARS_TABLE_SEMINARS_TARGET_GROUPS_MM,$rawData);
			return null;
		}elseif($tableName == 'tx_seminars_timeslots') {
			//we have no mm for timeslots
			$this->addSeminarToIndex($rawData['seminar']);
			return null;
		}

		//seminar indexable?
		if(!$this->isIndexableRecord($rawData, $options)) {
			return null;
		}
		//we seem to have a real seminar so let's go on with the work

		//init the seminar object to do common checks
		//@todo load the TS config or simulate the FE so we have the correct timeFormat settings etc.
		$this->oSeminar = $this->getSeminar($rawData);
		//set it's uid
		$indexDoc->setUid($this->oSeminar->getUid());
		//and check if it's valid at all
		//@todo if related data is deleted/hidden, handle this!
		if($rawData['deleted'] || $rawData['hidden']) {
			$indexDoc->setDeleted(true);
			return $indexDoc;
		}

		//redirect the indexing to the responsible class
		if($rawData['object_type'] == 0){
			$oIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_SeminarObjectType0');
		}
		elseif($rawData['object_type'] == 1){
			$oIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_SeminarObjectType1');
		}
		//we dont need to index a event date as this happens when we index
		//a event topic
		elseif($rawData['object_type'] == 2){
			$this->addSeminarToIndex($rawData['topic']);
			return null;
		}

		return $oIndexer->prepareSearchData($tableName, $rawData, $indexDoc, $options);
	}

	/**
	 * Collects all seminar uids found via an MM Relation (given table)
	 * and the given foreign uid (e.g. of a category). All seminars that
	 * are found will be put into the queue
	 *
	 * @param string $sTable	| the MM Table
	 * @param array $aRawData	| the record (non seminar; e.g. category) containing the foreign uid
	 * @return void
	 */
	private function handleRelatedMmChanged($sTable, $aRawData) {
		//collect all seminar uids for the given related record
		//for example all seminars with the given category or target group
		$aSeminarUids = tx_oelib_db::selectColumnForMultiple(
			'uid_local',
			$sTable,
			'uid_foreign = '.$aRawData['uid']
		);
		//if not empty put everything into the queue
		if(!empty($aSeminarUids))
			foreach ($aSeminarUids as $iUid)
				$this->addSeminarToIndex($iUid);
	}

	/**
	 * Indexes everything about the seminar
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return void
	 */
	protected function indexSeminar(tx_mksearch_interface_IndexerDocument $indexDoc) {
		//Mapping which function fills which field
		$aFunctionFieldMapping = array(
			'title_s'=> $this->oSeminar->getTitle(),
			'subtitle_s'=> $this->oSeminar->getSubtitle(),
			'realtitle_s'=> $this->oSeminar->getRealTitle(),
			'description_s'=> $this->oSeminar->getDescription(),
			'date_s'=> $this->oSeminar->getDate(),
			'accreditationnumber_s'=> $this->oSeminar->getAccreditationNumber(),
			'credit_points_s'=> $this->oSeminar->getCreditPoints(),
			'currentpriceregular_s'=> $this->oSeminar->getCurrentPriceRegular(),
			'time_s'=> $this->oSeminar->getTime(),
			'titleanddate_s'=> $this->oSeminar->getTitleAndDate(),
			//as this field will be multi value for object_type = 1
			//we set it to multi value eventhough it will
			//never happen for object_type = 0
			'begin_date_ms'=> array(0 => $this->oSeminar->getBeginDateAsTimestamp()),
			'end_date_ms'=> array(0 => $this->oSeminar->getEndDateAsTimestamp()),
		);
		foreach ($aFunctionFieldMapping as $sKey => $mValue){
			if(!empty($mValue))
				$indexDoc->addField($sKey, $mValue);
		}
	}

	/**
	 * Indexes everything about the seminar categories
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return void
	 */
	protected function indexSeminarCategories(tx_mksearch_interface_IndexerDocument $indexDoc) {
		//Mapping which function fills which field
		$aCategories = $this->oSeminar->getCategories();
		$aRecordFieldMapping = $this->getCategoriesMapping();

		$aTempIndexDoc = $this->getMultiValueFieldsByArray($aCategories,$aRecordFieldMapping);

		//now we index the collected fields
		$this->indexArrayByMapping($indexDoc, $aRecordFieldMapping, $aTempIndexDoc);
	}

	/**
	 * Indexes everything about the seminar organizers
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return void
	 */
	protected function indexSeminarTargetGroups(tx_mksearch_interface_IndexerDocument $indexDoc) {
		//Mapping which function fills which field
		$aTargetGroups = $this->oSeminar->getTargetGroupsAsArray();
		if(!empty($aTargetGroups))
			$indexDoc->addField('targetgroups_title_ms', $aTargetGroups);
	}

	/**
	 * Puts a seminar into the mksearch queue
	 * @param int $iUid
	 */
	protected function addSeminarToIndex($iUid){
		$oIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
		$oIndexSrv->addRecordToIndex('tx_seminars_seminars', $iUid);
	}

	/**
	 * Collects the values from objects. the objects
	 * reside inside an list object
	 *
	 * @param array $aValues	| array of objects
	 * @param array $aMapping 	| the field key and the function name delivering the data
	 * @return array
	 */
	protected function getMultiValueFieldsByListObject($aValues, array $aMapping) {
		$aTempIndexDoc = array();
		//as this is a multivalue field we collect all
		//information from the given categories
		foreach ($aValues as $oValue){
			foreach ($aMapping as $sFunction => $mIndexKey){
				$mValue = $oValue->$sFunction();
				if(!empty($mValue)){
					$sIndexKey = is_array($mIndexKey) ? $mIndexKey['indexfield'] : $mIndexKey;
					// $mValue kann ein Object sein, dann kann in mIndexKey der Getter konfiguriert werden
					if(is_object($mValue) && is_array($mIndexKey)) {
						$getter = $mIndexKey['getter'];
						$mValue = $mValue->$getter();
					}
					$aTempIndexDoc[$sIndexKey][] = $mValue;
				}
			}
		}

		return $aTempIndexDoc;
	}

	/**
	 * Collects the values from an array
	 *
	 * @param array $aValues
	 * @param array $aMapping
	 */
	protected function getMultiValueFieldsByArray($aValues, array $aMapping) {
		$aTempIndexDoc = array();
		//as this is a multivalue field we collect all
		//information from the given arrays
		foreach ($aValues as $aValue){
			foreach ($aMapping as $sRecordKey => $sIndexKey){
				//we take the value as key in the resulting array as we dont want doubled values
				if(!empty($aValue[$sRecordKey])){
					//handling of one dimensional arrays
					if(is_array($aValue[$sRecordKey])){
						foreach ($aValue[$sRecordKey] as $key => $mValue){
							$aTempIndexDoc[$sIndexKey][$mValue] = $mValue;
						}
					}else{//just a value so put it in
						$aTempIndexDoc[$sIndexKey][$aValue[$sRecordKey]] = $aValue[$sRecordKey];
					}
				}
			}
		}

		//as result we want a simple numeric array
		foreach ($aTempIndexDoc as &$aValue)
			$aValue = array_values($aValue);

		return $aTempIndexDoc;
	}

	/**
	 * Indexes the given array by the mapping
	 *
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $aMapping
	 * @param array $aValues
	 */
	protected function indexArrayByMapping(tx_mksearch_interface_IndexerDocument $indexDoc, array $aMapping, array $aValues) {
		foreach ($aMapping as $sRecordKey => $sIndexKey){
			if(!empty($aValues[$sIndexKey]))
				$indexDoc->addField($sIndexKey, $aValues[$sIndexKey]);
		}
	}

	/**
	 * Prüft anhand der Konfiguration, ob der übergebene Datensatz indiziert werden soll.
	 * TODO: implement
	 * @param array $sourceRecord
	 * @param array $options
	 */
	protected function isIndexableRecord($sourceRecord, $options) {
		$ret = tx_mksearch_util_Indexer::getInstance()
					->isOnIndexablePage($sourceRecord, $options);
		return $ret;
	}

	/**
	 * Liefert das Seminar Objekt zur gegebenen Uid
	 * @param array $rawData
	 * @return tx_seminars_seminar
	 */
	protected function getSeminar($rawData) {
		return new tx_seminars_seminar($rawData['uid']);
	}


	/**
	 * This method is directly taken from tx_seminars_seminar. We have no
	 * other possibility than to copy it to get the speakers bag as all methods
	 * delivering them are either protected or private.
	 *
	 * @param string the relation in which the speakers stand to this event:
	 *               "speakers" (default), "partners", "tutors" or "leaders"
	 *
	 * @return tx_seminars_speakerbag a speakerbag object
	 */
	protected function getSpeakerBag($uid, $speakerRelation = 'speakers') {
		switch ($speakerRelation) {
			case 'partners':
				$mmTable = self::SEMINARS_TABLE_SEMINARS_PARTNERS_MM;
				break;
			case 'tutors':
				$mmTable = self::SEMINARS_TABLE_SEMINARS_TUTORS_MM;
				break;
			case 'leaders':
				$mmTable = self::SEMINARS_TABLE_SEMINARS_LEADERS_MM;
				break;
			case 'speakers':
				// The fallthrough is intended.
			default:
				$mmTable = self::SEMINARS_TABLE_SEMINARS_SPEAKERS_MM;
				break;
		}
		if(tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
			return t3lib_div::makeInstance(
				'tx_seminars_Bag_Speaker',
				$mmTable . '.uid_local = ' . $uid . ' AND ' . 'tx_seminars_speakers.uid = ' . $mmTable . '.uid_foreign',
				$mmTable,
				'sorting'
			);
		}
		else {
			return tx_oelib_ObjectFactory::make(
					'tx_seminars_speakerbag',
					$mmTable . '.uid_local = ' . $uid . ' AND ' .
					self::SEMINARS_TABLE_SPEAKERS . '.uid = ' . $mmTable . '.uid_foreign',
					$mmTable);
						
		}
	}

	/**
	 * Provides the mapping for the fields in Solr
	 * and the fields in the category object
	 * @return array
	 */
	protected function getCategoriesMapping() {
		return array(
			'title'=> 'categories_title_ms',
		);
	}

	/**
	 * Provides the mapping for the fields in Solr
	 * and the functions of the organizer object delivering
	 * the data
	 * @return array
	 */
	protected function getOrganizersMapping() {
		return array(
			'getTitle' => 'organizers_title_ms',
			'getHomepage' => 'organizers_homepage_ms',
			'getEMailAddress' => 'organizers_email_ms',
			'getDescription' => 'organizers_description_ms',
		);
	}

	/**
	 * Provides the mapping for the fields in Solr
	 * and the functions of the places object delivering
	 * the data
	 * @return array
	 */
	protected function getPlacesMapping() {
		return array(
			'getAddress' => 'place_adress_ms',
			'getCity' => 'place_city_ms',
			// Das Land wird ggf. als Tx_Oelib_Model_Country geliefert
			'getCountry' => array('indexfield' => 'place_country_ms', 'getter' => 'getLocalShortName' ),
			'getDirections' => 'place_directions_ms',
			'getHomepage' => 'place_homepage_ms',
			'getNotes' => 'place_notes_ms',
			'getOwner' => 'place_owner_ms',
			'getTitle' => 'place_title_ms',
		);
	}

	/**
	 * Provides the mapping for the fields in Solr
	 * and the functions of the speakers object delivering
	 * the data
	 * @return array
	 */
	protected function getSpeakersMapping() {
		return array(
			'getTitle' => 'speakers_title_ms',
			'getGender' => 'speakers_gender_ms',
			'getOrganization' => 'speakers_organization_ms',
			'getHomepage' => 'speakers_homepage_ms',
			'getNotes' => 'speakers_notes_ms',
			'getAddress' => 'speakers_address_ms',
			'getPhoneWork' => 'speakers_phone_work_ms',
			'getPhoneHome' => 'speakers_phone_home_ms',
			'getPhoneMobile' => 'speakers_phone_mobile_ms',
			'getFax' => 'speakers_fax_ms',
			'getEmail' => 'speakers_email_ms',
		);
	}

	/**
	 * Provides the mapping for the fields in Solr
	 * and the fields in the timeslots object
	 * @return array
	 */
	protected function getTimeslotsMapping() {
		return array(
			'title'=> 'timeslots_title_ms',
			'time'=> 'timeslots_time_ms',
			'entry_date'=> 'timeslots_entry_date_ms',
			'room'=> 'timeslots_room_ms',
			'place'=> 'timeslots_place_ms',
			'speakers'=> 'timeslots_speakers_ms',
		);
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
		return <<<LH
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
#
# you should always configure the root pageTree for this indexer in the includes. mostly the domain
# include.pageTrees {
# 	0 = $pid-of-domain
# }

LH;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php']);
}