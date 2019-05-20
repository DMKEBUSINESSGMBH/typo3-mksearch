<?php

tx_rnbase::load('tx_rnbase_mod_BaseModFunc');

/**
 * Mksearch backend module.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_Keywords extends tx_rnbase_mod_BaseModFunc
{
    /**
     * Return function id (used in page typoscript etc.).
     *
     * @return string
     */
    protected function getFuncId()
    {
        return 'keywords';
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
     * Kindklassen implementieren diese Methode um den Modulinhalt zu erzeugen.
     *
     * @param string                    $template
     * @param tx_rnbase_configurations  $configurations
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param tx_rnbase_util_FormTool   $formTool
     *
     * @return string
     */
    protected function getContent($template, &$configurations, &$formatter, $formTool)
    {
        $markerArray = array();

        $markerArray['###COMMON_START###'] = $markerArray['###COMMON_END###'] = '';
        if ($GLOBALS['BE_USER']->isAdmin()) {
            $markerArray['###COMMON_START###'] = tx_rnbase_util_Templates::getSubpart($template, '###COMMON_START###');
            $markerArray['###COMMON_END###'] = tx_rnbase_util_Templates::getSubpart($template, '###COMMON_END###');
        }

        $templateMod = tx_rnbase_util_Templates::getSubpart($template, '###SEARCHPART###');
        $out = $this->showSearch($templateMod, $configurations, $formTool, $markerArray);

        return $out;
    }

    /**
     * Returns search form.
     *
     * @param string                   $template
     * @param tx_rnbase_configurations $configurations
     * @param tx_rnbase_util_FormTool  $formTool
     *
     * @return string
     */
    protected function showSearch($template, $configurations, $formTool, &$markerArray)
    {
        tx_rnbase::load('tx_mksearch_mod1_util_Template');

        return tx_mksearch_mod1_util_Template::parseList(
            $template,
            $this->getModule(),
            $markerArray,
            $this->getSearcher($options = array()),
            'KEYWORD'
        );
    }

    /**
     * Method to get a company searcher.
     *
     * @param array $options
     *
     * @return tx_mksearch_mod1_searcher_Keywords
     */
    private function getSearcher(&$options)
    {
        if (!isset($options['pid'])) {
            $options['pid'] = $this->getModule()->id;
        }

        return tx_rnbase::makeInstance('tx_mksearch_mod1_searcher_Keywords', $this->getModule(), $options);
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_Keywords.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_Keywords.php'];
}
