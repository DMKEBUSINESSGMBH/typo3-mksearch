<?php

/**
 * Backend Modul Index.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
abstract class tx_mksearch_mod1_handler_Base
{
    /**
     * Enter description here ...
     *
     * @param string                $template
     * @param tx_rnbase_mod_IModule $mod
     * @param array                 $options
     *
     * @return string
     */
    public function showScreen($template, tx_rnbase_mod_IModule $mod, $options)
    {
        return tx_mksearch_mod1_util_Template::parseList(
            $template,
            $mod,
            $markerArray = array(),
            $this->getSearcher($mod, $options = array()),
            strtoupper($this->getSubID())
        );
    }

    /**
     * Datenverarbeitung.
     *
     * @param tx_rnbase_mod_IModule $mod
     */
    public function handleRequest(tx_rnbase_mod_IModule $mod)
    {
        return '';
    }

    /**
     * @param tx_rnbase_mod_IModule $mod
     * @param array                 $options
     *
     * @return tx_mksearch_mod1_searcher_abstractBase
     */
    abstract protected function getSearcher(tx_rnbase_mod_IModule $mod, &$options);

    /**
     * Returns the label for Handler in SubMenu. You can use a label-Marker.
     *
     * @return string
     */
    public function getSubLabel()
    {
        return '###LABEL_HANDLER_'.strtoupper($this->getSubID()).'###';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/handler/class.tx_mksearch_mod1_handler_Base.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/handler/class.tx_mksearch_mod1_handler_Base.php'];
}
