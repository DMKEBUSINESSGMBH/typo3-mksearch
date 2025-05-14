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
 * Show status of search cores.
 */
class tx_mksearch_mod1_util_IndexStatusHandler
{
    /**
     * Returns an instance.
     *
     * @return tx_mksearch_mod1_util_IndexStatusHandler
     */
    public static function getInstance(): object
    {
        return TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_util_IndexStatusHandler');
    }

    /**
     * Enter description here ...
     */
    public function handleRequest4Index(tx_mksearch_model_internal_Index $index): string
    {
        try {
            $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
            $status = $searchEngine->getStatus();
            $msg = $status->getMessage();
            $color = $status->getStatus() > 0 ? 'green' : ($status->getStatus() < 0 ? 'red' : 'yellow');
        } catch (Exception $exception) {
            $color = 'red';
            $msg = 'Exception occured: '.$exception->getMessage();
        }

        $ret = '';
        $ret .= '<a href="#hint" class="mktooltip">';
        $ret .= '<span style="width:20px; background-color:'.$color.'">&nbsp;&nbsp;&nbsp;</span>&nbsp;';
        $ret .= '<strong>'.$index->getTitle().'</strong> - '.$index->getCredentialString().'<br />';
        $ret .= '<span class="info">'.$msg.'</span>';

        return $ret.'</a>';
    }

    /**
     * Handle request.
     */
    public function handleRequest(array $options = []): string
    {
        $fields = [];
        $states = [];
        if (!empty($options['pid'])) {
            $fields['INDX.PID'][OP_EQ_INT] = $options['pid'];
        }

        $options['enablefieldsfe'] = 1;
        $indices = tx_mksearch_util_ServiceRegistry::getIntIndexService()->search($fields, $options);

        // Loop through all active indices, collecting all configurations
        foreach ($indices as $index) {
            $states[] = $this->handleRequest4Index($index);
        }

        return implode('<br />', $states);
    }
}
