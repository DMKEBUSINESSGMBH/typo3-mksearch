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
 * benötigte Klassen einbinden.
 */

//damit die User func ausgeführt werden kann, muss sie geladen werden, was auf dem
//CLI und TYPO3 < 4.5 nicht der Fall ist
//im FE geschieht dies durch includeLibs im TS bzw. ab TYPO3 4.5 auch automatisch
//auf dem CLI

/**
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
 */
class tx_mksearch_tests_filter_ElasticSearchBaseTest extends tx_mksearch_tests_Testcase
{
    /**
     * @var string
     */
    private $confId = 'elasticsearch.';

    /**
     * @group uinit
     */
    public function testPrepareFormFieldsSetsDefaultFieldsIfNotInParameters()
    {
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $formData = [];
        $reflectionObject = new ReflectionObject($this->getFilter());
        $reflectionMethod = $reflectionObject->getMethod('prepareFormFields');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invokeArgs(
            $this->getFilter(),
            [&$formData, $parameters]
        );

        self::assertArrayHasKey('zip', $formData, 'zip nicht vorhanden in formdata');
        self::assertArrayHasKey('city', $formData, 'city nicht vorhanden in formdata');
        self::assertArrayHasKey('company', $formData, 'company nicht vorhanden in formdata');
        self::assertEquals('', $formData['zip'], 'zip nicht leer in formdata');
        self::assertEquals('', $formData['city'], 'city nicht leer in formdata');
        self::assertEquals('', $formData['company'], 'company nicht leer in formdata');
    }

    /**
     * @group uinit
     */
    public function testPrepareFormFieldsSetsDefaultFieldsNotIfAlreadyInFormData()
    {
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $formData = ['zip' => 1, 'city' => 2, 'company' => 3];
        $reflectionObject = new ReflectionObject($this->getFilter());
        $reflectionMethod = $reflectionObject->getMethod('prepareFormFields');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invokeArgs(
            $this->getFilter(),
            [&$formData, $parameters]
        );

        self::assertEquals(1, $formData['zip'], 'zip leer in formdata');
        self::assertEquals(2, $formData['city'], 'city leer in formdata');
        self::assertEquals(3, $formData['company'], 'company leer in formdata');
    }

    /**
     * @group uinit
     */
    public function testGetModeValuesAvailable()
    {
        $configArray = [$this->confId => [
            'filter.' => ['availableModes' => 'newCheckedMode,newNotCheckedMode'], ],
        ];
        $filter = $this->getFilter($configArray);

        self::assertEquals(
            ['newCheckedMode', 'newNotCheckedMode'],
            $this->callInaccessibleMethod($filter, 'getModeValuesAvailable'),
            'return falsch'
        );
    }

    /**
     * @group uinit
     */
    public function testPrepareFormFieldsSetsCorrectModeChecked()
    {
        $_GET['mksearch']['options']['mode'] = 'newCheckedMode';
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $parameters->init('mksearch');
        $formData = [];
        $configArray = [$this->confId => [
            'filter.' => ['availableModes' => 'newCheckedMode,newNotCheckedMode'], ],
        ];
        $filter = $this->getFilter($configArray);
        $reflectionObject = new ReflectionObject($filter);
        $reflectionMethod = $reflectionObject->getMethod('prepareFormFields');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invokeArgs($filter, [&$formData, $parameters]);

        self::assertEquals(
            'checked=checked',
            $formData['mode_newCheckedMode_selected'],
            'mode_newCheckedMode_selected nicht selected'
        );
        self::assertEquals(
            '',
            $formData['mode_newNotCheckedMode_selected'],
            'mode_newNotCheckedMode_selected nicht selected'
        );
    }

    /**
     * @group uinit
     */
    public function testPrepareFormFieldsSetsStandardModeCheckedAsDefault()
    {
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $formData = [];
        $configArray = [$this->confId => ['filter.' => ['availableModes' => 'standard,advanced']]];
        $filter = $this->getFilter($configArray);
        $reflectionObject = new ReflectionObject($filter);
        $reflectionMethod = $reflectionObject->getMethod('prepareFormFields');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invokeArgs($filter, [&$formData, $parameters]);

        self::assertEquals(
            'checked=checked',
            $formData['mode_standard_selected'],
            'mode_standard_selected nicht selected'
        );
        self::assertEquals(
            '',
            $formData['mode_advanced_selected'],
            'mode_advanced_selected doch selected'
        );
    }

    /**
     * @group unit
     */
    public function testInitReturnsFalseIfNoSubmit()
    {
        $configArray = [$this->confId => ['filter.' => ['forceSearch' => false]]];

        $filter = $this->getFilter($configArray);
        $fields = $options = [];
        self::assertFalse($filter->init($fields, $options), 'filter liefert nicht false');
    }

    /**
     * @group unit
     */
    public function testInitReturnsTrueIfNoSubmitButForceSearch()
    {
        $configArray = [$this->confId => ['filter.' => ['forceSearch' => true]]];

        $filter = $this->getFilter($configArray);
        $fields = $options = [];
        self::assertTrue($filter->init($fields, $options), 'filter liefert nicht true');
    }

