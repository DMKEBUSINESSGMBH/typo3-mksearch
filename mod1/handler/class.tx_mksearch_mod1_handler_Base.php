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
     * @param \Sys25\RnBase\Backend\Module\IModule $mod
     * @param array                 $options
     *
     * @return string
     */
    public function showScreen($template, \Sys25\RnBase\Backend\Module\IModule $mod, $options)
    {
        return tx_mksearch_mod1_util_Template::parseList(
            $template,
            $mod,
            $markerArray = [],
            $this->getSearcher($mod, $options = []),
            strtoupper($this->getSubID())
        );
    }

    /**
     * Datenverarbeitung.
     *
     * @param \Sys25\RnBase\Backend\Module\IModule $mod
     */
    public function handleRequest(\Sys25\RnBase\Backend\Module\IModule $mod)
    {
        return '';
    }

    /**
     * @param \Sys25\RnBase\Backend\Module\IModule $mod
     * @param array                 $options
     *
     * @return tx_mksearch_mod1_searcher_abstractBase
     */
    abstract protected function getSearcher(\Sys25\RnBase\Backend\Module\IModule $mod, &$options);

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
