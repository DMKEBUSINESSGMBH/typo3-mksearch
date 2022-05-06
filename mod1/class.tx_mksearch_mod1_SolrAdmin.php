<?php

/**
 * Mksearch backend module.
 *
 * Hat nchts mehr speziell mit Solr zu tun,
 * wurde von der Namensgebung allerdings so beibehalten.
 *
 * @author René Nitzsche <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_SolrAdmin extends \Sys25\RnBase\Backend\Module\ExtendedModFunc
{
    /**
     * Return function id (used in page typoscript etc.).
     *
     * @return string
     */
    protected function getFuncId()
    {
        return 'admin';
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
        return [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_handler_admin_Solr'),
        ];
    }

    protected function makeSubSelectors(&$selStr)
    {
        return false;
    }
}