    /**
     * @group unit
     */
    public function testInitReturnsTrueIfSubmit()
    {
        $configArray = [$this->confId => ['filter.' => ['forceSearch' => false]]];

        $filter = $this->getFilter($configArray, ['submit' => true]);
        $fields = $options = [];
        self::assertTrue($filter->init($fields, $options), 'filter liefert nicht true');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectPlainTerm()
    {
        $_GET['mksearch']['term'] = 'test term';

        $fields = [];
        $options = [];
        $configArray = [
            $this->confId => [
                'filter.' => [
                    'fields.' => [
                        'term' => 'contentType:* AND text:"###PARAM_MKSEARCH_TERM###"',
                    ],
                ],
            ],
        ];
        $filter = $this->getFilter($configArray);
        $filter->init($fields, $options);
        self::assertEquals(
            'contentType:* AND text:"test term"',
            $fields['term'],
            'term template falsch geparsed!'
        );
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectTermIfTermEmpty()
    {
        $_GET['mksearch']['term'] = '';

        $fields = [];
        $options = [];
        $configArray = [
            $this->confId => [
                'filter.' => [
                    'fields.' => [
                        'term' => 'contentType:* ###PARAM_MKSEARCH_TERM###',
                    ],
                ],
            ],
        ];
        $filter = $this->getFilter($configArray);
        $filter->init($fields, $options);
        self::assertEquals('contentType:* ', $fields['term'], 'term template falsch geparsed!');
    }

    /**
     * @group unit
     */
    public function testInitSetsCorrectTermIfNoTermParamSet()
    {
        $fields = [];
        $options = [];
        $configArray = [
            $this->confId => [
                'filter.' => [
                    'fields.' => [
                        'term' => 'contentType:* ###PARAM_MKSEARCH_TERM###',
                    ],
                ],
            ],
        ];
        $filter = $this->getFilter($configArray);
        $filter->init($fields, $options);
        self::assertEquals('contentType:* ', $fields['term'], 'term template falsch geparsed!');
    }

    /**
     * @group unit
     */
    public function testGetFilterUtility()
    {
        $filter = $this->getFilter();
        $method = new ReflectionMethod('tx_mksearch_filter_ElasticSearchBase', 'getFilterUtility');
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
        $filter = $this->getFilter([], ['sort' => 'uid desc']);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid desc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectIfSortOrderAsc()
    {
        $filter = $this->getFilter([], ['sort' => 'uid asc']);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid asc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectWithUnknownSortOrder()
    {
        $filter = $this->getFilter([], ['sort' => 'uid unknown']);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid asc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectIfSortOrderInSortOrderParameter()
    {
        $filter = $this->getFilter([], ['sort' => 'uid', 'sortorder' => 'asc']);

        $fields = $options = [];
        $filter->init($fields, $options);

        self::assertEquals('uid asc', $options['sort'], 'sort falsch in options');
    }

    /**
     * @group unit
     */
    public function testInitSetsSortingToOptionsCorrectIfNoSortOrderUsesClassPropertyForSortOrder()
    {
        $filter = $this->getFilter([], ['sort' => 'uid']);

        $filterUtil = $this->getMock('tx_mksearch_util_Filter', ['parseTermTemplate']);

        $order = new ReflectionProperty('tx_mksearch_util_Filter', 'sortOrder');
        $order->setAccessible(true);
        $order->setValue($filterUtil, 'asc');

        $filterUtilProperty = new ReflectionProperty(
            'tx_mksearch_filter_ElasticSearchBase',
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
    public function testInitSetsSortingToOptionsCorrectIfNoSortFieldUsesClassPropertyForSortField()
    {
        $filter = $this->getFilter();
        $filterUtil = $this->getMock('tx_mksearch_util_Filter', ['parseTermTemplate']);

        $field = new ReflectionProperty('tx_mksearch_util_Filter', 'sortField');
        $field->setAccessible(true);
        $field->setValue($filterUtil, 'uid');

        $filterUtilProperty = new ReflectionProperty(
            'tx_mksearch_filter_ElasticSearchBase',
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

        $config = [$this->confId => ['filter.' => [
            'sort.' => [
                'fields' => 'uid, title',
                'link.' => ['noHash' => true],
            ],
            'config.' => ['template' => ''],
        ]]];

        $filter = $this->getFilter($config);

        $filterUtil = $this->getMock('tx_mksearch_util_Filter', ['getSortString']);

        $field = new ReflectionProperty('tx_mksearch_util_Filter', 'sortField');
        $field->setAccessible(true);
        $field->setValue($filterUtil, 'uid');

        $order = new ReflectionProperty('tx_mksearch_util_Filter', 'sortOrder');
        $order->setAccessible(true);
        $order->setValue($filterUtil, 'asc');

        $filterUtilProperty = new ReflectionProperty(
            'tx_mksearch_filter_ElasticSearchBase',
            'filterUtility'
        );
        $filterUtilProperty->setAccessible(true);
        $filterUtilProperty->setValue($filter, $filterUtil);

        $fields = $options = [];
        $filter->init($fields, $options);

        $method = new ReflectionMethod('tx_mksearch_filter_ElasticSearchBase', 'getConfigurations');
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
     * @param array $configArray
     * @param array $parametersArray
     *
     * @return tx_mksearch_filter_ElasticSearchBase
     */
    private function getFilter(array $configArray = [], $parametersArray = [])
    {
        if (!isset($configArray[$this->confId]['filter.']['forceSearch'])) {
            $configArray[$this->confId]['filter.']['forceSearch'] = true;
        }

        $configArray = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
            tx_mksearch_tests_Util::loadPageTS4BE(),
            $configArray
        );
        $configArray[$this->confId]['filter.']['requiredFormFields'] =
            'zip,company,city';
        $configurations = tx_mksearch_tests_Util::loadConfig4BE(
            $configArray
        );
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters', $parametersArray);
        $configurations = $this->createConfigurations(
            $configurations->getConfigArray(),
            'mksearch',
            'mksearch',
            $parameters,
            tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getContentObjectRendererClass())
        );

        return tx_rnbase::makeInstance(
            'tx_mksearch_filter_ElasticSearchBase',
            $parameters,
            $configurations,
            $this->confId.'filter.'
        );
    }
}
