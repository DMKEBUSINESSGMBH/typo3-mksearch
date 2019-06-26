<?php
/**
 * @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * benötigte Klassen einbinden.
 */
require_once tx_rnbase_util_Extensions::extPath('mksearch').'lib/Apache/Solr/Document.php';

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_marker_Irfaq_testcase extends tx_mksearch_tests_Testcase
{
    /**
     * @var tx_mksearch_marker_Irfaq
     */
    protected $marker;

    /**
     * setUp() = init DB etc.
     */
    protected function setUp()
    {
        $this->prepareTSFE();

        $this->marker = tx_rnbase::makeInstance('tx_mksearch_marker_Irfaq');

        parent::setUp();
    }

    /**
     * @group unit
     */
    public function testParseTemplateRemovesShowFirstCategoryLinkIfFieldEmpty()
    {
        $config = tx_mksearch_tests_Util::loadPageTS4BE();
        $configurations = tx_mksearch_tests_Util::loadConfig4BE($config);
        $formatter = $configurations->getFormatter();

        //now test
        $doc = new Apache_Solr_Document();
        $doc->category_first_shortcut_s = '';

        $item = tx_rnbase::makeInstance('tx_mksearch_model_SolrHit', $doc);

        $template = '###ITEM_SHOWFIRSTCATEGORYLINK###test###ITEM_SHOWFIRSTCATEGORYLINK###';
        $parsedTemplate = $this->marker->parseTemplate(
            $template,
            $item,
            $formatter,
            'searchsolr.hit.extrainfo.default.hit.',
            'ITEM'
        );

        self::assertEquals('', $parsedTemplate, 'Die Marker wurden nicht entfernt!');
    }

    /**
     * @group unit
     */
    public function testParseTemplateRemovesShowFirstCategoryLinkNotIfFieldFilled()
    {
        $config = tx_mksearch_tests_Util::loadPageTS4BE();
        $configurations = tx_mksearch_tests_Util::loadConfig4BE($config);
        $formatter = $configurations->getFormatter();

        //now test
        $doc = new Apache_Solr_Document();
        $doc->category_first_shortcut_s = 'filled';

        $item = tx_rnbase::makeInstance('tx_mksearch_model_SolrHit', $doc);

        $template = '###ITEM_SHOWFIRSTCATEGORYLINK###test###ITEM_SHOWFIRSTCATEGORYLINK###';
        $parsedTemplate = $this->marker->parseTemplate(
            $template,
            $item,
            $formatter,
            'searchsolr.hit.extrainfo.default.hit.',
            'ITEM'
        );

        self::assertEquals(
            '###ITEM_SHOWFIRSTCATEGORYLINK###test###ITEM_SHOWFIRSTCATEGORYLINK###',
            $parsedTemplate,
            'Die Marker wurden nicht entfernt!'
        );
    }
}
