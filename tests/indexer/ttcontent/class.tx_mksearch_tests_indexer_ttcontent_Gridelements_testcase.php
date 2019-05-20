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

tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_indexer_ttcontent_Gridelements');

/**
 * Gridelements indexer testcase.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_ttcontent_Gridelements_testcase extends tx_mksearch_tests_Testcase
{
    /**
     * setUp() = init DB etc.
     */
    protected function setUp()
    {
        if (!tx_rnbase_util_Extensions::isLoaded('gridelements')) {
            $this->markTestSkipped('Gridelements not installed.');
        }

        parent::setUp();
    }

    /**
     * Test the isGridelement method.
     *
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
                array($indexer, 'isGridelement'),
                array(array('CType' => 'gridelements_pi1'))
            )
        );
        $this->assertFalse(
            $this->callInaccessibleMethod(
                array($indexer, 'isGridelement'),
                array(array('CType' => 'list'))
            )
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForNonGridElement()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(array('CType' => 'list'));

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            array('hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex')
        );
        $indexer
            ->expects($this->once())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->never())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            array($indexer, 'hasDocToBeDeleted'),
            array($ttContentModel, $indexDoc)
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForGridElementAndForcedIndexing()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(
            array(
                'CType' => 'gridelements_pi1',
                'tx_gridelements_container' => 5,
                'tx_mksearch_is_indexable' => 1,
            )
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            array('hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex')
        );
        $indexer
            ->expects($this->once())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->never())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            array($indexer, 'hasDocToBeDeleted'),
            array($ttContentModel, $indexDoc)
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForGridElementAndForcedNoIndexing()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(
            array(
                'CType' => 'gridelements_pi1',
                'tx_gridelements_container' => 5,
                'tx_mksearch_is_indexable' => -1,
            )
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            array('hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex')
        );
        $indexer
            ->expects($this->once())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->never())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            array($indexer, 'hasDocToBeDeleted'),
            array($ttContentModel, $indexDoc)
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForGridElementAndContainer()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(
            array(
                'CType' => 'gridelements_pi1',
                'tx_gridelements_container' => 5,
                'tx_mksearch_is_indexable' => 0,
            )
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            array('hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex')
        );
        $indexer
            ->expects($this->never())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->once())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            array($indexer, 'hasDocToBeDeleted'),
            array($ttContentModel, $indexDoc)
        );
    }

    /**
     * Test the hasDocToBeDeleted method.
     *
     *
     * @group unit
     * @test
     */
    public function testHasDocToBeDeletedForGridElementAndNoContainer()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(
            array(
                'CType' => 'gridelements_pi1',
                'tx_gridelements_container' => 0,
                'tx_mksearch_is_indexable' => 0,
            )
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            array('hasNonGridelementDocToBeDeleted', 'addGridelementsContainerToIndex')
        );
        $indexer
            ->expects($this->once())
            ->method('hasNonGridelementDocToBeDeleted');
        $indexer
            ->expects($this->never())
            ->method('addGridelementsContainerToIndex');

        $this->callInaccessibleMethod(
            array($indexer, 'hasDocToBeDeleted'),
            array($ttContentModel, $indexDoc)
        );
    }

    /**
     * Test the getContentByContentType method.
     *
     *
     * @group unit
     * @test
     */
    public function testGetContentByContentTypeForGridelement()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            array('getGridelementElementContent')
        );
        $indexer
            ->expects($this->once())
            ->method('getGridelementElementContent');

        $this->callInaccessibleMethod(
            array($indexer, 'getContentByContentType'),
            array(array('CType' => 'gridelements_pi1'), array())
        );
    }

    /**
     * Test the getContentByContentType method.
     *
     *
     * @group unit
     * @test
     */
    public function testGetContentByContentTypeForNonGridelement()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            array('getGridelementElementContent')
        );
        $indexer
            ->expects($this->never())
            ->method('getGridelementElementContent');

        $this->callInaccessibleMethod(
            array($indexer, 'getContentByContentType'),
            array(array('CType' => 'list'), array())
        );
    }

    /**
     * Test the getAllowedCTypes method.
     *
     *
     * @group unit
     * @test
     */
    public function testGetAllowedCTypes()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Gridelements',
            array('getGridelementElementContent')
        );
        $expected = array(
            'text', 'textpic', 'gridelements_pi1',
            'text.', 'textpic.', 'gridelements_pi1.',
        );

        $cTypes = $this->callInaccessibleMethod(
            array($indexer, 'getAllowedCTypes'),
            array(array(
                'includeCTypesInGridelementRendering' => 'text,textpic,gridelements_pi1',
            ))
        );
        self::assertEquals($cTypes, $expected);
    }
}
