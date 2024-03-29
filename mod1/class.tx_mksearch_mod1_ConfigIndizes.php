<?php

use Psr\Http\Message\ServerRequestInterface;

/**
 * Mksearch backend module.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_ConfigIndizes extends \Sys25\RnBase\Backend\Module\ExtendedModFunc
{
    /**
     * Return function id (used in page typoscript etc.).
     *
     * @return string
     */
    protected function getFuncId()
    {
        return 'configindizes';
    }

    public function getPid()
    {
        return $this->getModule()->getPid();
    }

    public function main(?ServerRequestInterface $request = null)
    {
        return tx_mksearch_mod1_util_Misc::getSubModuleContent(
            parent::main($request),
            $this,
            'web_MksearchM1_config_indices'
        );
    }

    /**
     * Liefert die Einträge für das Tab-Menü.
     *
     * @return array
     */
    protected function getSubMenuItems()
    {
        return [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_handler_Index'),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_handler_Composite'),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_handler_IndexerConfig'),
        ];
    }

    protected function makeSubSelectors(&$selStr)
    {
        return false;
    }

    public function getModuleIdentifier()
    {
        return 'mksearch';
    }
}
