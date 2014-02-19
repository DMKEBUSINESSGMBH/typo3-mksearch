<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 das Medienkombinat GmbH
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
tx_rnbase::load('tx_mksearch_tests_Util');

/**
 * @author Hannes Bochmann
 *
 */
class tx_mksearch_tests_indexer_Cal_testcase extends Tx_Phpunit_Testcase {

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		if(!t3lib_extMgm::isLoaded('cal')) {
			$this->markTestSkipped('cal nicht geladen.');
		}
		
		tx_rnbase::load('tx_mksearch_indexer_Cal');
		tx_rnbase::load('tx_mksearch_model_cal_Event');
	}
	
	/**
	 * @group unit
	 */
	public function testPrepareSearchDataReturnsCorrectDocWithFieldsThatAreNotPrepared() {
		$calRecord = array(
			'start_time' 		=> 45660,
			'end_time' 			=> 45720,
			'allday' 			=> 1,
			'timezone' 			=> 'UTC',
			'title' 			=> 'First Event',
			'organizer' 		=> 'John Doe',
			'organizer_link' 	=> 'john.doe.com',
			'location' 			=> 'Springfield',
			'type' 				=> 1
		);

		$indexDocFieldArray =
			$this->getIndexDocFieldArrayByCalRecord($calRecord);

		$this->assertEquals(
			45660,$indexDocFieldArray['start_time_i']->getValue(),
			'start_time falsch indiziert!'
		);
		$this->assertEquals(
			45720,$indexDocFieldArray['end_time_i']->getValue(),
			'end_time falsch indiziert!'
		);
		$this->assertEquals(
			1,$indexDocFieldArray['allday_b']->getValue(),
			'allday falsch indiziert!'
		);
		$this->assertEquals(
			'UTC',$indexDocFieldArray['timezone_s']->getValue(),
			'timezone falsch indiziert!'
		);
		$this->assertEquals(
			'First Event',$indexDocFieldArray['title']->getValue(),
			'title falsch indiziert!'
		);
		$this->assertEquals(
			'John Doe',$indexDocFieldArray['organizer_s']->getValue(),
			'organizer falsch indiziert!'
		);
		$this->assertEquals(
			'john.doe.com',$indexDocFieldArray['organizer_link_s']->getValue(),
			'organizer_link falsch indiziert!'
		);
		$this->assertEquals(
			'Springfield',$indexDocFieldArray['location_s']->getValue(),
			'location falsch indiziert!'
		);
		$this->assertEquals(
			1,$indexDocFieldArray['type_i']->getValue(),
			'type falsch indiziert!'
		);
	}

	/**
	* @group unit
	*/
	public function testPrepareSearchDataReturnsCorrectDocForStartAndEnddateFieldsWithoutTimezone() {
		$calRecord = array(
			'start_date' 	=> '20130303',
			'end_date' 		=> '20130305',
		);

		$indexDocFieldArray =
			$this->getIndexDocFieldArrayByCalRecord($calRecord);

		$this->assertEquals(
			'2013-03-02T23:00:00Z',$indexDocFieldArray['start_date_dt']->getValue(),
			'start_date_dt falsch indiziert!'
		);
		$this->assertEquals(
			'2013-03-04T23:00:00Z',$indexDocFieldArray['end_date_dt']->getValue(),
			'end_date_dt falsch indiziert!'
		);
		$this->assertEquals(
			1362265200,$indexDocFieldArray['start_date_i']->getValue(),
			'start_date_i falsch indiziert!'
		);
		$this->assertEquals(
			1362438000,$indexDocFieldArray['end_date_i']->getValue(),
			'end_date_i falsch indiziert!'
		);
		$this->assertEquals(
			'20130303',$indexDocFieldArray['start_date_s']->getValue(),
			'start_date_s falsch indiziert!'
		);
		$this->assertEquals(
			'20130305',$indexDocFieldArray['end_date_s']->getValue(),
			'end_date_s falsch indiziert!'
		);
		$this->assertEquals(
			2013,$indexDocFieldArray['start_date_year_i']->getValue(),
			'start_date_year_i falsch indiziert!'
		);
		$this->assertEquals(
			3,$indexDocFieldArray['start_date_month_i']->getValue(),
			'start_date_month_i falsch indiziert!'
		);
		$this->assertEquals(
			3,$indexDocFieldArray['start_date_day_i']->getValue(),
			'start_date_day_i falsch indiziert!'
		);
		$this->assertEquals(
			2013,$indexDocFieldArray['end_date_year_i']->getValue(),
			'end_date_year_i falsch indiziert!'
		);
		$this->assertEquals(
			3,$indexDocFieldArray['end_date_month_i']->getValue(),
			'end_date_month_i falsch indiziert!'
		);
		$this->assertEquals(
			5,$indexDocFieldArray['end_date_day_i']->getValue(),
			'end_date_day_i falsch indiziert!'
		);
		$this->assertEquals(
			1362265200,$indexDocFieldArray['tstamp']->getValue(),
			'tstamp nicht gleich start_date_i falsch indiziert!'
		);
	}

	/**
	* @group unit
	*/
	public function testPrepareSearchDataReturnsCorrectDocForStartAndEnddateFieldsWithTimezone() {
		$calRecord = array(
			'start_date' 	=> 20130303,
			'end_date' 		=> 20130305,
			'timezone'		=> 'UTC'
		);

		$indexDocFieldArray =
			$this->getIndexDocFieldArrayByCalRecord($calRecord);

		$this->assertEquals(
			'2013-03-03T00:00:00Z',$indexDocFieldArray['start_date_dt']->getValue(),
			'start_date_dt falsch indiziert!'
		);
		$this->assertEquals(
			'2013-03-05T00:00:00Z',$indexDocFieldArray['end_date_dt']->getValue(),
			'end_date_dt falsch indiziert!'
		);
		$this->assertEquals(
			1362268800,$indexDocFieldArray['start_date_i']->getValue(),
			'start_date_i falsch indiziert!'
		);
		$this->assertEquals(
			1362441600,$indexDocFieldArray['end_date_i']->getValue(),
			'end_date_i falsch indiziert!'
		);
		$this->assertEquals(
			'20130303',$indexDocFieldArray['start_date_s']->getValue(),
			'start_date_s falsch indiziert!'
		);
		$this->assertEquals(
			'20130305',$indexDocFieldArray['end_date_s']->getValue(),
			'end_date_s falsch indiziert!'
		);
		$this->assertEquals(
			2013,$indexDocFieldArray['start_date_year_i']->getValue(),
			'start_date_year_i falsch indiziert!'
		);
		$this->assertEquals(
			3,$indexDocFieldArray['start_date_month_i']->getValue(),
			'start_date_month_i falsch indiziert!'
		);
		$this->assertEquals(
			3,$indexDocFieldArray['start_date_day_i']->getValue(),
			'start_date_day_i falsch indiziert!'
		);
		$this->assertEquals(
			2013,$indexDocFieldArray['end_date_year_i']->getValue(),
			'end_date_year_i falsch indiziert!'
		);
		$this->assertEquals(
			3,$indexDocFieldArray['end_date_month_i']->getValue(),
			'end_date_month_i falsch indiziert!'
		);
		$this->assertEquals(
			5,$indexDocFieldArray['end_date_day_i']->getValue(),
			'end_date_day_i falsch indiziert!'
		);
	}

	/**
	* @group unit
	*/
	public function testPrepareSearchDataRemovesHtmlFromDescriptionField() {
		$calRecord = array(
			'description' 	=> '<p>my description</p>',
		);

		$indexDocFieldArray =
			$this->getIndexDocFieldArrayByCalRecord($calRecord);

		$this->assertEquals(
			'my description',$indexDocFieldArray['description_s']->getValue(),
			'description falsch indiziert!'
		);
	}

	/**
	* @group unit
	*/
	public function testPrepareSearchDataIndexesCalendarTitleCorrect() {
		$calRecord = array(
			'title' => 'First Calendar',
		);

		$calendarModel = tx_rnbase::makeInstance(
			'tx_mksearch_model_cal_Calendar', $calRecord
		);

		$calEventRecord = array(
			'calendar_id' 	=> 1,
		);
		$calendarEventMock = $this->getMock(
			'tx_mksearch_model_cal_Event',
			array('getCalendar'), array($calEventRecord)
		);
		$calendarEventMock->expects($this->once())
			->method('getCalendar')
			->will($this->returnValue($calendarModel));

		$indexer = $this->getMock(
			'tx_mksearch_indexer_Cal', array('createModel')
		);
		$indexer->expects($this->once())
			->method('createModel')
			->will($this->returnValue($calendarEventMock));

		$indexDocFieldArray =
			$this->getIndexDocFieldArrayByCalRecord($calEventRecord, $indexer);

		$this->assertEquals(
			'First Calendar',$indexDocFieldArray['calendar_title_s']->getValue(),
			'calendar_title falsch indiziert!'
		);
	}

	/**
	* @group unit
	*/
	public function testPrepareSearchDataIndexesCategoryTitlesCorrect() {
		$categoryModels = array(
			0 => tx_rnbase::makeInstance(
				'tx_mksearch_model_cal_Category', array('title' => 'First Category')
			),
			1 => tx_rnbase::makeInstance(
				'tx_mksearch_model_cal_Category', array('title' => 'Second Category')
			)
		);

		$calEventRecord = array(
			'category_id' 	=> 1,
		);
		$calendarEventMock = $this->getMock(
			'tx_mksearch_model_cal_Event',
			array('getCategories'), array($calEventRecord)
		);
		$calendarEventMock->expects($this->once())
			->method('getCategories')
			->will($this->returnValue($categoryModels));

		$indexer = $this->getMock(
			'tx_mksearch_indexer_Cal', array('createModel')
		);
		$indexer->expects($this->once())
			->method('createModel')
			->will($this->returnValue($calendarEventMock));

		$indexDocFieldArray =
			$this->getIndexDocFieldArrayByCalRecord($calEventRecord, $indexer);

		$expectedCategoryTitles = array(
			0 => 'First Category',
			1 => 'Second Category'
		);
		$this->assertEquals(
			$expectedCategoryTitles,$indexDocFieldArray['category_title_ms']->getValue(),
			'categoy_title falsch indiziert!'
		);
	}

	/**
	 * @group unit
	 */
	public function testPrepareSearchDataStopsIndexingAndPutsEventsToTheIndexIfTableCalendarHasChanged() {
		$indexer = $this->getMock(
			'tx_mksearch_indexer_Cal',
			array('getEventsByCalendarUid','addEventToIndex')
		);

		$eventsByCalendarUid = array(
			array('uid' => 1),array('uid' => 2)
		);
		$indexer->expects($this->once())
			->method('getEventsByCalendarUid')
			->will($this->returnValue($eventsByCalendarUid));

		$indexer->expects($this->at(1))
			->method('addEventToIndex')
			->with(1);

		$indexer->expects($this->at(2))
			->method('addEventToIndex')
			->with(2);

		$this->assertNull($this->getIndexDocFieldArrayByCalRecord(
				array(), $indexer, 'tx_cal_calendar'
			),
			'Die Indizierung wurde nicht abgebrochen'
		);
	}

	/**
	 * @group unit
	 */
	public function testPrepareSearchDataStopsIndexingAndPutsEventsToTheIndexIfTableCategoryHasChanged() {
		$indexer = $this->getMock(
			'tx_mksearch_indexer_Cal',
			array('getEventsByCategoryUid','addEventToIndex')
		);

		$eventsByCategoryUid = array(
			array('uid' => 1),array('uid' => 2)
		);
		$indexer->expects($this->once())
			->method('getEventsByCategoryUid')
			->will($this->returnValue($eventsByCategoryUid));

		$indexer->expects($this->at(1))
			->method('addEventToIndex')
			->with(1);

		$indexer->expects($this->at(2))
			->method('addEventToIndex')
			->with(2);

		$this->assertNull($this->getIndexDocFieldArrayByCalRecord(
				array(), $indexer, 'tx_cal_category'
			),
			'Die Indizierung wurde nicht abgebrochen'
		);
	}

	/**
	 * @param array $calRecord
	 * @param tx_mksearch_indexer_Cal $indexer
	 * @param string $table
	 *
	 * @return tx_mksearch_model_IndexerDocumentBase
	 */
	private function getIndexDocFieldArrayByCalRecord(
		array $calRecord,
		tx_mksearch_indexer_Cal $indexer = null,
		$table = 'tx_cal_event'
	) {
		if($indexer === null) {
			$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Cal');
		}

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase',$extKey, $cType
		);

		$options = array();
		$indexDoc = $indexer->prepareSearchData(
			$table, $calRecord, $indexDoc, $options
		);

		$return = is_object($indexDoc) ? $indexDoc->getData() : $indexDoc;

		return $return;
	}
}