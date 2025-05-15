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

/**
 * Backend Modul Index.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_handler_admin_Solr implements Sys25\RnBase\Backend\Module\IModHandler
{
    private $data = [];

    /**
     * Returns a unique ID for this handler. This is used to created the subpart in template.
     */
    public function getSubID(): string
    {
        return 'AdminSolr';
    }

    /**
     * Returns the label for Handler in SubMenu. You can use a label-Marker.
     */
    public function getSubLabel(): string
    {
        return '###LABEL_HANDLER_'.strtoupper($this->getSubID()).'###';
    }

    /**
     * This method is called each time the method func is clicked, to handle request data.
     */
    public function handleRequest(Sys25\RnBase\Backend\Module\IModule $mod): ?string
    {
        $submitted = Sys25\RnBase\Frontend\Request\Parameters::getPostOrGetParameter('doDelete') || Sys25\RnBase\Frontend\Request\Parameters::getPostOrGetParameter('doQuery');
        if (!$submitted) {
            return '';
        }

        $this->data = Sys25\RnBase\Frontend\Request\Parameters::getPostOrGetParameter('data');
        $deleteQuery = trim($this->data['deletequery']);
        $SET = Sys25\RnBase\Frontend\Request\Parameters::getPostOrGetParameter('SET');
        $core = intval($SET['solr_core']);
        if (0 === $core) {
            $mod->addMessage('###LABEL_SOLR_NOCORE_FOUND###', '###LABEL_COMMON_WARNING###');

            return null;
        }

        try {
            $core = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_internal_Index', $core);
            $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($core);
            $result = $searchEngine->indexDeleteByQuery($deleteQuery);
            $searchEngine->commitIndex();
            $mod->addMessage('###LABEL_SOLR_DELETE_SUCCESSFUL###', '###LABEL_COMMON_INFO###');
        } catch (Exception $exception) {
            $mod->addMessage(htmlspecialchars($exception->getMessage()), '###LABEL_COMMON_ERROR###', 2);
            Sys25\RnBase\Utility\Logger::warn('[SolrAdmin] Exception for delete query.', 'mksearch', ['Exception' => $exception->getMessage()]);
        }

        return null;
    }

    /**
     * Display the user interface for this handler.
     *
     * @param string $template the subpart for handler in func template
     * @param array  $options
     */
    public function showScreen($template, Sys25\RnBase\Backend\Module\IModule $mod, $options)
    {
        $markerArray = [];

        $cores = $this->findSolrCores($mod);
        if (empty($cores)) {
            return '###LABEL_SOLR_NOCORES_FOUND###';
        }

        return $this->showAdminPanel($template, $cores, $mod, $markerArray);
    }

    /**
     * Returns search form.
     *
     * @param string $template
     * @param array  $cores
     *
     * @return string
     */
    protected function showAdminPanel($template, $cores, Sys25\RnBase\Backend\Module\IModule $mod, ?array &$markerArray)
    {
        $formTool = $mod->getFormTool();

        $markerArray['###SEL_CORES###'] = $this->getCoreSelector($cores, $mod);
        $markerArray['###INPUT_DELETEQUERY###'] = $formTool->createTextArea('data[deletequery]', $this->data['deletequery'] ?? '');
        $markerArray['###BTN_SEND###'] = $formTool->createSubmit('doDelete', '###LABEL_SOLR_SUBMIT_DELETE###', 'Do you really want to submit this DELETE query?');

        return Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($template, $markerArray);
    }

    protected function findSolrCores(Sys25\RnBase\Backend\Module\IModule $mod)
    {
        $fields = [];
        $options = [];
        $options['enablefieldsfe'] = 1;
        // Solr-Core auf der aktuellen Seite suchen
        $fields['INDX.PID'][OP_EQ_INT] = $mod->getPid();
        $fields['INDX.ENGINE'][OP_EQ] = 'solr';

        return tx_mksearch_util_ServiceRegistry::getIntIndexService()->search($fields, $options);
    }

    protected function getCoreSelector($cores, Sys25\RnBase\Backend\Module\IModule $mod)
    {
        $entries = [];
        foreach ($cores as $core) {
            $entries[$core->getUid()] = $core->getTitle().' ('.$core->getName().') '.$this->countDocs($core);
        }

        $menu = $mod->getFormTool()->showMenu($mod->getPid(), 'solr_core', $mod->getName(), $entries);

        return $menu['menu'];
    }

    protected function countDocs(tx_mksearch_model_internal_Index $core): string
    {
        $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($core);

        $fields['term'] = '*:*';
        $options['rows'] = '0';
        $info = '';
        try {
            $ret = $searchEngine->search($fields, $options);
            $result = $ret;
            $info = $ret['numFound'].' docs found';
        } catch (Exception $exception) {
            $info = $exception->getMessage();
        }

        return '('.$info.')';
    }
}
