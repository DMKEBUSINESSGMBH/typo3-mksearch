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

tx_rnbase::load('tx_mksearch_indexer_seminars_Seminar');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * Indexes seminars with the object_type = 0
 * 
 * @package tx_mksearch
 * @subpackage tx_mksearch_indexer_seminars
 */
class tx_mksearch_indexer_seminars_SeminarObjectType1 extends tx_mksearch_indexer_seminars_Seminar {

	/**
	 * All seminar dates which are related to the current topic
	 * @var array
	 */
	protected $aDates;
	
	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	public function prepareSearchData($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		//we have to init the seminar again
		$this->oSeminar = $this->getSeminar($rawData);
		//the organizers, speakers etc are not related to the topic but to the dates
		//so we fetch all dates related to this topic and collect their
		//data
		$this->aDates = $this->getSeminarDatesByTopic();
		if(empty($this->aDates)) return null;//nothing to do as we have no dates
		//else
		
		//now we can start the real indexing
		//those functions are provided by our parent
		$this->indexSeminar($indexDoc);
		$this->indexSeminarCategories($indexDoc);
		$this->indexSeminarTargetGroups($indexDoc);
		//those functions are provided by this class
		$this->indexSeminarOrganizers($indexDoc);
		$this->indexSeminarPlaces($indexDoc);
		$this->indexSeminarSpeakers($indexDoc);
		$this->indexSeminarTimeslots($indexDoc);
		//@todo handle skills of speakers, tutors etc and everything
		//about lodgings, foods and payments
		return $indexDoc;
	}
	
	/**
	 * the begin_date and end_date comes from the dates so we have to fetch them
	 * from there and override the existing (mostly) empty dates
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return void
	 */
	protected function indexSeminar(tx_mksearch_interface_IndexerDocument $indexDoc) {
		//first let the seminar topic be indexed normally
		parent::indexSeminar($indexDoc);
		//the begin_date and end_date comes from our dates so we take them from there
		//and overwrite the existing values
		$aBeginDates = $aEndDates = array();
		foreach ($this->aDates as $oDate){
			$aBeginDates[] = $oDate->getBeginDateAsTimestamp();
			$aEndDates[] = $oDate->getEndDateAsTimestamp();
		}
		if(!empty($aBeginDates))
			$indexDoc->addField('begin_date_ms', $aBeginDates);
		if(!empty($aEndDates))
			$indexDoc->addField('end_date_ms', $aEndDates);
	}
	
	/**
	 * Indexes everything about the seminar target groups
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return void
	 */
	protected function indexSeminarOrganizers(tx_mksearch_interface_IndexerDocument $indexDoc) {
		//Mapping which function fills which field
		$aFunctionFieldMapping = $this->getOrganizersMapping();
		
		//get all organizers by the related dates
		$aOrganizersByDates = $this->getRelatedDataByDates('getOrganizerBag');
		$aTempIndexDocs = $this->getMultiValueFieldsByListObjectArray($aOrganizersByDates,$aFunctionFieldMapping);
		//add fields
		foreach ($aTempIndexDocs as $sIndexKey => $mValue)
			if(!empty($mValue))
				$indexDoc->addField($sIndexKey, $mValue);
	}
	
	/**
	 * Indexes everything about the seminar places
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return void
	 */
	protected function indexSeminarPlaces(tx_mksearch_interface_IndexerDocument $indexDoc) {
		//Mapping which function fills which field
		$aFunctionFieldMapping = $this->getPlacesMapping();
		
		$aPlacesByDates = $this->getRelatedDataByDates('getPlaces');
		
		$aTempIndexDocs = $this->getMultiValueFieldsByListObjectArray($aPlacesByDates,$aFunctionFieldMapping);
		
		//add fields
		foreach ($aTempIndexDocs as $sIndexKey => $mValue)
			if(!empty($mValue))
				$indexDoc->addField($sIndexKey, $mValue);
	}
	
