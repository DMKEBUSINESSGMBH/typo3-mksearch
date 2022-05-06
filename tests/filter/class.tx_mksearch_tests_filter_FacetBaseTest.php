<?php
/**
 * @author Hannes Bochmann
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
 * benötigte Klassen einbinden.
 */

/**
 * Testfälle für tx_mksearch_filter_FacetBase.
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_filter_FacetBaseTest extends tx_mksearch_tests_Testcase
{
    protected $parameters;
    protected $groupDataBackup;

    protected function setUp(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        parent::setUp();
        $this->parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Frontend\Request\Parameters::class);
        $this->parameters->setQualifier('mksearch');
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_tests_Testcase::tearDown()
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_GET['mksearch']);

        if (isset($GLOBALS['TSFE']->id)) {
            unset($GLOBALS['TSFE']->id);
        }
        if (isset($GLOBALS['TSFE']->rootLine[0]['uid'])) {
            unset($GLOBALS['TSFE']->rootLine[0]['uid']);
        }
    }

    /**
     * @group unit
     */
    public function testInitSetsFilterQueriesFromParametersNot()
    {
        $config = $this->getDefaultConfig();
        // das feld für den fq muss noch erlaubt werden
        $config['searchsolr.']['filter.']['default.']['allowedFqParams'] = 'facet_field';
        // fq noch setzen
        $this->parameters->offsetSet('fq', 'facet_field:"facet value"');
        $filter = $this->getFilter($config);

        $fields = ['term' => 'contentType:* ###PARAM_MKSEARCH_TERM###'];
        $options = [];
        $filter->init($fields, $options);

        self::assertEquals(
            '(-fe_group_mi:[* TO *] AND id:[* TO *]) OR fe_group_mi:0',
            $options['fq'],
            'fq wuede falsch übernommen!'
        );
    }

    /**
     * @group unit
     */
    public function testSettingOfFeGroupsToFilterQuery()
    {
        $tsFeBackup = $GLOBALS['TSFE']->fe_user->groupData['uid'] ?? 0;
        $GLOBALS['TSFE']->fe_user->groupData['uid'] = [1, 2];

        $config = $this->getDefaultConfig();

        $filter = $this->getFilter($config);

        $fields = ['term' => 'contentType:* ###PARAM_MKSEARCH_TERM###'];
        $options = [];
        $filter->init($fields, $options);

        self::assertEquals('(-fe_group_mi:[* TO *] AND id:[* TO *]) OR fe_group_mi:0 OR fe_group_mi:1 OR fe_group_mi:2', $options['fq'], 'fq wuede gesetzt!');

        $GLOBALS['TSFE']->fe_user->groupData['uid'] = $tsFeBackup;
    }

    /**
     * @group unit
     */
    public function testInitSetsLimitToZero()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['limit'] = 10;

        $filter = $this->getFilter($config);

        $fields = [];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals(0, $options['limit'], 'limit nicht 0');
    }

    /**
     * @group unit
     */
    public function testInitSetsFacetToTrue()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['facet'] = 'false';

        $filter = $this->getFilter($config);

        $fields = [];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals('true', $options['facet'], 'facet nicht true');
    }

    /**
     * @return array
     */
    private function getDefaultConfig()
    {
        $config = tx_mksearch_tests_Util::loadPageTS4BE();
        // wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
        $config['searchsolr.']['filter.']['default.'] = $config['lib.']['mksearch.']['defaultsolrfilter.'];
        // force noch setzen
        $config['searchsolr.']['filter.']['default.']['force'] = 1;

        return $config;
    }

    /**
     * @param array $config
     *
     * @return tx_mksearch_filter_FacetBase
     */
    private function getFilter($config = [])
    {
        $filter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_filter_FacetBase',
            $this->parameters,
            tx_mksearch_tests_Util::loadConfig4BE($config),
            'searchsolr.'
        );

        return $filter;
    }
}
