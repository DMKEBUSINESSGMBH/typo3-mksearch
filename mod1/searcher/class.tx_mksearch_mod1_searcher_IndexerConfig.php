<?php

/**
 * Searcher for keywords.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_searcher_IndexerConfig extends tx_mksearch_mod1_searcher_abstractBase
{
    /**
     * Liefert die Funktions-Id.
     */
    public function getSearcherId()
    {
        return 'indexerconfig';
    }

    /**
     * Liefert den Service.
     *
     * @return tx_mksearch_service_Base
     */
    public function getService()
    {
        return tx_mksearch_util_ServiceRegistry::getIntConfigService();
    }

    /**
     * Kann von der Kindklasse überschrieben werden, um weitere Filter zu setzen.
     *
     * @param array $fields
     * @param array $options
     */
    protected function prepareFieldsAndOptions(array &$fields, array &$options)
    {
        parent::prepareFieldsAndOptions($fields, $options);
        if (isset($this->options['pid'])) {
            $fields['CFG.pid'][OP_EQ_INT] = $this->options['pid'];
        }
    }

    /**
     * @return tx_mksearch_mod1_decorator_Keyword
     */
    protected function getDecorator(&$mod)
    {
        return tx_rnbase::makeInstance('tx_mksearch_mod1_decorator_IndexerConfig', $mod);
    }

    /**
     * Liefert die Spalten für den Decorator.
     *
     * @param tx_mksearch_mod1_decorator_Keyword $oDecorator
     *
     * @return array
     */
    protected function getColumns(&$oDecorator)
    {
        return [
            'uid' => [
                'title' => 'label_uid',
                'decorator' => $oDecorator,
            ],
            'actions' => [
                'title' => 'label_tableheader_actions',
                'decorator' => $oDecorator,
            ],
            'title' => [
                'title' => 'label_tableheader_title',
                'decorator' => $oDecorator,
            ],
            'contenttype' => [
                'title' => 'label_tableheader_contenttype',
                'decorator' => $oDecorator,
            ],
            'composites' => [
                'title' => 'label_tableheader_composites',
                'decorator' => $oDecorator,
            ],
        ];
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_mod1_searcher_abstractBase::getSearchColumns()
     */
    protected function getSearchColumns()
    {
        return [
            'CFG.uid', 'CFG.title', 'CFG.extkey', 'CFG.contenttype', 'CFG.configuration',
            'CMP.uid', 'CMP.title', 'CMP.description',
            'INDX.uid', 'INDX.title', 'INDX.name', 'INDX.description', 'INDX.engine', 'INDX.configuration',
        ];
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/searcher/class.tx_mksearch_mod1_searcher_IndexerConfig.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/searcher/class.tx_mksearch_mod1_searcher_IndexerConfig.php'];
}
