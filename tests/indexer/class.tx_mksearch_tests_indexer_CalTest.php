<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 DMK E-Business GmbH
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
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_CalTest extends tx_mksearch_tests_Testcase
{
    /**
     * (non-PHPdoc).
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cal')) {
            $this->markTestSkipped('cal nicht geladen.');
        }
        parent::setUp();
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataReturnsCorrectDocWithFieldsThatAreNotPrepared()
    {
        $calRecord = [
            'start_time' => 45660,
            'end_time' => 45720,
            'allday' => 1,
            'timezone' => 'UTC',
            'title' => 'First Event',
            'organizer' => 'John Doe',
            'organizer_link' => 'john.doe.com',
            'location' => 'Springfield',
            'type' => 1,
        ];

        $indexDocFieldArray =
            $this->getIndexDocFieldArrayByCalRecord($calRecord);

        self::assertEquals(
            45660,
            $indexDocFieldArray['start_time_i']->getValue(),
            'start_time falsch indiziert!'
        );
        self::assertEquals(
            45720,
            $indexDocFieldArray['end_time_i']->getValue(),
            'end_time falsch indiziert!'
        );
        self::assertEquals(
            1,
            $indexDocFieldArray['allday_b']->getValue(),
            'allday falsch indiziert!'
        );
        self::assertEquals(
            'UTC',
            $indexDocFieldArray['timezone_s']->getValue(),
            'timezone falsch indiziert!'
        );
        self::assertEquals(
            'First Event',
            $indexDocFieldArray['title']->getValue(),
            'title falsch indiziert!'
        );
        self::assertEquals(
            'John Doe',
            $indexDocFieldArray['organizer_s']->getValue(),
            'organizer falsch indiziert!'
        );
        self::assertEquals(
            'john.doe.com',
            $indexDocFieldArray['organizer_link_s']->getValue(),
            'organizer_link falsch indiziert!'
        );
        self::assertEquals(
            'Springfield',
            $indexDocFieldArray['location_s']->getValue(),
            'location falsch indiziert!'
        );
        self::assertEquals(
            1,
            $indexDocFieldArray['type_i']->getValue(),
            'type falsch indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataReturnsCorrectDocForStartAndEnddateFieldsWithoutTimezone()
    {
        $calRecord = [
            'start_date' => '20130303',
            'end_date' => '20130305',
        ];

        $indexDocFieldArray =
            $this->getIndexDocFieldArrayByCalRecord($calRecord);

        self::assertEquals(
            '2013-03-02T23:00:00Z',
            $indexDocFieldArray['start_date_dt']->getValue(),
            'start_date_dt falsch indiziert!'
        );
        self::assertEquals(
            '2013-03-04T23:00:00Z',
            $indexDocFieldArray['end_date_dt']->getValue(),
            'end_date_dt falsch indiziert!'
        );
        self::assertEquals(
            1362265200,
            $indexDocFieldArray['start_date_i']->getValue(),
            'start_date_i falsch indiziert!'
        );
        self::assertEquals(
            1362438000,
            $indexDocFieldArray['end_date_i']->getValue(),
            'end_date_i falsch indiziert!'
        );
        self::assertEquals(
            '20130303',
            $indexDocFieldArray['start_date_s']->getValue(),
            'start_date_s falsch indiziert!'
        );
        self::assertEquals(
            '20130305',
            $indexDocFieldArray['end_date_s']->getValue(),
            'end_date_s falsch indiziert!'
        );
        self::assertEquals(
            2013,
            $indexDocFieldArray['start_date_year_i']->getValue(),
            'start_date_year_i falsch indiziert!'
        );
        self::assertEquals(
            3,
            $indexDocFieldArray['start_date_month_i']->getValue(),
            'start_date_month_i falsch indiziert!'
        );
        self::assertEquals(
            3,
            $indexDocFieldArray['start_date_day_i']->getValue(),
            'start_date_day_i falsch indiziert!'
        );
        self::assertEquals(
            2013,
            $indexDocFieldArray['end_date_year_i']->getValue(),
            'end_date_year_i falsch indiziert!'
        );
        self::assertEquals(
            3,
            $indexDocFieldArray['end_date_month_i']->getValue(),
            'end_date_month_i falsch indiziert!'
        );
        self::assertEquals(
            5,
            $indexDocFieldArray['end_date_day_i']->getValue(),
            'end_date_day_i falsch indiziert!'
        );
        self::assertEquals(
            1362265200,
            $indexDocFieldArray['tstamp']->getValue(),
            'tstamp nicht gleich start_date_i falsch indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataReturnsCorrectDocForStartAndEnddateFieldsWithTimezone()
    {
        $calRecord = [
            'start_date' => 20130303,
            'end_date' => 20130305,
            'timezone' => 'UTC',
        ];

        $indexDocFieldArray =
            $this->getIndexDocFieldArrayByCalRecord($calRecord);

        self::assertEquals(
            '2013-03-03T00:00:00Z',
            $indexDocFieldArray['start_date_dt']->getValue(),
            'start_date_dt falsch indiziert!'
        );
        self::assertEquals(
            '2013-03-05T00:00:00Z',
            $indexDocFieldArray['end_date_dt']->getValue(),
            'end_date_dt falsch indiziert!'
        );
        self::assertEquals(
            1362268800,
            $indexDocFieldArray['start_date_i']->getValue(),
            'start_date_i falsch indiziert!'
        );
        self::assertEquals(
            1362441600,
            $indexDocFieldArray['end_date_i']->getValue(),
            'end_date_i falsch indiziert!'
        );
        self::assertEquals(
            '20130303',
            $indexDocFieldArray['start_date_s']->getValue(),
            'start_date_s falsch indiziert!'
        );
        self::assertEquals(
            '20130305',
            $indexDocFieldArray['end_date_s']->getValue(),
            'end_date_s falsch indiziert!'
        );
        self::assertEquals(
            2013,
            $indexDocFieldArray['start_date_year_i']->getValue(),
            'start_date_year_i falsch indiziert!'
        );
        self::assertEquals(
            3,
            $indexDocFieldArray['start_date_month_i']->getValue(),
            'start_date_month_i falsch indiziert!'
        );
        self::assertEquals(
            3,
            $indexDocFieldArray['start_date_day_i']->getValue(),
            'start_date_day_i falsch indiziert!'
        );
        self::assertEquals(
            2013,
            $indexDocFieldArray['end_date_year_i']->getValue(),
            'end_date_year_i falsch indiziert!'
        );
        self::assertEquals(
            3,
            $indexDocFieldArray['end_date_month_i']->getValue(),
            'end_date_month_i falsch indiziert!'
        );
        self::assertEquals(
            5,
            $indexDocFieldArray['end_date_day_i']->getValue(),
            'end_date_day_i falsch indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataRemovesHtmlFromDescriptionField()
    {
        $calRecord = [
            'description' => '<p>my description</p>',
        ];

        $indexDocFieldArray =
            $this->getIndexDocFieldArrayByCalRecord($calRecord);

        self::assertEquals(
            'my description',
            $indexDocFieldArray['description_s']->getValue(),
            'description falsch indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataIndexesCalendarTitleCorrect()
    {
        $calRecord = [
            'title' => 'First Calendar',
        ];

        $calendarModel = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_cal_Calendar',
            $calRecord
        );

        $calEventRecord = [
            'calendar_id' => 1,
        ];
        $calendarEventMock = $this->getMock(
            'tx_mksearch_model_cal_Event',
            ['getCalendar'],
            [$calEventRecord]
        );
        $calendarEventMock->expects($this->once())
            ->method('getCalendar')
            ->will($this->returnValue($calendarModel));

        $indexer = $this->getMock(
            'tx_mksearch_indexer_Cal',
            ['createModel']
        );
        $indexer->expects($this->once())
            ->method('createModel')
            ->will($this->returnValue($calendarEventMock));

        $indexDocFieldArray =
            $this->getIndexDocFieldArrayByCalRecord($calEventRecord, $indexer);

        self::assertEquals(
            'First Calendar',
            $indexDocFieldArray['calendar_title_s']->getValue(),
            'calendar_title falsch indiziert!'
        );
    }

    /**
     * @return array
     */
    private function getIndexDocFieldArrayForCategoryTests()
    {
        $categoryModels = [
            0 => \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'tx_mksearch_model_cal_Category',
                ['uid' => 3, 'title' => 'First Category']
            ),
            1 => \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'tx_mksearch_model_cal_Category',
                ['uid' => 2, 'title' => 'Second Category']
            ),
        ];

        $calEventRecord = [
            'category_id' => 2,
        ];
        $calendarEventMock = $this->getMock(
            'tx_mksearch_model_cal_Event',
            ['getCategories'],
            [$calEventRecord]
        );
        $calendarEventMock->expects($this->once())
            ->method('getCategories')
            ->will($this->returnValue($categoryModels));

        $indexer = $this->getMock(
            'tx_mksearch_indexer_Cal',
            ['createModel']
        );
        $indexer->expects($this->once())
            ->method('createModel')
            ->will($this->returnValue($calendarEventMock));

        $indexDocFieldArray =
            $this->getIndexDocFieldArrayByCalRecord($calEventRecord, $indexer);

        return $indexDocFieldArray;
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataIndexesCategoryUidsCorrect()
    {
        $indexDocFieldArray = $this->getIndexDocFieldArrayForCategoryTests();
        $expectedCategoryUids = [
            0 => 3,
            1 => 2,
        ];
        self::assertEquals(
            $expectedCategoryUids,
            $indexDocFieldArray['category_uid_mi']->getValue(),
            'categoy_title falsch indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataIndexesCategoryTitlesCorrect()
    {
        $indexDocFieldArray = $this->getIndexDocFieldArrayForCategoryTests();

        $expectedCategoryTitles = [
            0 => 'First Category',
            1 => 'Second Category',
        ];
        self::assertEquals(
            $expectedCategoryTitles,
            $indexDocFieldArray['category_title_ms']->getValue(),
            'categoy_title falsch indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataStopsIndexingAndPutsEventsToTheIndexIfTableCalendarHasChanged()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_Cal',
            ['getEventsByCalendarUid', 'addEventToIndex']
        );

        $eventsByCalendarUid = [
            ['uid' => 1], ['uid' => 2],
        ];
        $indexer->expects($this->once())
            ->method('getEventsByCalendarUid')
            ->will($this->returnValue($eventsByCalendarUid));

        $indexer->expects($this->at(1))
            ->method('addEventToIndex')
            ->with(1);

        $indexer->expects($this->at(2))
            ->method('addEventToIndex')
            ->with(2);

        self::assertNull(
            $this->getIndexDocFieldArrayByCalRecord(
                [],
                $indexer,
                'tx_cal_calendar'
            ),
            'Die Indizierung wurde nicht abgebrochen'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataStopsIndexingAndPutsEventsToTheIndexIfTableCategoryHasChanged()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_Cal',
            ['getEventsByCategoryUid', 'addEventToIndex']
        );

        $eventsByCategoryUid = [
            ['uid' => 1], ['uid' => 2],
        ];
        $indexer->expects($this->once())
            ->method('getEventsByCategoryUid')
            ->will($this->returnValue($eventsByCategoryUid));

        $indexer->expects($this->at(1))
            ->method('addEventToIndex')
            ->with(1);

        $indexer->expects($this->at(2))
            ->method('addEventToIndex')
            ->with(2);

        self::assertNull(
            $this->getIndexDocFieldArrayByCalRecord(
                [],
                $indexer,
                'tx_cal_category'
            ),
            'Die Indizierung wurde nicht abgebrochen'
        );
    }

    /**
     * @param array                   $calRecord
     * @param tx_mksearch_indexer_Cal $indexer
     * @param string                  $table
     *
     * @return tx_mksearch_model_IndexerDocumentBase
     */
    private function getIndexDocFieldArrayByCalRecord(
        array $calRecord,
        tx_mksearch_indexer_Cal $indexer = null,
        $table = 'tx_cal_event'
    ) {
        if (null === $indexer) {
            $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_Cal');
        }

        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            $extKey,
            $cType
        );

        $options = [];
        $indexDoc = $indexer->prepareSearchData(
            $table,
            $calRecord,
            $indexDoc,
            $options
        );

        $return = is_object($indexDoc) ? $indexDoc->getData() : $indexDoc;

        return $return;
    }
}
