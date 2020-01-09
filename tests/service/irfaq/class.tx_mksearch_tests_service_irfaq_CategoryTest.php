<?php
/**
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * tx_mksearch_tests_service_irfaq_CategoryTest.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_service_irfaq_CategoryTest extends tx_mksearch_tests_Testcase
{
    /**
     * {@inheritdoc}
     *
     * @see tx_mksearch_tests_Testcase::setUp()
     */
    protected function setUp()
    {
        if (!tx_rnbase_util_Extensions::isLoaded('irfaq')) {
            self::markTestSkipped('irfaq nicht installiert');
        }

        parent::setUp();
    }

    /**
     * @group unit
     */
    public function testGetByQuestion()
    {
        $question = tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Question', ['uid' => 123]);
        $service = $this->getMock('tx_mksearch_service_irfaq_Category', ['search']);
        $service->expects(self::once())
            ->method('search')
            ->with(['IRFAQ_QUESTION_CATEGORY_MM.uid_local' => [OP_EQ_INT => 123]], []);

        $service->getByQuestion($question);
    }

    /**
     * Damit testen wir nur ob der Datenbankzugriff keine Fehler verursacht.
     *
     * @group integration
     */
    public function testGetByQuestionThrowsNoErrors()
    {
        tx_mksearch_util_ServiceRegistry::getIrfaqCategoryService()->getByQuestion(
            tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Question', ['uid' => 123])
        );
    }

    /**
     * @group unit
     */
    public function testGetSearchClass()
    {
        self::assertEquals('tx_mksearch_search_irfaq_Category', tx_mksearch_util_ServiceRegistry::getIrfaqCategoryService()->getSearchClass());
    }
}
