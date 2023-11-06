<?php

use Psr\Http\Message\ServerRequestInterface;

/**
 * Mksearch backend module.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_Keywords extends \Sys25\RnBase\Backend\Module\BaseModFunc
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

    public function main(ServerRequestInterface $request = null)
    {
        return tx_mksearch_mod1_util_Misc::getSubModuleContent(parent::main($request), $this, 'web_MksearchM1_keywords');
    }

    /**
     * Kindklassen implementieren diese Methode um den Modulinhalt zu erzeugen.
     *
     * @param string                    $template
     * @param \Sys25\RnBase\Configuration\Processor  $configurations
     * @param \Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param \Sys25\RnBase\Backend\Form\ToolBox   $formTool
     *
     * @return string
     */
    protected function getContent($template, &$configurations, &$formatter, $formTool)
    {
        $markerArray = [];

        $markerArray['###COMMON_START###'] = $markerArray['###COMMON_END###'] = '';
        if ($GLOBALS['BE_USER']->isAdmin()) {
            $markerArray['###COMMON_START###'] = \Sys25\RnBase\Frontend\Marker\Templates::getSubpart($template, '###COMMON_START###');
            $markerArray['###COMMON_END###'] = \Sys25\RnBase\Frontend\Marker\Templates::getSubpart($template, '###COMMON_END###');
        }

        $templateMod = \Sys25\RnBase\Frontend\Marker\Templates::getSubpart($template, '###SEARCHPART###');
        $out = $this->showSearch($templateMod, $configurations, $formTool, $markerArray);

        return $out;
    }

    /**
     * Returns search form.
     *
     * @param string                   $template
     * @param \Sys25\RnBase\Configuration\Processor $configurations
     * @param \Sys25\RnBase\Backend\Form\ToolBox  $formTool
     *
     * @return string
     */
    protected function showSearch($template, $configurations, $formTool, &$markerArray)
    {
        $options = [];

        return tx_mksearch_mod1_util_Template::parseList(
            $template,
            $this->getModule(),
            $markerArray,
            $this->getSearcher($options),
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
            $options['pid'] = $this->getModule()->getPid();
        }

        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_searcher_Keywords', $this->getModule(), $options);
    }

    public function getModuleIdentifier()
    {
        return 'mksearch';
    }
}
