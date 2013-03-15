<?php
/**
 * 	@package TYPO3
 *  @subpackage tx_mksearch
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2013 Hannes Bochmann <kontakt@das-medienkombinat.de>
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
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * @package TYPO3
 * @subpackage tx_mksearch
 * @author Hannes Bochmann
 */
class tx_mksearch_indexer_Cal extends tx_mksearch_indexer_Base {

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
		return array('cal', 'event');
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_indexer_Base::createModel()
	 */
	protected function createModel(array $rawData) {
		return tx_rnbase::makeInstance('tx_mksearch_model_cal_Event', $rawData);
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_indexer_Base::stopIndexing()
	 */
	protected function stopIndexing(
		$tableName,
		$rawData,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$options
	) {
		if($tableName == 'tx_cal_category') {
			$events = $this->getEventsByCategoryUid($rawData['uid']);
		}else if($tableName == 'tx_cal_calendar') {
			$events = $this->getEventsByCalendarUid($rawData['uid']);
		}
		
		if($events) {
			foreach($events as $event) {
				$this->addEventToIndex($event['uid']);
			}
			return true;
		}
	}
	
	/**
	 * @param int $categoryUid
	 */
	protected function getEventsByCategoryUid($categoryUid) {
		return tx_rnbase_util_DB::doSelect(
			'uid_local as uid',
			'tx_cal_event_category_mm', 
			array(
				//da MM keine TCA hat
				'enablefieldsoff' => true,
				'where'	=> 'uid_foreign = ' . intval($categoryUid)
			)
		);
	}
	
	/**
	 * @param int $calendarUid
	 */
	protected function getEventsByCalendarUid($calendarUid) {
		return tx_rnbase_util_DB::doSelect(
			'uid','tx_cal_event', 
			array(
				'enablefieldsfe' => true,
				'where'	=> 'calendar_id = ' . intval($calendarUid)
			)
		);
	}
	
	/**
	 * @param int $eventUid
	 */
	protected function addEventToIndex($eventUid) {
		static $indexSrv;
		$indexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
		$indexSrv->addRecordToIndex('tx_cal_event', $eventUid);
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	protected function indexData(
		tx_rnbase_model_base $model,
		$tableName,
		$rawData,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$options
	) {
		$this->indexEvent($model, $indexDoc);
		$this->indexCalendar($model, $indexDoc);
		$this->indexCategories($model, $indexDoc);

		return $indexDoc;
	}

	/**
	 * @param tx_mksearch_model_cal_Event $calEvent
	 * @param tx_mksearch_interface_IndexerDocument- $indexDoc
	 *
	 * @return void
	 */
	private function indexEvent(
		tx_mksearch_model_cal_Event $calEvent,
		tx_mksearch_interface_IndexerDocument $indexDoc
	) {
		$calEvent->record['start_date'] = $this->getCalDateStringAsSolrDateTimeString(
			$calEvent->record['start_date'], $calEvent->record['timezone']
		);

		$calEvent->record['end_date'] = $this->getCalDateStringAsSolrDateTimeString(
			$calEvent->record['end_date'], $calEvent->record['timezone']
		);

		$calEvent->record['description'] =
			tx_mksearch_util_Misc::html2plain($calEvent->record['description']);

		$this->indexModelByMapping(
			$calEvent,$this->getEventMapping(),$indexDoc
		);
	}

	/**
	 * @param string $calDateString YYYYMMDD
	 *
	 * @return string
	 */
	private function getCalDateStringAsSolrDateTimeString(
		$calDateString, $timezone = ''
	) {
		if(empty($calDateString)) {
			return;
		}

		$calDateStringWithPossibleTimeZone = $timezone ?
			$calDateString . ' ' . $timezone : $calDateString;
		$calDateAsTimestamp = strtotime($calDateStringWithPossibleTimeZone);
		
		return $this->convertTimestampToDateTime($calDateAsTimestamp);
	}

	/**
	 * @return array
	 */
	private function getEventMapping() {
		return array(
			'start_time' 		=> 'start_time_i',
			'end_time' 			=> 'end_time_i',
			'allday' 			=> 'allday_b',
			'timezone' 			=> 'timezone_s',
			'title' 			=> 'title',
			'organizer' 		=> 'organizer_s',
			'organizer_link' 	=> 'organizer_link_s',
			'location' 			=> 'location_s',
			'type' 				=> 'type_i',
			'start_date'		=> 'start_date_dt',
			'end_date'			=> 'end_date_dt',
			'description'		=> 'description_s'
		);
	}

	/**
	 * @param tx_mksearch_model_cal_Event $calEvent
	 * @param tx_mksearch_interface_IndexerDocument- $indexDoc
	 *
	 * @return void
	 */
	private function indexCalendar(
		tx_mksearch_model_cal_Event $calEvent,
		tx_mksearch_interface_IndexerDocument $indexDoc
	) {
		$this->indexModelByMapping(
			$calEvent->getCalendar(),
			$this->getCalendarMapping(),
			$indexDoc,'calendar_'
		);
	}
	
	/**
	 * @return array
	 */
	private function getCalendarMapping() {
		return array(
			'title' => 'title'
		);
	}
	
	/**
	 * @param tx_mksearch_model_cal_Event $calEvent
	 * @param tx_mksearch_interface_IndexerDocument- $indexDoc
	 *
	 * @return void
	 */
	private function indexCategories(
		tx_mksearch_model_cal_Event $calEvent,
		tx_mksearch_interface_IndexerDocument $indexDoc
	) {
		$this->indexArrayOfModelsByMapping(
			$calEvent->getCategories(),
			$this->getCategoryMapping(),
			$indexDoc,'category_'
		);
	}
	
	
	/**
	 * @return array
	 */
	private function getCategoryMapping() {
		return array(
			'title'	=> 'title_ms'
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_indexer_Base::getDefaultTSConfig()
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
# }
CONFIG;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Cal.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Cal.php']);
}