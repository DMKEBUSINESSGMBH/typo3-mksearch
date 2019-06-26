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
     * @var tx_rnbase_mod_IModule
     */
    private $mod = null;
    /**
     * Selector Klasse.
     *
     * @var tx_mksearch_mod1_util_Selector
     */
    private $selector = null;
    /**
     * Otions.
     *
     * @var array
     */
    protected $options = array();

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
     * @param tx_rnbase_mod_IModule $mod
     * @param array                 $options
     */
    public function __construct(tx_rnbase_mod_IModule $mod, array $options = array())
    {
        $this->init($mod, $options);
    }

    /**
     * Init object.
     *
     * @param tx_rnbase_mod_IModule $mod
     * @param array                 $options
     */
    protected function init(tx_rnbase_mod_IModule $mod, $options)
    {
        // locallang einlesen
        if (!self::$llLoaded) {
            $GLOBALS['LANG']->includeLLFile('EXT:mksearch/mod1/locallang.xml');
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
        $data = array();
        $options = array();
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
            $data['updatebutton'] = array(
                'label' => '',
                'button' => $updateButton,
            );
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
        /* @var $pager tx_rnbase_util_BEPager */
        $pager = tx_rnbase::makeInstance(
            'tx_rnbase_util_BEPager',
            $this->getSearcherId().'Pager',
            $this->getModule()->getName(),
            (isset($this->options['pid'])) ? $this->options['pid'] : 0
        );

        $fields = $options = array();
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

        //der zusammengeführte Pager für die Ausgabe
        //nur wenn es auch Ergebnisse gibt. sonst reicht die noItemsFoundMsg
        $sPagerData = '';
        if ($cnt) {
            $sPagerData = $pagerData['limits'].' - '.$pagerData['pages'];
        }

        return array(
                'table' => $content,
                'totalsize' => $cnt,
                'pager' => '<div class="pager">'.$sPagerData.'</div>',
            );
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

        //die fields nun mit dem Suchbegriff und den Spalten,
        //in denen gesucht werden soll, füllen
        tx_mksearch_mod1_util_SearchBuilder::buildFreeText($fields, $this->currentSearchWord, $this->getSearchColumns());
    }

    /**
     * Liefert die Spalten, in denen gesucht werden soll.
     *
     * @return array
     */
    protected function getSearchColumns()
    {
        return array();
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

            return; //stop
        }
        // else
        $aColumns = $this->getColumns($this->getDecorator($this->getModule()));

        /* @var $tables Tx_Rnbase_Backend_Utility_Tables */
        $tables = tx_rnbase::makeInstance('Tx_Rnbase_Backend_Utility_Tables');
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
     * @return tx_rnbase_mod_IDecorator
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
     * @param tx_rnbase_mod_IDecorator $oDecorator
     *
     * @return array
     */
    protected function getDecoratorColumns(&$oDecorator)
    {
        return array(
            'uid' => array(
                'title' => 'label_tableheader_uid',
                'decorator' => &$oDecorator,
            ),
            'actions' => array(
                'title' => 'label_tableheader_actions',
                'decorator' => &$oDecorator,
            ),
        );
    }

    /**
     * Der Selector wird erst erzeugt, wenn er benötigt wird.
     *
     * @return tx_mksearch_mod1_util_Selector
     */
    protected function getSelector()
    {
        if (!$this->selector) {
            $this->selector = tx_rnbase::makeInstance('tx_mksearch_mod1_util_Selector');
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
     * Returns an instance of tx_rnbase_mod_IModule.
     *
     * @return tx_rnbase_mod_IModule
     */
    protected function getModule()
    {
        return $this->mod;
    }

    /**
     * Returns an instance of tx_rnbase_mod_IModule.
     *
     * @return tx_rnbase_mod_IModule
     */
    protected function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns an instance of tx_rnbase_mod_IModule.
     *
     * @return tx_rnbase_util_FormTool
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/searcher/class.tx_mksearch_mod1_searcher_abstractBase.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/searcher/class.tx_mksearch_mod1_searcher_abstractBase.php'];
}
