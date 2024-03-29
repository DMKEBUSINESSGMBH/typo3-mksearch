<?php

/**
 * Backend Modul Index.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_handler_Index extends tx_mksearch_mod1_handler_Base implements \Sys25\RnBase\Backend\Module\IModHandler
{
    /**
     * Method to get a company searcher.
     *
     * @param \Sys25\RnBase\Backend\Module\IModule $mod
     * @param array                 $options
     *
     * @return tx_mksearch_mod1_searcher_abstractBase
     */
    protected function getSearcher(\Sys25\RnBase\Backend\Module\IModule $mod, &$options)
    {
        if (!isset($options['pid'])) {
            $options['pid'] = $mod->getPid();
        }

        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_searcher_Index', $mod, $options);
    }

    /**
     * Returns a unique ID for this handler. This is used to created the subpart in template.
     *
     * @return string
     */
    public function getSubID()
    {
        return 'Index';
    }
}
