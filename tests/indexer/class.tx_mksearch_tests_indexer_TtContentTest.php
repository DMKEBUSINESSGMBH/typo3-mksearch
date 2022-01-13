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

require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'lib/Apache/Solr/Document.php');

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_TtContentTest extends tx_mksearch_tests_Testcase
{
    /**
     * {@inheritdoc}
     *
     * @see tx_mksearch_tests_Testcase::setUp()
     */
    protected function setUp()
    {
        self::markTestIncomplete('Error: Call to a member function isConnected() on null');
        // @TODO: ther are db operations. where? fix it!
        $this->prepareLegacyTypo3DbGlobal();
    }

    private static function getDefaultOptions()
    {
        $options = [];
        $options['CType.']['_default_.']['indexedFields.'] = [
            'bodytext', 'imagecaption', 'altText', 'titleText',
        ];

        return $options;
    }

    /**
     * @param array  $record
     * @param array  $options
     * @param string $expectedTitle
     *
     * @group unit
     * @test
     * @dataProvider getGetTitleData
     */
    public function testGetTitle(
        array $record,
        array $options,
        $expectedTitle
    ) {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getModelToIndex', 'getPageContent']
        );

        $record['pid'] = '57';

        $indexer
            ->expects($this->any())
            ->method('getPageContent')
            ->with($this->equalTo('57'))
            ->will(
                $this->returnValue(
                    ['title' => 'PageTitle']
                )
            );
        $indexer
            ->expects($this->once())
            ->method('getModelToIndex')
            ->will(
                $this->returnValue(
                    $this->getModel($record)
                )
            );

        $title = $this->callInaccessibleMethod($indexer, 'getTitle', $options);

        $this->assertSame($expectedTitle, $title);
    }

    /**
     * Liefert die Daten für den testGetTitle testcase.
     *
     * @return array
     */
    public function getGetTitleData()
    {
        return [
            // header 100 is hidden, so the title has to be empty with leaveHeaderEmpty option.
            __LINE__ => [
                'record' => ['header_layout' => 100, 'header' => 'Test'],
                'options' => ['leaveHeaderEmpty' => true],
                'expected_title' => '',
            ],
            // header 100 is hidden, so the title has to be used from the page.
            __LINE__ => [
                'record' => ['header_layout' => 100, 'header' => 'Test'],
                'options' => ['leaveHeaderEmpty' => false],
                'expected_title' => 'PageTitle',
            ],
            // the title of the content element should be used.
            __LINE__ => [
                'record' => ['header' => 'Test'],
                'options' => ['leaveHeaderEmpty' => false],
                'expected_title' => 'Test',
            ],
            // the title of the content element is empty, the pagetitle should be used.
            __LINE__ => [
                'record' => ['header' => ''],
                'options' => ['leaveHeaderEmpty' => false],
                'expected_title' => 'PageTitle',
            ],
        ];
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataCallsPrepareSearchDataOnActualIndexer()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');
        $options = ['options'];
        $record = ['record'];

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        $actualIndexer = $this->getMock('tx_mksearch_indexer_ttcontent_Normal', ['prepareSearchData']);

        $actualIndexer->expects($this->once())
            ->method('prepareSearchData')
            ->with('tt_content', $record, $indexDoc, $options)
            ->will($this->returnValue('return'));

        $actualIndexerProperty = new ReflectionProperty('tx_mksearch_indexer_TtContent', 'actualIndexer');
        $actualIndexerProperty->setAccessible(true);
        $actualIndexerProperty->setValue($indexer, $actualIndexer);

        self::assertEquals(
            'return',
            $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options)
        );
    }

    /**
     * @group unit
     */
    public function test_prepareSearchData_CheckIgnoreContentType()
    {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        //content type correct?
        self::assertEquals('core', $extKey, 'wrong ext key');
        self::assertEquals('tt_content', $cType, 'wrong cType');

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = ['uid' => 123, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 1, 'CType' => 'list', 'header' => 'test'];
        $options = self::getDefaultOptions();
        $options['ignoreCTypes.'] = ['search', 'mailform', 'login'];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Null returned for uid '.$record['uid']);

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = self::getDefaultOptions();
        $options['ignoreCTypes.'] = ['search', 'mailform', 'list'];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Not Null returned for uid '.$record['uid']);

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = self::getDefaultOptions();
        $options['ignoreCTypes'] = 'search,mailform,login';
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Null returned for uid '.$record['uid']);

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = self::getDefaultOptions();
        $options['ignoreCTypes'] = 'search,mailform,list';
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Not Null returned for uid '.$record['uid']);
    }

    /**
     * @group unit
     */
    public function test_prepareSearchData_CheckIncludeContentType()
    {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 123, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 1, 'CType' => 'list', 'header' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = self::getDefaultOptions();
        $options['includeCTypes.'] = ['search', 'mailform', 'login'];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Not Null returned for uid '.$record['uid'].' when CType not in includeCTypes');

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = self::getDefaultOptions();
        $options['includeCTypes.'] = ['search', 'mailform', 'list'];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Null returned for uid '.$record['uid'].' when CType in includeCTypes');
    }

    /**
     * @group unit
     */
    public function testGroupFieldIsAddedWithPid()
    {
        $record = ['uid' => 123, 'pid' => 456];

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        $actualIndexer = $this->getMock('tx_mksearch_indexer_ttcontent_Normal', ['hasDocToBeDeleted']);

        $actualIndexerProperty = new ReflectionProperty('tx_mksearch_indexer_TtContent', 'actualIndexer');
        $actualIndexerProperty->setAccessible(true);
        $actualIndexerProperty->setValue($indexer, $actualIndexer);

        $indexDoc = $indexer->prepareSearchData('doesnt_matter', $record, $indexDoc, self::getDefaultOptions());

        $indexedData = $indexDoc->getData();
        self::assertEquals('core:tt_content:456', $indexedData['group_s']->getValue());
    }
}
