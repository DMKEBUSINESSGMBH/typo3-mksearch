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
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_marker_Facet');

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_marker_Facet_testcase extends tx_mksearch_tests_Testcase
{
    /**
     * Enter description here ...
     *
     * @var tx_mksearch_marker_Facet
     */
    protected $oMarker;

    /**
     * setUp() = init DB etc.
     */
    protected function setUp()
    {
        $this->prepareTSFE();

        $this->oParameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $this->oMarker = tx_rnbase::makeInstance('tx_mksearch_marker_Facet');
        parent::setUp();
    }

    /**
     * prüfen ob die richtigen fields und options zurück gegeben werden.
     */
    public function testPrepareLinks()
    {
        //set noHash as we don't need it in tests
        $aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
        $aConfig['searchsolr.']['facet.']['links.']['show.']['noHash'] = 1;
        $this->oConfig = tx_mksearch_tests_Util::loadConfig4BE($aConfig);
        $this->oFormatter = $this->oConfig->getFormatter();

        //now test
        $oItem = tx_rnbase::makeInstance('tx_mksearch_model_Facet', 'contentType', 'media', 'media', 2);
        $sTemplate = '###FACET_SHOWLINK######FACET_LABEL### (###FACET_COUNT###)###FACET_SHOWLINK###';
        $sParsedTemplate = $this->oMarker->parseTemplate($sTemplate, $oItem, $this->oFormatter, 'searchsolr.facet.', 'FACET');
        //Feld noch im Link drin?
        $expectedParsedTemplate = '/(\<a href="\?id=)([a-z0-9]+)(&amp;mksearch%5Bfq%5D=contentType%3Amedia" \>media \(2\)\<\/a\>)/';
        $expectedParsedTemplate = str_replace('" \>', '"\>', $expectedParsedTemplate);
        self::assertRegExp(
            $expectedParsedTemplate,
            $sParsedTemplate,
            'Die Filter Query wurde nicht korrekt gesetzt!'
        );
    }

    /**
     * prüfen ob die richtigen fields und options zurück gegeben werden.
     */
    public function testPrepareLinksWithExcludeFieldName()
    {
        //set noHash as we don't need it in tests
        $aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
        $aConfig['searchsolr.']['facet.']['links.']['show.']['noHash'] = 1;
        $aConfig['searchsolr.']['facet.']['links.']['show.']['excludeFieldName'] = 1;
        $this->oConfig = tx_mksearch_tests_Util::loadConfig4BE($aConfig);
        $this->oFormatter = $this->oConfig->getFormatter();

        $oItem = tx_rnbase::makeInstance('tx_mksearch_model_Facet', 'contentType', 'media', 'media', 2);
        $sTemplate = '###FACET_SHOWLINK######FACET_LABEL### (###FACET_COUNT###)###FACET_SHOWLINK###';
        $sParsedTemplate = $this->oMarker->parseTemplate($sTemplate, $oItem, $this->oFormatter, 'searchsolr.facet.', 'FACET');
        //Feld noch im Link drin?
        $expectedParsedTemplate = '/(\<a href="\?id=)([a-z0-9]+)(&amp;mksearch%5Bfq%5D=media" \>media \(2\)\<\/a\>)/';
        $expectedParsedTemplate = str_replace('" \>', '"\>', $expectedParsedTemplate);
        self::assertRegExp(
            $expectedParsedTemplate,
            $sParsedTemplate,
            'In der Filter Query steht noch immer der Feldname!'
        );
    }

    /**
     * prüfen ob die paramater url enkodiert werden.
     */
    public function testPrepareLinksWithFacetIdContainingSeveralWordsAndUmlauts()
    {
        //set noHash as we don't need it in tests
        $aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
        $aConfig['searchsolr.']['facet.']['links.']['show.']['noHash'] = 1;
        $aConfig['searchsolr.']['facet.']['links.']['show.']['excludeFieldName'] = 1;
        $this->oConfig = tx_mksearch_tests_Util::loadConfig4BE($aConfig);
        $this->oFormatter = $this->oConfig->getFormatter();

        $oItem = tx_rnbase::makeInstance('tx_mksearch_model_Facet', 'contentType', 'Über uns', 'Über uns', 2);
        $sTemplate = '###FACET_SHOWLINK######FACET_LABEL### (###FACET_COUNT###)###FACET_SHOWLINK###';
        $sParsedTemplate = $this->oMarker->parseTemplate($sTemplate, $oItem, $this->oFormatter, 'searchsolr.facet.', 'FACET');
        //Feld noch im Link drin?
        $expectedParsedTemplate = '/(\<a href="\?id=)([a-z0-9]+)(&amp;mksearch%5Bfq%5D=%C3%9Cber%20uns" \>Über uns \(2\)\<\/a\>)/';
        $expectedParsedTemplate = str_replace('" \>', '"\>', $expectedParsedTemplate);
        self::assertRegExp(
            $expectedParsedTemplate,
            $sParsedTemplate,
            'In der Filter Query steht noch immer der Feldname!'
        );
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_model_SolrHit_testcase.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_model_SolrHit_testcase.php'];
}
