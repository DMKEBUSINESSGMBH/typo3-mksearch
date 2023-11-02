<?php

namespace DMK\Mksearch\Tests\ViewHelpers;

use DMK\Mksearch\ViewHelpers\CObjectViewHelper;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
 * DMK\Mksearch\Tests\ViewHelpers$CObjectViewHelperTest.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class CObjectViewHelperTest extends \tx_mksearch_tests_Testcase
{
    protected function tearDown(): void
    {
        $property = new \ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, false);
    }

    /**
     * @group unit
     */
    public function testSimulateFrontendEnvironmentWhenMksearchIndexingIsInProgress()
    {
        $property = new \ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, true);

        $GLOBALS['TSFE'] = null;

        $viewHelper = $this->getViewHelper();
        $viewHelper->_call('simulateFrontendEnvironment');
        self::assertNull($GLOBALS['TSFE']);
    }

    /**
     * @group unit
     */
    public function testSimulateFrontendEnvironmentWhenMksearchIndexingIsNotInProgress()
    {
        $property = new \ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, false);

        $GLOBALS['TSFE'] = null;

        $viewHelper = $this->getViewHelper();
        $viewHelper->_call('simulateFrontendEnvironment');
        self::assertInstanceOf('stdCLass', $GLOBALS['TSFE']);
    }

    /**
     * @group unit
     */
    public function testResetFrontendEnvironmentWhenMksearchIndexingIsInProgress()
    {
        $property = new \ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, true);

        $viewHelper = $this->getViewHelper();

        $GLOBALS['TSFE'] = 'test';
        $viewHelper->_call(
            'resetFrontendEnvironment',
            $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock()
        );
        self::assertSame('test', $GLOBALS['TSFE']);
    }

    /**
     * @group unit
     */
    public function testResetFrontendEnvironmentWhenMksearchIndexingIsNotInProgress()
    {
        $property = new \ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, false);

        $GLOBALS['TSFE'] = 'test';

        $viewHelper = $this->getViewHelper();

        $viewHelper->_call(
            'resetFrontendEnvironment',
            $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock()
        );
        self::assertInstanceOf(TypoScriptFrontendController::class, $GLOBALS['TSFE']);
    }

    protected function getViewHelper(): CObjectViewHelper
    {
        return $this->getAccessibleMock(CObjectViewHelper::class, ['render']);
    }
}
