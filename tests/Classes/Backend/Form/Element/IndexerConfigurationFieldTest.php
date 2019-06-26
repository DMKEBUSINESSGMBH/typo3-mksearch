<?php

namespace DMK\Mksearch\Backend\Form\Element;

/**
 *  Copyright notice.
 *
 *  (c) Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * DMK\Mksearch\Backend\Form\Element$IndexerConfigurationFieldTest.
 *
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class IndexerConfigurationFieldTest extends \tx_rnbase_tests_BaseTestCase
{
    /**
     * @group unit
     * @dataProvider dataProviderRenderWhenDefaultValueShouldNotBeSet
     */
    public function testRenderWhenDefaultValueShouldNotBeSet(array $databaseRow)
    {
        $field = $this->getAccessibleMock(
            'DMK\\Mksearch\\Backend\\Form\\Element\\IndexerConfigurationField',
            array('callRenderOnParent'), array(), '', false
        );

        $field->_set('data', array('databaseRow' => $databaseRow));

        $field
            ->expects(self::once())
            ->method('callRenderOnParent')
            ->willReturn('test');

        self::assertEquals('test', $field->render());
        self::assertEmpty($field->_get('data')['parameterArray']['itemFormElValue']);
    }

    /**
     * @return string[][][]
     */
    public function dataProviderRenderWhenDefaultValueShouldNotBeSet()
    {
        return array(
            array(array('configuration' => 'someInfo')),
            array(array('configuration' => 'someInfo', 'extkey' => array(0 => 'core'))),
            array(array('configuration' => 'someInfo', 'contenttype' => array(0 => 'page'))),
            array(array('configuration' => 'someInfo', 'extkey' => array(0 => 'core'), 'contenttype' => array(0 => 'page'))),
            array(array('extkey' => array(0 => 'core'))),
            array(array('contenttype' => array(0 => 'page'))),
            array(array()),
        );
    }

    /**
     * @group unit
     */
    public function testRenderWhenDefaultValueShouldBeSet()
    {
        $field = $this->getAccessibleMock(
            'DMK\\Mksearch\\Backend\\Form\\Element\\IndexerConfigurationField',
            array('callRenderOnParent'), array(), '', false
        );

        $field->_set('data', array('databaseRow' => array(
            'configuration' => '', 'extkey' => array(0 => 'core'), 'contenttype' => array(0 => 'page'),
        )));

        $field
            ->expects(self::once())
            ->method('callRenderOnParent')
            ->willReturn('test');

        self::assertEquals('test', $field->render());
        self::assertNotEmpty($field->_get('data')['parameterArray']['itemFormElValue']);
        self::assertGreaterThan(1, strlen($field->_get('data')['parameterArray']['itemFormElValue']));
        self::assertEquals(
            \tx_rnbase::makeInstance('tx_mksearch_indexer_Page')->getDefaultTSConfig(),
            $field->_get('data')['parameterArray']['itemFormElValue']
        );
    }

    /**
     * @group unit
     */
    public function testRenderWhenDefaultValueShouldBeSetButUnknownExtensionKey()
    {
        $field = $this->getAccessibleMock(
            'DMK\\Mksearch\\Backend\\Form\\Element\\IndexerConfigurationField',
            array('callRenderOnParent'), array(), '', false
        );

        $field->_set('data', array('databaseRow' => array(
            'configuration' => '', 'extkey' => array(0 => 'unknown'), 'contenttype' => array(0 => 'page'),
        )));

        $field
            ->expects(self::once())
            ->method('callRenderOnParent')
            ->willReturn('test');

        self::assertEquals('test', $field->render());
        self::assertEmpty($field->_get('data')['parameterArray']['itemFormElValue']);
    }
}
