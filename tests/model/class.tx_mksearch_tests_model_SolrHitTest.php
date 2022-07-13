<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 DMK E-Business GmbH
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
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_model_SolrHitTest extends tx_mksearch_tests_Testcase
{
    public function test_getSolrId()
    {
        $doc = new Apache_Solr_Document();
        $doc->id = 'myid';
        $hit = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_SolrHit', $doc);

        self::assertEquals('myid', $hit->getSolrId());
    }
}
