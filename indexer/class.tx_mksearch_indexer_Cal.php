<?php
/**
 * 	@package TYPO3
 *  @subpackage tx_mksearch
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2013 Hannes Bochmann <dev@dmk-ebusiness.de>
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

tx_rnbase::load('tx_mksearch_indexer_Base');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * @package TYPO3
 * @subpackage tx_mksearch
 * @author Hannes Bochmann
 *
 * @todo Bilder indizieren; alles übrige wie Organisator indizieren
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

		return parent::stopIndexing($tableName, $rawData, $indexDoc, $options);
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
		tx_rnbase_IModel $model,
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
		$calEvent = $this->prepareDates($calEvent);

		$calEvent->record['description'] =
			tx_mksearch_util_Misc::html2plain($calEvent->record['description']);

		$this->indexModelByMapping(
			$calEvent,$this->getEventMapping(),$indexDoc
		);

		$indexDoc->setTimestamp($calEvent->record['start_date_timestamp']);
	}

	/**
	 * @param tx_mksearch_model_cal_Event $calEvent
	 *
	 * @return tx_mksearch_model_cal_Event
	 */
	private function prepareDates(tx_mksearch_model_cal_Event $calEvent) {
		$datePrefixes = array('start', 'end');
		foreach ($datePrefixes as $datePrefix) {
			$calEvent->record[$datePrefix . '_date_timestamp'] = $this->getTimestampFromCalDateString(
				$calEvent->record[$datePrefix . '_date'], $calEvent->record['timezone']
			);
			$calEvent->record[$datePrefix . '_date_datetime'] = $this->getCalDateStringAsSolrDateTimeString(
				$calEvent->record[$datePrefix . '_date'], $calEvent->record['timezone']
			);

			$calDate = tx_rnbase::makeInstance(
				$this->getCalDateClass(), $calEvent->record[$datePrefix . '_date']
			);
			$calEvent->record[$datePrefix . '_date_year'] = $calDate->getYear();
			$calEvent->record[$datePrefix . '_date_month'] = $calDate->getMonth();
			$calEvent->record[$datePrefix . '_date_day'] = $calDate->getDay();
		}

		return $calEvent;
	}

	/**
	 * @return string
	 */
	protected function getCalDateClass() {
		if ($this->isCalInstalledInVersion190OrHigher()) {
			$calDateClass = 'TYPO3\\CMS\\Cal\\Model\\CalDate';
		} else {
			require_once(tx_rnbase_util_Extensions::extPath('cal') . 'model/class.tx_cal_date.php');
			$calDateClass = 'tx_cal_date';
		}
		return $calDateClass;
	}

	/**
	 * Ist cal mind. in Version 1.9.0 installiert?
	 * @return boolean
	 */
	protected function isCalInstalledInVersion190OrHigher() {
		$calVersionNumber = tx_rnbase_util_Extensions::getExtensionVersion('cal');
		return tx_rnbase_util_TYPO3::convertVersionNumberToInteger($calVersionNumber) >= 1009000;
	}

	/**
	 * @param string $calDateString YYYYMMDD
	 * @param string $timezone e.g. UTC
	 *
	 * @return string
	 */
	private function getCalDateStringAsSolrDateTimeString(
		$calDateString, $timezone = ''
	) {
		if(empty($calDateString)) {
			return;
		}

		$calDateAsTimestamp = $this->getTimestampFromCalDateString(
			$calDateString, $timezone
		);

		return $this->convertTimestampToDateTime($calDateAsTimestamp);
	}

	/**
	 * @param string $calDateString YYYYMMDD
	 * @param string $timezone e.g. UTC
	 *
	 * @return int
	 */
	private function getTimestampFromCalDateString(
		$calDateString, $timezone = ''
	) {
		if(empty($calDateString)) {
			return 0;
		}

		$calDateStringWithPossibleTimeZone = $timezone ?
			$calDateString . ' ' . $timezone : $calDateString;
		return strtotime($calDateStringWithPossibleTimeZone);
	}

	/**
	 * @return array
	 */
	private function getEventMapping() {
		return array(
			'start_time' 			=> 'start_time_i',
			'end_time' 				=> 'end_time_i',
			'allday' 				=> 'allday_b',
			'timezone' 				=> 'timezone_s',
			'title' 				=> 'title',
			'organizer' 			=> 'organizer_s',
			'organizer_link' 		=> 'organizer_link_s',
			'location' 				=> 'location_s',
			'type' 					=> 'type_i',
			'start_date'			=> 'start_date_s',
			'end_date'				=> 'end_date_s',
			'start_date_datetime'	=> 'start_date_dt',
			'end_date_datetime'		=> 'end_date_dt',
			'start_date_timestamp'	=> 'start_date_i',
			'end_date_timestamp'	=> 'end_date_i',
			'start_date_year'		=> 'start_date_year_i',
			'start_date_month'		=> 'start_date_month_i',
			'start_date_day'		=> 'start_date_day_i',
			'end_date_year'			=> 'end_date_year_i',
			'end_date_month'		=> 'end_date_month_i',
			'end_date_day'			=> 'end_date_day_i',
			'description'			=> 'description_s'
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
			'title' => 'title_s'
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
### should the Root Page of the current records page be indexed?
# indexSiteRootPage = 0

# you should always configure the root pageTree for this indexer in the includes. mostly the domain
# include.pageTrees {
# 	0 = $pid-of-domain
# }

# should a special workspace be indexed?
# default is the live workspace (ID = 0)
# comma separated list of workspace IDs
#workspaceIds = 1,2,3
CONFIG;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Cal.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Cal.php']);
}
