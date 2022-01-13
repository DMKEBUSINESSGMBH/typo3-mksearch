<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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

/**
 * Gridelements indexer testcase.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_ttcontent_GridelementsTest extends tx_mksearch_tests_Testcase
{
    /**
     * setUp() = init DB etc.
     */
    protected function setUp()
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements')) {
            $this->markTestSkipped('Gridelements not installed.');
        }

        parent::setUp();
    }

    /**
     * Test the isGridelement method.
     *
     * @group unit
     * @test
     */
    public function testIsGridelement()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements'
        );

        $this->assertTrue(
            $this->callInaccessibleMethod(
                [$indexer, 'isGridelement'],
                [['CType' => 'gridelements_pi1']]
            )
        );
        $this->assertFalse(
            $this->callInaccessibleMethod(
                [$indexer, 'isGridelement'],
                [['CType' => 'list']]
            )
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForNonGridElement()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(['CType' => 'list']);

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            ['hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex']
        );
        $indexer
            ->expects($this->once())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->never())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            [$indexer, 'hasDocToBeDeleted'],
            [$ttContentModel, $indexDoc]
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForGridElementAndForcedIndexing()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(
            [
                'CType' => 'gridelements_pi1',
                'tx_gridelements_container' => 5,
                'tx_mksearch_is_indexable' => 1,
            ]
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            ['hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex']
        );
        $indexer
            ->expects($this->once())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->never())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            [$indexer, 'hasDocToBeDeleted'],
            [$ttContentModel, $indexDoc]
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForGridElementAndForcedNoIndexing()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(
            [
                'CType' => 'gridelements_pi1',
                'tx_gridelements_container' => 5,
                'tx_mksearch_is_indexable' => -1,
            ]
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            ['hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex']
        );
        $indexer
            ->expects($this->once())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->never())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            [$indexer, 'hasDocToBeDeleted'],
            [$ttContentModel, $indexDoc]
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForGridElementAndContainer()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(
            [
                'CType' => 'gridelements_pi1',
                'tx_gridelements_container' => 5,
                'tx_mksearch_is_indexable' => 0,
            ]
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            ['hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex']
        );
        $indexer
            ->expects($this->never())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->once())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            [$indexer, 'hasDocToBeDeleted'],
            [$ttContentModel, $indexDoc]
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForGridElementAndNoContainer()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(
            [
                'CType' => 'gridelements_pi1',
                'tx_gridelements_container' => 0,
                'tx_mksearch_is_indexable' => 0,
            ]
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            ['hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex']
        );
        $indexer
            ->expects($this->once())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->never())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            [$indexer, 'hasDocToBeDeleted'],
            [$ttContentModel, $indexDoc]
        );
    }

    /**
     * Test the getContentByContentType method.
     *
     * @group unit
     * @test
     */
    public function testGetContentByContentTypeForGridelement()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            ['getGridelementElementContent']
        );
        $indexer
            ->expects($this->once())
            ->method('getGridelementElementContent');

        $this->callInaccessibleMethod(
            [$indexer, 'getContentByContentType'],
            [['CType' => 'gridelements_pi1'], []]
        );
    }

    /**
     * Test the getContentByContentType method.
     *
     * @group unit
     * @test
     */
    public function testGetContentByContentTypeForNonGridelement()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            ['getGridelementElementContent']
        );
        $indexer
            ->expects($this->never())
            ->method('getGridelementElementContent');

        $this->callInaccessibleMethod(
            [$indexer, 'getContentByContentType'],
            [['CType' => 'list'], []]
        );
    }

    /**
     * Test the getAllowedCTypes method.
     *
     * @group unit
     * @test
     */
    public function testGetAllowedCTypes()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            ['getGridelementElementContent']
        );
        $expected = [
            'text', 'textpic', 'gridelements_pi1',
            'text.', 'textpic.', 'gridelements_pi1.',
        ];

        $cTypes = $this->callInaccessibleMethod(
            [$indexer, 'getAllowedCTypes'],
            [[
                'includeCTypesInGridelementRendering' => 'text,textpic,gridelements_pi1',
            ]]
        );
        self::assertEquals($cTypes, $expected);
    }
}
