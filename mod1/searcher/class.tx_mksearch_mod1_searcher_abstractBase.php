<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

abstract class tx_mksearch_mod1_searcher_abstractBase
{
    /**
     * Selector Klasse.
     */
    private Sys25\RnBase\Backend\Module\IModule $mod;

    /**
     * Selector Klasse.
     */
    private ?object $selector = null;

    /**
     * Otions.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Current search term.
     *
     * @var string
     */
    protected $currentSearchWord = '';

    /**
     * Current hidden option.
     *
     * @var string
     */
    protected $currentShowHidden = 1;

    /**
     * Constructor.
     */
    public function __construct(Sys25\RnBase\Backend\Module\IModule $mod, array $options = [])
    {
        $this->init($mod, $options);
    }

    /**
     * Init object.
     *
     * @param array $options
     */
    protected function init(Sys25\RnBase\Backend\Module\IModule $mod, $options)
    {
        $this->setOptions($options);
        $this->mod = $mod;
    }

    /**
     * Bietet die Möglichkeit die Optionen nach der Erstellung noch zu ändern.
     *
     * @param array $options
     */
    public function setOptions($options): void
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    abstract protected function getSearcherId();

    /**
     * Liefert den Service.
     *
     * @return tx_mksearch_service_Base
     */
    abstract public function getService();

    /**
     * Returns the complete search form.
     *
     * @return string
     */
    public function getSearchForm()
    {
        $data = $this->getFilterTableDataForSearchForm();

        $selector = $this->getSelector();

        return $selector->buildFilterTable($data);
    }

    /**
     * Liefert die Daten für das Basis-Suchformular damit
     * das Html gebaut werden kann.
     *
     * @return array
     */
    protected function getFilterTableDataForSearchForm()
    {
        $data = [
            'search' => [],
            'hidden' => [],
        ];
        $options = [];
        if (isset($this->options['pid'])) {
            $options['pid'] = $this->options['pid'];
        }

        $selector = $this->getSelector();

        $this->currentSearchWord = $selector->showFreeTextSearchForm(
            $data['search'],
            $this->getSearcherId().'Search',
            $options
        );

        $this->currentShowHidden = $selector->showHiddenSelector(
            $data['hidden'],
            $options
        );

        if ($updateButton = $this->getSearchButton()) {
            $data['updatebutton'] = [
                'label' => '',
                'button' => $updateButton,
            ];
        }

        return $data;
    }

    /**
     * Returns the search button.
     *
     * @return string|false
     */
    protected function getSearchButton()
    {
        return $this->getFormTool()->createSubmit(
            $this->getSearcherId().'Search',
            $GLOBALS['LANG']->sL('LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:label_button_search')
        );
    }

    /**
     * Bildet die Resultliste mit Pager.
     *
     * @return string
     */
    public function getResultList()
    {
        $srv = $this->getService();
        /* @var $pager \Sys25\RnBase\Backend\Utility\BEPager */
        $pager = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Backend\Utility\BEPager::class,
            $this->getSearcherId().'Pager',
            $this->getModule(),
            $this->options['pid'] ?? 0
        );
        $fields = [];
        $options = [];
        $this->prepareFieldsAndOptions($fields, $options);

        // Get counted data
        $cnt = $this->getCount($fields, $options);

        $pager->setListSize($cnt);
        $pager->setOptions($options);

        // Get data
        $items = $srv->search($fields, $options);
        $content = '';
        $this->showItems($content, $items);

        $pagerData = $pager->render();

        // der zusammengeführte Pager für die Ausgabe
        // nur wenn es auch Ergebnisse gibt. sonst reicht die noItemsFoundMsg
        $sPagerData = '';
        if ($cnt) {
            $sPagerData = $pagerData['limits'].' - '.$pagerData['pages'];
        }

