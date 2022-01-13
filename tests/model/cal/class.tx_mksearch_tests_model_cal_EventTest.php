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
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen.
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_model_cal_EventTest extends tx_mksearch_tests_Testcase
{
    /**
     * {@inheritdoc}
     *
     * @see tx_mksearch_tests_Testcase::setUp()
     */
    protected function setUp()
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cal')) {
            self::markTestSkipped('cal nicht installiert');
        }

        parent::setUp();
    }

    /**
     * @group integration
     *
     * wir testen im Prinzip nur ob die Query korrekt durchläuft. Damit stellen
     * wir zumindest sicher dass die Tabellen und Felder vorhanden sind
     */
    public function testGetCategoriesByEvent()
    {
        $event = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_cal_Event', ['uid' => 1]);

        self::assertTrue(is_array($this->callInaccessibleMethod($event, 'getCategoriesByEvent')));
    }

    /**
     * group unit.
     */
    public function testGetCategories()
    {
        $event = $this->loadYaml([
            '_model' => 'tx_mksearch_model_cal_Event',
            'getCategoriesByEvent' => [
                0 => ['uid_foreign' => ['title' => 'First Category']],
                1 => ['uid_foreign' => ['title' => 'Second Category']],
            ],
        ]);

        $categories = $event->getCategories();

        self::assertEquals(2, count($categories), 'Mehr Kategorien als erwartet.');
        self::assertInstanceOf('tx_mksearch_model_cal_Category', $categories[0]);
        self::assertEquals(
            'First Category',
            $categories[0]->getTitle(),
            'Titel der 1. Kategorie falsch'
        );
        self::assertInstanceOf('tx_mksearch_model_cal_Category', $categories[1]);
        self::assertEquals(
            'Second Category',
            $categories[1]->getTitle(),
            'Titel der 2. Kategorie falsch'
        );
    }
}
