<?php

tx_rnbase::load('tx_rnbase_mod_ExtendedModFunc');
tx_rnbase::load('tx_mksearch_mod1_util_Template');

/**
 * Mksearch backend module.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_ConfigIndizes extends tx_rnbase_mod_ExtendedModFunc
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

    public function main()
    {
        $out = parent::main();
        $out = tx_mksearch_mod1_util_Template::parseBasics($out, $this);

        return $out;
    }

    /**
     * Liefert die Einträge für das Tab-Menü.
     *
     * @return array
     */
    protected function getSubMenuItems()
    {
        return array(
                tx_rnbase::makeInstance('tx_mksearch_mod1_handler_Index'),
                tx_rnbase::makeInstance('tx_mksearch_mod1_handler_Composite'),
                tx_rnbase::makeInstance('tx_mksearch_mod1_handler_IndexerConfig'),
            );
    }

    protected function makeSubSelectors(&$selStr)
    {
        return false;
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_ConfigIndizes.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_ConfigIndizes.php'];
}
