<?php

namespace DMK\Mksearch\Tests;

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
class XClassesTest extends \tx_mksearch_tests_Testcase
{
    /**
     * @group unit
     */
    public function testFluidCObjViewHelperIsXClassed()
    {
        self::assertInstanceOf(
            'DMK\\Mksearch\\ViewHelpers\\CObjectViewHelper',
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\ViewHelpers\\CObjectViewHelper')
        );
    }

    /**
     * @group unit
     */
    public function testFluidHtmlViewHelperIsXClassed()
    {
        self::assertInstanceOf(
            'DMK\\Mksearch\\ViewHelpers\\Format\\HtmlViewHelper',
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlViewHelper')
        );
    }
}
