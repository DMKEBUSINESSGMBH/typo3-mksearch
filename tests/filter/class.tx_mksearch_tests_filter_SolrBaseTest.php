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
// damit die User func ausgeführt werden kann, muss sie geladen werden, was auf dem
// CLI und TYPO3 < 4.5 nicht der Fall ist
// im FE geschieht dies durch includeLibs im TS bzw. ab TYPO3 4.5 auch automatisch
// auf dem CLI

/**
 * Testfälle für tx_mksearch_filter_SolrBase.
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_filter_SolrBaseTest extends tx_mksearch_tests_Testcase
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
    public function testInitReturnsFalseIfNothingSubmittedAndNotForced()
    {
        // set noHash as we don't need it in tests
        $config = tx_mksearch_tests_Util::loadPageTS4BE();
        $filter = $this->getFilter($config);

        $fields = [];
        $options = [];
        self::assertFalse($filter->init($fields, $options), 'Filter ist scheinbar doch durchgelaufen!');

        // noch prüfen ob bei submit true zurück gegeben wird
        $this->parameters->offsetSet('submit', true);
        $fields = [];
        $options = [];
        self::assertTrue($filter->init($fields, $options), 'Filter ist scheinbar doch nicht durchgelaufen!');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectRequestHandler()
    {
        $config = $this->getDefaultConfig();
        $filter = $this->getFilter($config);

        $fields = [];
        $options = [];
        $filter->init($fields, $options);
        self::assertEmpty($options['qt'], 'Request Handler scheinbar doch gesetzt!');

        // set noHash as we don't need it in tests
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['requestHandler'] = 'testHandler';
        $filter = $this->getFilter($config);

        $fields = [];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals('testHandler', $options['qt'], 'Request Handler scheinbar doch nicht gesetzt!');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectTerm()
    {
        $config = $this->getDefaultConfig();
        // Test term setzen
        $_GET['mksearch']['term'] = 'test term';
        $filter = $this->getFilter($config);

        $fields = ['term' => 'contentType:* ###PARAM_MKSEARCH_TERM###'];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals('contentType:* AND text:("test" "term")', $fields['term'], 'Request Handler scheinbar doch nicht gesetzt!');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectTermIfTermEmpty()
    {
        $config = $this->getDefaultConfig();
        // Test term setzen
        $_GET['mksearch']['term'] = '';
        $filter = $this->getFilter($config);

        $fields = ['term' => 'contentType:* ###PARAM_MKSEARCH_TERM###'];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals('contentType:* ', $fields['term'], 'Request Handler scheinbar doch nicht gesetzt!');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectTermIfNoTermParamSet()
    {
        $config = $this->getDefaultConfig();
        $filter = $this->getFilter($config);

        $fields = ['term' => 'contentType:* ###PARAM_MKSEARCH_TERM###'];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals('contentType:* ', $fields['term'], 'Request Handler scheinbar doch nicht gesetzt!');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectTermIfTermContainsSolrControlCharacters()
    {
        $config = $this->getDefaultConfig();
        // Test term setzen
        $_GET['mksearch']['term'] = '*';
        $filter = $this->getFilter($config);

        $fields = ['term' => 'contentType:* ###PARAM_MKSEARCH_TERM###'];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals('contentType:* ', $fields['term'], 'Request Handler scheinbar doch nicht gesetzt!');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectFqIfSetAndNoFqFieldDefinedForWrapping()
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

        self::assertEquals([
            0 => '(-fe_group_mi:[* TO *] AND id:[* TO *]) OR fe_group_mi:0',
            1 => 'facet_field:"\"facet value\""',
        ], $options['fq'], 'fq wurde falsch übernommen!');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectFqIfSetAndFqFieldDefinedForWrapping()
    {
        $config = $this->getDefaultConfig();
        // fqField setzen
        $config['searchsolr.']['filter.']['default.']['fqField'] = 'facet_dummy';
        // fq noch setzen
        $this->parameters->offsetSet('fq', '"facet value"');
        $filter = $this->getFilter($config);

        $fields = ['term' => 'contentType:* ###PARAM_MKSEARCH_TERM###'];
        $options = [];
        $filter->init($fields, $options);

        self::assertEquals([
            0 => '(-fe_group_mi:[* TO *] AND id:[* TO *]) OR fe_group_mi:0',
            1 => 'facet_dummy:"\"facet value\""',
        ], $options['fq'], 'fq wuede falsch übernommen!');
    }

    /**
     * @group unit
     */
    public function testAllowedFqParams()
    {
        $config = $this->getDefaultConfig();
        // force noch setzen, das gegenteil wird bereits in testInitSetsCorrectFqIfSetAndNoFqFieldDefinedForWrapping geprüft
        $config['searchsolr.']['filter.']['default.']['allowedFqParams'] = 'allowedfield';

        // fq noch setzen
        $this->parameters->offsetSet('fq', 'field:"facet value"');

        $filter = $this->getFilter($config);

        $fields = ['term' => 'contentType:* ###PARAM_MKSEARCH_TERM###'];
        $options = [];
        $filter->init($fields, $options);

        self::assertEquals('(-fe_group_mi:[* TO *] AND id:[* TO *]) OR fe_group_mi:0', $options['fq'], 'fq wuede gesetzt!');
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
    public function testGetFilterUtility()
    {
        $filter = $this->getFilter();
        $method = new ReflectionMethod('tx_mksearch_filter_SolrBase', 'getFilterUtility');
        $method->setAccessible(true);

        self::assertInstanceOf(
            'tx_mksearch_util_Filter',
            $method->invoke($filter),
            'filter utility falsch'
        );
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectFromParameter()
    {
        $config = $this->getDefaultConfig();

        $this->parameters->offsetSet('sort', 'uid desc');

        $filter = $this->getFilter($config);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid desc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectIfSortOrderAsc()
    {
        $config = $this->getDefaultConfig();

        $this->parameters->offsetSet('sort', 'uid asc');

        $filter = $this->getFilter($config);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid asc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectWithUnknownSortOrder()
    {
        $config = $this->getDefaultConfig();

        $this->parameters->offsetSet('sort', 'uid unknown');

        $filter = $this->getFilter($config);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid asc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectIfSortOrderInSortOrderParameter()
    {
        $config = $this->getDefaultConfig();

        $this->parameters->offsetSet('sort', 'uid');
        $this->parameters->offsetSet('sortorder', 'asc');

        $filter = $this->getFilter($config);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid asc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectIfNoSortOrderUsesClassPropertyForSortOrder()
    {
        $config = $this->getDefaultConfig();

        $this->parameters->offsetSet('sort', 'uid');

        $filter = $this->getFilter($config);

        $filterUtil = $this->getMock('tx_mksearch_util_Filter', ['parseTermTemplate']);

        $order = new ReflectionProperty('tx_mksearch_util_Filter', 'sortOrder');
        $order->setAccessible(true);
        $order->setValue($filterUtil, 'desc');

        $filterUtilProperty = new ReflectionProperty(
            'tx_mksearch_filter_SolrBase',
            'filterUtility'
        );
        $filterUtilProperty->setAccessible(true);
        $filterUtilProperty->setValue($filter, $filterUtil);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid desc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectIfNoSortFieldUsesClassPropertyForSortField()
    {
        $config = $this->getDefaultConfig();

        $filter = $this->getFilter($config);
        $filterUtil = $this->getMock('tx_mksearch_util_Filter', ['parseTermTemplate']);

        $field = new ReflectionProperty('tx_mksearch_util_Filter', 'sortField');
        $field->setAccessible(true);
        $field->setValue($filterUtil, 'uid');

        $filterUtilProperty = new ReflectionProperty(
            'tx_mksearch_filter_SolrBase',
            'filterUtility'
        );
        $filterUtilProperty->setAccessible(true);
        $filterUtilProperty->setValue($filter, $filterUtil);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid asc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testParseTemplateParsesSortMarkerCorrect()
    {
        $this->prepareTSFE();

        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['sort.']['fields'] = 'uid, title';
        $config['searchsolr.']['filter.']['default.']['sort.']['link.']['noHash'] = true;

        $filter = $this->getFilter($config);

        $filterUtil = $this->getMock('tx_mksearch_util_Filter', ['getSortString']);

        $field = new ReflectionProperty('tx_mksearch_util_Filter', 'sortField');
        $field->setAccessible(true);
        $field->setValue($filterUtil, 'uid');

        $order = new ReflectionProperty('tx_mksearch_util_Filter', 'sortOrder');
        $order->setAccessible(true);
        $order->setValue($filterUtil, 'asc');

        $filterUtilProperty = new ReflectionProperty(
            'tx_mksearch_filter_SolrBase',
            'filterUtility'
        );
        $filterUtilProperty->setAccessible(true);
        $filterUtilProperty->setValue($filter, $filterUtil);

        $fields = $options = [];
        $filter->init($fields, $options);

        $method = new ReflectionMethod('tx_mksearch_filter_SolrBase', 'getConfigurations');
        $method->setAccessible(true);
        $formatter = $method->invoke($filter)->getFormatter();

        // eine kleine auswahl der möglichen marker
        $template = '###SORT_UID_ORDER### ###SORT_TITLE_LINKURL###';
        $parsedTemplate = $filter->parseTemplate(
            $template,
            $formatter,
            'searchsolr.filter.default.'
        );

        self::assertRegExp(
            '/(asc \?id=)([a-z0-9]+)(&mksearch%5Bsort%5D=title&mksearch%5Bsortorder%5D=asc)/',
            $parsedTemplate,
            'sort marker falsch geparsed'
        );
    }

    /**
     * @group unit
     */
    public function testInitSetsFacetSortToCountIfNoneAlreadySet()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['facet.']['fields'] = 'somefield';

        $filter = $this->getFilter($config);

        $fields = [];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals('count', $options['facet.sort'], 'facet.sort falsch');
    }

    /**
     * @group unit
     */
    public function testInitSetsFacetSortNotToCountIfOneAlreadySet()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['facet.']['fields'] = 'somefield';

        $filter = $this->getFilter($config);

        $fields = [];
        $options = ['facet.sort' => 'index'];
        $filter->init($fields, $options);
        self::assertEquals('index', $options['facet.sort'], 'facet.sort falsch');
    }

    /**
     * @group unit
     */
    public function testInitSetsFacetSortToConfiguredSortIfNoneAlreadySet()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['facet.']['fields'] = 'somefield';
        $config['searchsolr.']['filter.']['default.']['options.']['facet.']['sort'] = 'index';

        $filter = $this->getFilter($config);

        $fields = [];
        $options = [];
        $filter->init($fields, $options);
        self::assertEquals('index', $options['facet.sort'], 'facet.sort falsch');
    }

    /**
     * @group unit
     */
    public function testInitSetsFacetSortNotToConfiguredSortIfOneAlreadySet()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['facet.']['fields'] = 'somefield';
        $config['searchsolr.']['filter.']['default.']['options.']['facet.']['sort'] = 'index';

        $filter = $this->getFilter($config);

        $fields = [];
        $options = ['facet.sort' => 'something other'];
        $filter->init($fields, $options);
        self::assertEquals('something other', $options['facet.sort'], 'facet.sort falsch');
    }

    /**
     * @group unit
     */
    public function testInitDoesNotConsiderGroupingIfNotEnabled()
    {
        $config = $this->getDefaultConfig();
        $filter = $this->getFilter($config);
        $fields = [];
        $options = [];
        $filter->init($fields, $options);

        self::assertNotEquals('true', $options['group']);
    }

    /**
     * @group unit
     */
    public function testInitSetsGroupingOptionsCorrectIfEnabled()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['enable'] = 1;
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['field'] = 'myField';
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['useNumberOfGroupsAsSearchResultCount'] = false;
        $filter = $this->getFilter($config);
        $fields = [];
        $options = [];
        $filter->init($fields, $options);

        self::assertEquals('true', $options['group']);
        self::assertEquals('myField', $options['group.field']);
        self::assertArrayNotHasKey('group.ngroups', $options);
        self::assertArrayNotHasKey('group.truncate', $options);
    }

    /**
     * @group unit
     */
    public function testInitSetsGroupingOptionsCorrectIfEnabledAndUseNumberOfGroupsAsSearchResultCount()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['enable'] = 1;
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['field'] = 'myField';
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['useNumberOfGroupsAsSearchResultCount'] = true;
        $filter = $this->getFilter($config);
        $fields = [];
        $options = [];
        $filter->init($fields, $options);

        self::assertEquals('true', $options['group']);
        self::assertEquals('myField', $options['group.field']);
        self::assertEquals('true', $options['group.ngroups']);
        self::assertEquals('true', $options['group.truncate']);
    }

    /**
     * @group unit
     */
    public function testInitRemovesGroupConfigIfNotEnabledButSomeOptionsAreSet()
    {
        $config = $this->getDefaultConfig();
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['enable'] = 0;
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['field'] = 'myField';
        $config['searchsolr.']['filter.']['default.']['options.']['group.']['useNumberOfGroupsAsSearchResultCount'] = true;
        $filter = $this->getFilter($config);
        $fields = [];
        $options = [];
        $filter->init($fields, $options);

        self::assertArrayNotHasKey('group', $options);
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
     * @return tx_mksearch_filter_SolrBase
     */
    private function getFilter($config = [])
    {
        $filter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_filter_SolrBase',
            $this->parameters,
            tx_mksearch_tests_Util::loadConfig4BE($config),
            'searchsolr.'
        );

        return $filter;
    }
}
