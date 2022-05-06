<?php

/**
 * Searcher for keywords.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_searcher_Index extends tx_mksearch_mod1_searcher_abstractBase
{
    /**
     * Liefert die Funktions-Id.
     */
    public function getSearcherId()
    {
        return 'index';
    }

    /**
     * Liefert den Service.
     *
     * @return tx_mksearch_service_Base
     */
    public function getService()
    {
        return tx_mksearch_util_ServiceRegistry::getIntIndexService();
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
            $fields['INDX.pid'][OP_EQ_INT] = $this->options['pid'];
        }
    }

    /**
     * @return tx_mksearch_mod1_decorator_Keyword
     */
    protected function getDecorator(&$mod)
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_Index', $mod);
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
            'core' => [
                'title' => 'label_tableheader_core',
                'decorator' => $oDecorator,
            ],
            'engine' => [
                'title' => 'label_tableheader_engine',
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
        return ['INDX.uid', 'INDX.title', 'INDX.name', 'INDX.description', 'INDX.engine', 'INDX.configuration'];
    }
}