        return [
            'table' => $content,
            'totalsize' => $cnt,
            'pager' => '<div class="pager">'.$sPagerData.'</div>',
        ];
    }

    /**
     * Kann von der Kindklasse überschrieben werden, um weitere Filter zu setzen.
     */
    protected function prepareFieldsAndOptions(array &$fields, array &$options)
    {
        $options['distinct'] = 1;

        if (!$this->currentShowHidden) {
            $options['enablefieldsfe'] = 1;
        } else {
            $options['enablefieldsbe'] = 1;
        }

        // die fields nun mit dem Suchbegriff und den Spalten,
        // in denen gesucht werden soll, füllen
        tx_mksearch_mod1_util_SearchBuilder::buildFreeText($fields, $this->currentSearchWord, $this->getSearchColumns());
    }

    /**
     * Liefert die Spalten, in denen gesucht werden soll.
     *
     * @return array
     */
    protected function getSearchColumns()
    {
        return [];
    }

    /**
     * Start creation of result list.
     *
     * @param string $content
     *
     * @return string
     */
    protected function showItems(&$content, array $items)
    {
        if ([] === $items) {
            $content = $this->getNoItemsFoundMsg();

            return null; // stop
        }

        // else
        $aColumns = $this->getColumns($this->getDecorator($this->getModule()));

        /* @var $tables \Sys25\RnBase\Backend\Utility\Tables */
        $tables = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Backend\Utility\Tables::class);
        [$tableData, $tableLayout] = $tables->prepareTable(
            $items,
            $aColumns,
            $this->getFormTool(),
            $this->getOptions()
        );

        $out = $tables->buildTable($tableData, $tableLayout);

        $content .= $out;

        return $out;
    }

    /**
     * @return Sys25\RnBase\Backend\Decorator\InterfaceDecorator
     */
    abstract protected function getDecorator(&$mod);

    /**
     * @deprecated bitte getDecoratorColumns nutzen
     */
    protected function getColumns(&$oDecorator)
    {
        return $this->getDecoratorColumns($oDecorator);
    }

    /**
     * Liefert die Spalten für den Decorator.
     *
     * @param Sys25\RnBase\Backend\Decorator\InterfaceDecorator $oDecorator
     *
     * @return array
     */
    protected function getDecoratorColumns(&$oDecorator)
    {
        return [
            'uid' => [
                'title' => 'label_tableheader_uid',
                'decorator' => &$oDecorator,
            ],
            'actions' => [
                'title' => 'label_tableheader_actions',
                'decorator' => &$oDecorator,
            ],
        ];
    }

    /**
     * Der Selector wird erst erzeugt, wenn er benötigt wird.
     *
     * @return tx_mksearch_mod1_util_Selector
     */
    protected function getSelector()
    {
        if (null === $this->selector) {
            $this->selector = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_util_Selector');
            $this->selector->init($this->getModule());
        }

        return $this->selector;
    }

    protected function getCount(array &$fields, array $options)
    {
        // Get counted data
        $options['count'] = 1;

        return $this->getService()->search($fields, $options);
    }

    /**
     * Returns an instance of \Sys25\RnBase\Backend\Module\IModule.
     *
     * @return Sys25\RnBase\Backend\Module\IModule
     */
    protected function getModule()
    {
        return $this->mod;
    }

    /**
     * Returns an instance of \Sys25\RnBase\Backend\Module\IModule.
     *
     * @return Sys25\RnBase\Backend\Module\IModule
     */
    protected function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns an instance of \Sys25\RnBase\Backend\Module\IModule.
     *
     * @return Sys25\RnBase\Backend\Form\ToolBox
     */
    protected function getFormTool()
    {
        return $this->mod->getFormTool();
    }

    /**
     * Returns the message in case no items could be found in showItems().
     *
     * @return string
     */
    protected function getNoItemsFoundMsg()
    {
        return '<p><strong>###LABEL_NO_'.strtoupper($this->getSearcherId()).'_FOUND###</strong></p><br/>';
    }
}
