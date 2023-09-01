<?php

/**
 * Basisklasse für Suchfunktionen in BE-Modulen.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
abstract class tx_mksearch_mod1_searcher_abstractBase
{
    /**
     * Wurde die ll bereits geladen?
     *
     * @var bool
     */
    private static $llLoaded = false;
    /**
     * Selector Klasse.
     *
     * @var \Sys25\RnBase\Backend\Module\IModule
     */
    private $mod;
    /**
     * Selector Klasse.
     *
     * @var tx_mksearch_mod1_util_Selector
     */
    private $selector;
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
     *
     * @param \Sys25\RnBase\Backend\Module\IModule $mod
     * @param array                 $options
     */
    public function __construct(\Sys25\RnBase\Backend\Module\IModule $mod, array $options = [])
    {
        $this->init($mod, $options);
    }

    /**
     * Init object.
     *
     * @param \Sys25\RnBase\Backend\Module\IModule $mod
     * @param array                 $options
     */
    protected function init(\Sys25\RnBase\Backend\Module\IModule $mod, $options)
    {
        // locallang einlesen
        if (!self::$llLoaded) {
            $GLOBALS['LANG']->includeLLFile('EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf');
            self::$llLoaded = true;
        }
        $this->setOptions($options);
        $this->mod = $mod;
    }

    /**
     * Bietet die Möglichkeit die Optionen nach der Erstellung noch zu ändern.
     *
     * @param array $options
     */
    public function setOptions($options)
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
        $out = $selector->buildFilterTable($data);

        return $out;
    }

    /**
     * Liefert die Daten für das Basis-Suchformular damit
     * das Html gebaut werden kann.
     *
     * @return array
     */
    protected function getFilterTableDataForSearchForm()
    {
        $data = [];
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
        $out = $this->getFormTool()->createSubmit(
            $this->getSearcherId().'Search',
            $GLOBALS['LANG']->getLL('label_button_search')
        );

        return $out;
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
        $pager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Backend\Utility\BEPager::class,
            $this->getSearcherId().'Pager',
            $this->getModule()->getName(),
            (isset($this->options['pid'])) ? $this->options['pid'] : 0
        );

        $fields = $options = [];
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
     *
     * @param array $fields
     * @param array $options
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
     * @param array  $items
     *
     * @return string
     */
    protected function showItems(&$content, array $items)
    {
        if (0 === count($items)) {
            $content = $this->getNoItemsFoundMsg();

            return; // stop
        }
        // else
        $aColumns = $this->getColumns($this->getDecorator($this->getModule()));

        /* @var $tables \Sys25\RnBase\Backend\Utility\Tables */
        $tables = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Backend\Utility\Tables::class);
        list($tableData, $tableLayout) = $tables->prepareTable(
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
     * @return \Sys25\RnBase\Backend\Decorator\InterfaceDecorator
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
     * @param \Sys25\RnBase\Backend\Decorator\InterfaceDecorator $oDecorator
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
        if (!$this->selector) {
            $this->selector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_util_Selector');
            $this->selector->init($this->getModule());
        }

        return $this->selector;
    }

    /**
     * @param array $fields
     * @param array $options
     */
    protected function getCount(array &$fields, array $options)
    {
        // Get counted data
        $options['count'] = 1;

        return $this->getService()->search($fields, $options);
    }

    /**
     * Returns an instance of \Sys25\RnBase\Backend\Module\IModule.
     *
     * @return \Sys25\RnBase\Backend\Module\IModule
     */
    protected function getModule()
    {
        return $this->mod;
    }

    /**
     * Returns an instance of \Sys25\RnBase\Backend\Module\IModule.
     *
     * @return \Sys25\RnBase\Backend\Module\IModule
     */
    protected function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns an instance of \Sys25\RnBase\Backend\Module\IModule.
     *
     * @return \Sys25\RnBase\Backend\Form\ToolBox
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
