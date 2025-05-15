<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use Psr\Http\Message\ServerRequestInterface;

/**
 * Mksearch backend module.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_Keywords extends Sys25\RnBase\Backend\Module\BaseModFunc
{
    /**
     * Return function id (used in page typoscript etc.).
     */
    protected function getFuncId(): string
    {
        return 'keywords';
    }

    public function getPid()
    {
        return $this->getModule()->getPid();
    }

    public function main(?ServerRequestInterface $request = null)
    {
        return tx_mksearch_mod1_util_Misc::getSubModuleContent(parent::main($request), $this);
    }

    /**
     * Kindklassen implementieren diese Methode um den Modulinhalt zu erzeugen.
     *
     * @param string                                  $template
     * @param Sys25\RnBase\Configuration\Processor    $configurations
     * @param Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param Sys25\RnBase\Backend\Form\ToolBox       $formTool
     *
     * @return string
     */
    protected function getContent($template, &$configurations, &$formatter, $formTool)
    {
        $ret = tx_mksearch_mod1_util_Misc::checkPid($this->getModule());
        if (null !== $ret && '' !== $ret && '0' !== $ret) {
            return $ret;
        }

        $markerArray = [];

        $markerArray['###COMMON_START###'] = $markerArray['###COMMON_END###'] = '';
        if ($GLOBALS['BE_USER']->isAdmin()) {
            $markerArray['###COMMON_START###'] = Sys25\RnBase\Frontend\Marker\Templates::getSubpart($template, '###COMMON_START###');
            $markerArray['###COMMON_END###'] = Sys25\RnBase\Frontend\Marker\Templates::getSubpart($template, '###COMMON_END###');
        }

        $templateMod = Sys25\RnBase\Frontend\Marker\Templates::getSubpart($template, '###SEARCHPART###');

        return $this->showSearch($templateMod, $configurations, $formTool, $markerArray);
    }

    /**
     * Returns search form.
     *
     * @param string                               $template
     * @param Sys25\RnBase\Configuration\Processor $configurations
     * @param Sys25\RnBase\Backend\Form\ToolBox    $formTool
     *
     * @return string
     */
    protected function showSearch($template, $configurations, $formTool, ?array &$markerArray)
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
     * @return tx_mksearch_mod1_searcher_Keywords
     */
    private function getSearcher(array &$options): object
    {
        if (!isset($options['pid'])) {
            $options['pid'] = $this->getModule()->getPid();
        }

        return TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_searcher_Keywords', $this->getModule(), $options);
    }

    public function getModuleIdentifier(): string
    {
        return 'mksearch';
    }
}