	/**
	 * Indexes everything about the seminar speakers
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return void
	 */
	protected function indexSeminarSpeakers(tx_mksearch_interface_IndexerDocument $indexDoc) {
		//Mapping which function fills which field
		$aFunctionFieldMapping = $this->getSpeakersMapping();
		
		$aSpeakersByDates = array();
		foreach ($this->aDates as $oDate)
			$aSpeakersByDates[] = $this->getSpeakerBag($oDate->getUid());
		
		$aTempIndexDoc = $this->getMultiValueFieldsByListObjectArray($aSpeakersByDates,$aFunctionFieldMapping);
		
		foreach ($aTempIndexDoc as $sIndexKey => $mValue)
			if(!empty($mValue))
				$indexDoc->addField($sIndexKey, $mValue);
	}

	/**
	 * Indexes everything about the seminar timeslots
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return void
	 */
	protected function indexSeminarTimeslots(tx_mksearch_interface_IndexerDocument $indexDoc) {
		//Mapping which function fills which field
		$aRecordFieldMapping = $this->getTimeslotsMapping();
		$aTimeslotsByDate = $this->getRelatedDataByDates('getTimeSlotsAsArrayWithMarkers');
	
		//as the speakers will be a comma separated list we have to make
		//an array out of it
		if(empty($aTimeslotsByDate)) return null;//nothing to do
		//else
		$aMergedTimeslots = array();
		foreach ($aTimeslotsByDate as $aTimeslots){
			foreach ($aTimeslots as &$aTimeslot){
				$aTimeslot['speakers'] = tx_rnbase_util_Strings::trimExplode(',',$aTimeslot['speakers']);
				$aMergedTimeslots[] = $aTimeslot;
			}
		}
		$aTempIndexDoc = $this->getMultiValueFieldsByArray($aMergedTimeslots,$aRecordFieldMapping);
		
		//now we index the collected fields
		$this->indexArrayByMapping($indexDoc, $aRecordFieldMapping, $aTempIndexDoc);
	}
	
	/**
	 * Collects the values from objects. the objects
	 * reside inside an list object. these list objects reside inside an
	 * array
	 * 
	 * @param array $aValues	| array of array of objects
	 * @param array $aMapping 	| the field key and the function name delivering the data
	 * @return array
	 */
	private function getMultiValueFieldsByListObjectArray($aValues, array $aMapping) {
		if(empty($aValues)) return null;//nothing to do
		//else
		$aTempIndexDoc = array();
		//collect the index docs for each organizers by date
		foreach ($aValues as $aValue){
			$aTempIndexDocs[] = $this->getMultiValueFieldsByListObject($aValue,$aMapping);
		}
			
		//nothing found?
		if(empty($aTempIndexDocs)) return null;
		//else
		//now merge the data of the several index docs
		$aNewTempIndexDoc = array();
		foreach ($aTempIndexDocs as $aTempIndexDoc)
			foreach ($aTempIndexDoc as $sKey => $aValue)//value will always be a array as its multi value
				foreach ($aValue as $mValue)
					$aNewTempIndexDoc[$sKey][] = $mValue;
		
		return $aNewTempIndexDoc;
	}
	
	/**
	 * fetches all dates related to this topic
	 * @return array
	 */
	private function getSeminarDatesByTopic() {
		//first of all we collect all seminar uids with the topic
		//we have to take an own method as the seminars extension seems not to provide
		//a method to do that
		$aOptions = array();
		$aOptions['where'] = SEMINARS_TABLE_SEMINARS.'.topic=' . $this->oSeminar->getUid();
		$aFrom = array(SEMINARS_TABLE_SEMINARS, SEMINARS_TABLE_SEMINARS);
		$aRows = tx_rnbase_util_DB::doSelect(SEMINARS_TABLE_SEMINARS . '.uid', $aFrom, $aOptions);
		
		//now we get the according objects
		$aSeminars = array();
		if(!empty($aRows)){
			foreach ($aRows as $aRow)
				$aSeminars[] = $this->getSeminar($aRow);		
		}
		
		return $aSeminars;
	}
	
	/**
	 * Gets data which is related to the dates
	 * for example deliviering all places of the several dates
	 * 
	 * @param string $sFunction
	 * @return array
	 */
	private function getRelatedDataByDates($sFunction) {
		$aRelatedDataByDates = array();
		foreach ($this->aDates as $oDate){
			$aRelatedDataByDates[] = $oDate->$sFunction();	
		}
		
		return $aRelatedDataByDates;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_SeminarObjectType1.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_SeminarObjectType1.php']);
}