<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2015 DMK E-BUSINESS GmbH <kontakt@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_indexer_A21Glossary');

/**
 * tx_mksearch_tests_indexer_A21Glossary.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_A21Glossary_testcase extends tx_mksearch_tests_Testcase
{
    /**
     * @return tx_mksearch_indexer_A21Glossary|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getIndexerMock()
    {
        $mock = $this->getMock(
            'tx_mksearch_indexer_A21Glossary',
            array('stopIndexing', 'isIndexableRecord', 'hasDocToBeDeleted')
        );
        $mock
            ->expects($this->any())
            ->method('stopIndexing')
            ->will($this->returnValue(false));
        $mock
            ->expects($this->any())
            ->method('isIndexableRecord')
            ->will($this->returnValue(true));
        $mock
            ->expects($this->any())
            ->method('hasDocToBeDeleted')
            ->will($this->returnValue(true));

        return $mock;
    }

    /**
     * Testet die getContentType Methode.
     *
     * @group unit
     * @test
     */
    public function testGetContentType()
    {
        $ct = $this->getIndexerMock()->getContentType();
        self::assertTrue(is_array($ct));
        list($extKey, $contentType) = $ct;
        self::assertSame($extKey, 'a21glossary');
        self::assertSame($contentType, 'main');
    }

    /**
     * Testet die getContentType Methode.
     *
     * @group unit
     * @test
     */
    public function testIndexData()
    {
        $indexer = $this->getIndexerMock();
        $model = $this->getModel(
            array(
                    'uid' => 57,
                    'short' => 'Titel',
                    'shortcut' => 'Alternative',
                    'longversion' => 'Long',
                    'description' => 'Beschreibung',
                )
        )
            ->setTableName('tx_a21glossary_main');
        /* @var $indexDoc tx_mksearch_model_IndexerDocumentBase */
        $indexDoc = $this->getIndexDocMock($indexer);
        $indexDoc->setUid($model->getUid());
        $return = $this->callInaccessibleMethod(
            $indexer,
            'indexData',
            $model,
            'tx_a21glossary_main',
            $model->getRecord(),
            $indexDoc,
            array()
        );

        self::assertInstanceOf(get_class($indexDoc), $return);
        self::assertSame('a21glossary:main:57', $indexDoc->getPrimaryKey(true));

        // check default fields (title, content, abstract)
        // longversion is the caption of the glossar entry
        self::assertIndexDocHasField($indexDoc, 'title', 'Long');
        // description contains the explanation of the glossary word (short)
        self::assertIndexDocHasField($indexDoc, 'content', 'Beschreibung');
        self::assertIndexDocHasField($indexDoc, 'abstract', 'Beschreibung');

        // check mapped fields
        self::assertIndexDocHasField($indexDoc, 'short_s', 'Titel');
        self::assertIndexDocHasField($indexDoc, 'shortcut_s', 'Alternative');
        self::assertIndexDocHasField($indexDoc, 'longversion_s', 'Long');
        self::assertIndexDocHasField($indexDoc, 'description_t', 'Beschreibung');
    }
}
