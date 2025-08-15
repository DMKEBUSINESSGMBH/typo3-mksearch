<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_TtContentTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        self::markTestIncomplete('Error: Call to a member function isConnected() on null');
        // @TODO: ther are db operations. where? fix it!
        $this->prepareLegacyTypo3DbGlobal();
    }

    private function getDefaultOptions(): array
    {
        $options = [];
        $options['CType.']['_default_.']['indexedFields.'] = [
            'bodytext', 'imagecaption', 'altText', 'titleText',
        ];

        return $options;
    }

    #[PHPUnit\Framework\Attributes\DataProvider('getGetTitleData')]
    public function testGetTitle(
        array $record,
        array $options,
        string $expectedTitle,
    ): void {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getModelToIndex', 'getPageContent']
        );

        $record['pid'] = '57';

        $indexer
            ->expects($this->any())
            ->method('getPageContent')
            ->with($this->equalTo('57'))
            ->willReturn(
                ['title' => 'PageTitle']
            );
        $indexer
            ->expects($this->once())
            ->method('getModelToIndex')
            ->willReturn(
                $this->getModel($record)
            );

        $title = $this->callInaccessibleMethod($indexer, 'getTitle', $options);

        $this->assertSame($expectedTitle, $title);
    }

    /**
     * Liefert die Daten für den testGetTitle testcase.
     */
    public static function getGetTitleData(): array
    {
        return [
            // header 100 is hidden, so the title has to be empty with leaveHeaderEmpty option.
            1 => [
                'record' => ['header_layout' => 100, 'header' => 'Test'],
                'options' => ['leaveHeaderEmpty' => true],
                'expected_title' => '',
            ],
            // header 100 is hidden, so the title has to be used from the page.
            2 => [
                'record' => ['header_layout' => 100, 'header' => 'Test'],
                'options' => ['leaveHeaderEmpty' => false],
                'expected_title' => 'PageTitle',
            ],
            // the title of the content element should be used.
            3 => [
                'record' => ['header' => 'Test'],
                'options' => ['leaveHeaderEmpty' => false],
                'expected_title' => 'Test',
            ],
            // the title of the content element is empty, the pagetitle should be used.
            4 => [
                'record' => ['header' => ''],
                'options' => ['leaveHeaderEmpty' => false],
                'expected_title' => 'PageTitle',
            ],
        ];
    }

    public function testPrepareSearchDataCallsPrepareSearchDataOnActualIndexer(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');
        $options = ['options'];
        $record = ['record'];

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        $actualIndexer = $this->getMock('tx_mksearch_indexer_ttcontent_Normal', ['prepareSearchData']);

        $actualIndexer->expects($this->once())
            ->method('prepareSearchData')
            ->with('tt_content', $record, $indexDoc, $options)
            ->willReturn('return');

        $actualIndexerProperty = new ReflectionProperty('tx_mksearch_indexer_TtContent', 'actualIndexer');
        $actualIndexerProperty->setValue($indexer, $actualIndexer);

        self::assertEquals(
            'return',
            $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options)
        );
    }

    public function testPrepareSearchDataCheckIgnoreContentType(): void
    {
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        [$extKey, $cType] = $indexer->getContentType();
        // content type correct?
        self::assertEquals('core', $extKey, 'wrong ext key');
        self::assertEquals('tt_content', $cType, 'wrong cType');

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = ['uid' => 123, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 1, 'CType' => 'list', 'header' => 'test'];
        $options = $this->getDefaultOptions();
        $options['ignoreCTypes.'] = ['search', 'mailform', 'login'];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Null returned for uid '.$record['uid']);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = $this->getDefaultOptions();
        $options['ignoreCTypes.'] = ['search', 'mailform', 'list'];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Not Null returned for uid '.$record['uid']);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = $this->getDefaultOptions();
        $options['ignoreCTypes'] = 'search,mailform,login';
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Null returned for uid '.$record['uid']);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = $this->getDefaultOptions();
        $options['ignoreCTypes'] = 'search,mailform,list';
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Not Null returned for uid '.$record['uid']);
    }

    public function testPrepareSearchDataCheckIncludeContentType(): void
    {
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        [$extKey, $cType] = $indexer->getContentType();

        $record = ['uid' => 123, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 1, 'CType' => 'list', 'header' => 'test'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = $this->getDefaultOptions();
        $options['includeCTypes.'] = ['search', 'mailform', 'login'];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Not Null returned for uid '.$record['uid'].' when CType not in includeCTypes');

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = $this->getDefaultOptions();
        $options['includeCTypes.'] = ['search', 'mailform', 'list'];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Null returned for uid '.$record['uid'].' when CType in includeCTypes');
    }

    public function testGroupFieldIsAddedWithPid(): void
    {
        $record = ['uid' => 123, 'pid' => 456];

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        $actualIndexer = $this->getMock('tx_mksearch_indexer_ttcontent_Normal', ['hasDocToBeDeleted']);

        $actualIndexerProperty = new ReflectionProperty('tx_mksearch_indexer_TtContent', 'actualIndexer');
        $actualIndexerProperty->setValue($indexer, $actualIndexer);

        $indexDoc = $indexer->prepareSearchData('doesnt_matter', $record, $indexDoc, $this->getDefaultOptions());

        $indexedData = $indexDoc->getData();
        self::assertEquals('core:tt_content:456', $indexedData['group_s']->getValue());
    }
}
