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

namespace DMK\Mksearch\Backend\Form\Element;

use PHPUnit\Framework\Attributes\DataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * DMK\Mksearch\Backend\Form\Element$IndexerConfigurationFieldTest.
 *
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class IndexerConfigurationFieldTest extends UnitTestCase
{
    #[DataProvider('dataProviderRenderWhenDefaultValueShouldNotBeSet')]
    public function testRenderWhenDefaultValueShouldNotBeSet(array $databaseRow): void
    {
        $field = $this->getAccessibleMock(
            IndexerConfigurationField::class,
            ['callRenderOnParent'], [], '', false
        );

        $field->_set('data', ['databaseRow' => $databaseRow]);

        $field
            ->expects(self::once())
            ->method('callRenderOnParent')
            ->willReturn(['test']);

        self::assertEquals(['test'], $field->render());
        self::assertArrayNotHasKey('parameterArray', $field->_get('data'));
    }

    /**
     * @return string[][][]
     */
    public static function dataProviderRenderWhenDefaultValueShouldNotBeSet(): array
    {
        return [
            [['configuration' => 'someInfo']],
            [['configuration' => 'someInfo', 'extkey' => [0 => 'core']]],
            [['configuration' => 'someInfo', 'contenttype' => [0 => 'page']]],
            [['configuration' => 'someInfo', 'extkey' => [0 => 'core'], 'contenttype' => [0 => 'page']]],
            [['extkey' => [0 => 'core']]],
            [['contenttype' => [0 => 'page']]],
            [[]],
        ];
    }

    public function testRenderWhenDefaultValueShouldBeSet(): void
    {
        \tx_mksearch_util_Config::registerIndexer(
            'core',
            'page',
            'tx_mksearch_indexer_Page',
            [
                'pages',
                // @todo handle page overlay
                'pages_language_overlay',
            ]
        );

        $field = $this->getAccessibleMock(
            IndexerConfigurationField::class,
            ['callRenderOnParent'], [], '', false
        );

        $field->_set('data', ['databaseRow' => [
            'configuration' => '', 'extkey' => [0 => 'core'], 'contenttype' => [0 => 'page'],
        ]]);

        $field
            ->expects(self::once())
            ->method('callRenderOnParent')
            ->willReturn(['test']);

        self::assertEquals(['test'], $field->render());
        self::assertNotEmpty($field->_get('data')['parameterArray']['itemFormElValue']);
        self::assertGreaterThan(1, strlen($field->_get('data')['parameterArray']['itemFormElValue']));
        self::assertEquals(
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_Page')->getDefaultTSConfig(),
            $field->_get('data')['parameterArray']['itemFormElValue']
        );
    }

    public function testRenderWhenDefaultValueShouldBeSetButUnknownExtensionKey(): void
    {
        $field = $this->getAccessibleMock(
            IndexerConfigurationField::class,
            ['callRenderOnParent'], [], '', false
        );

        $field->_set('data', ['databaseRow' => [
            'configuration' => '', 'extkey' => [0 => 'unknown'], 'contenttype' => [0 => 'page'],
        ]]);

        $field
            ->expects(self::once())
            ->method('callRenderOnParent')
            ->willReturn(['test']);

        self::assertEquals(['test'], $field->render());
        self::assertEmpty($field->_get('data')['parameterArray']['itemFormElValue']);
    }
}
