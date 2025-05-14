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
 * Searcher for keywords.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_searcher_IndexerConfig extends tx_mksearch_mod1_searcher_abstractBase
{
    /**
     * Liefert die Funktions-Id.
     */
    protected function getSearcherId(): string
    {
        return 'indexerconfig';
    }

    /**
     * Liefert den Service.
     *
     * @return tx_mksearch_service_Base
     */
    public function getService()
    {
        return tx_mksearch_util_ServiceRegistry::getIntConfigService();
    }

    /**
     * Kann von der Kindklasse überschrieben werden, um weitere Filter zu setzen.
     */
    protected function prepareFieldsAndOptions(array &$fields, array &$options)
    {
        parent::prepareFieldsAndOptions($fields, $options);
        if (isset($this->options['pid'])) {
            $fields['CFG.pid'][OP_EQ_INT] = $this->options['pid'];
        }
    }

    /**
     * @return tx_mksearch_mod1_decorator_Keyword
     */
    protected function getDecorator(&$mod): object
    {
        return TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_IndexerConfig', $mod);
    }

    /**
     * Liefert die Spalten für den Decorator.
     *
     * @param tx_mksearch_mod1_decorator_Keyword $oDecorator
     */
    protected function getColumns(&$oDecorator): array
    {
        return [
            'uid' => [
                'title' => 'label_uid',
                'decorator' => $oDecorator,
            ],
            'actions' => [
                'title' => 'label_tableheader_actions',
                'decorator' => $oDecorator,
            ],
            'title' => [
                'title' => 'label_tableheader_title',
                'decorator' => $oDecorator,
            ],
            'contenttype' => [
                'title' => 'label_tableheader_contenttype',
                'decorator' => $oDecorator,
            ],
            'composites' => [
                'title' => 'label_tableheader_composites',
                'decorator' => $oDecorator,
            ],
        ];
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_mod1_searcher_abstractBase::getSearchColumns()
     */
    protected function getSearchColumns(): array
    {
        return [
            'CFG.uid', 'CFG.title', 'CFG.extkey', 'CFG.contenttype', 'CFG.configuration',
            'CMP.uid', 'CMP.title', 'CMP.description',
            'INDX.uid', 'INDX.title', 'INDX.name', 'INDX.description', 'INDX.engine', 'INDX.configuration',
        ];
    }
}
