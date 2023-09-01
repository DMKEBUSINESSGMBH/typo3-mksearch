<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Gridelements indexer.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_indexer_ttcontent_Gridelements extends tx_mksearch_indexer_ttcontent_Normal
{
    /**
     * Sets the index doc to deleted if neccessary.
     *
     * @param \Sys25\RnBase\Domain\Model\DataInterface                      $oModel
     * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
     * @param array                                 $aOptions
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    protected function hasDocToBeDeleted(
        \Sys25\RnBase\Domain\Model\DataInterface $oModel,
        tx_mksearch_interface_IndexerDocument $oIndexDoc,
        $aOptions = []
    ) {
        // @codingStandardsIgnoreEnd
        // should the element be removed from the index?
        if (
            // only for gridelements? no, other elements should be deleted too!
            // $this->isGridelement($oModel->getRecord()) &&
            // only if not directly set do indexable or not indexable!
            self::USE_INDEXER_CONFIGURATION == $oModel->getTxMksearchIsIndexable()
            // only, if there are a parent container
            && $oModel->getTxGridelementsContainer() > 0
        ) {
            // add the parent do index, so the changes are writen to index
            $this->addGridelementsContainerToIndex($oModel);

            return true;
        }

        return $this->hasNonGridelementDocToBeDeleted($oModel, $oIndexDoc, $aOptions);
    }

    /**
     * Sets the index doc to deleted if neccessary.
     *
     * @param \Sys25\RnBase\Domain\Model\DataInterface                      $oModel
     * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
     * @param array                                 $aOptions
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    protected function hasNonGridelementDocToBeDeleted(
        \Sys25\RnBase\Domain\Model\DataInterface $oModel,
        tx_mksearch_interface_IndexerDocument $oIndexDoc,
        $aOptions = []
    ) {
        // @codingStandardsIgnoreEnd
        return parent::hasDocToBeDeleted($oModel, $oIndexDoc, $aOptions);
    }

    /**
     * Adds the parent to index.
     *
     * @param \Sys25\RnBase\Domain\Model\DataInterface $oModel
     */
    protected function addGridelementsContainerToIndex(
        \Sys25\RnBase\Domain\Model\DataInterface $oModel
    ) {
        // add the parent do index, so the changes are writen to index
        $indexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
        $indexSrv->addRecordToIndex(
            'tt_content',
            $oModel->getTxGridelementsContainer()
        );
    }

    /**
     * Get the content by CType.
     *
     * @param array $rawData
     * @param array $options
     *
     * @return string
     */
    protected function getContentByContentType(
        array $rawData,
        array $options
    ) {
        if (!$this->isGridelement($rawData)) {
            return '';
        }

        return $this->getGridelementElementContent($rawData, $options);
    }

    /**
     * Is the given record an gridelement?
     *
     * @param array $rawData
     *
     * @return bool
     */
    protected function isGridelement(
        array $rawData
    ) {
        return
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements')
            && 'gridelements_pi1' == $rawData['CType']
        ;
    }

    /**
     * Fetches the content of an grid element.
     *
     * @param array $record
     * @param array $options
     *
     * @return string
     */
    protected function getGridelementElementContent(
        array $record,
        array $options
    ) {
        tx_mksearch_util_Indexer::prepareTSFE($record['pid'], $options['lang'] ?? 0);
        $uid = $this->getUid('tt_content', $record, []);

        $allowedCTypes = $this->getAllowedCTypes($options);

        if (is_array($allowedCTypes)) {
            foreach ($GLOBALS['TSFE']->tmpl->setup['tt_content.'] as $currentCType => $conf) {
                if ('key.' == $currentCType) {
                    continue;
                }
                // Config der nicht definierten ContentTypen entfernen, damit
                // Elemente nicht durch Gridelements gerendert werden
                if (!in_array($currentCType, $allowedCTypes)) {
                    unset($GLOBALS['TSFE']->tmpl->setup['tt_content.'][$currentCType]);
                }
            }
        }
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
        $cObj = $GLOBALS['TSFE']->cObj;
        $cObj->start($record, 'tt_content');
        $content = $cObj->cObjGetSingle(
            $GLOBALS['TSFE']->tmpl->setup['tt_content.']['gridelements_pi1'],
            $GLOBALS['TSFE']->tmpl->setup['tt_content.']['gridelements_pi1.']
        );

        return $content;
    }

    /**
     * Gets the allowed CTypes from Configuration for Gridelement Rendering.
     *
     * @param array $options
     *
     * @return array $allowedCTypes
     */
    protected function getAllowedCTypes($options)
    {
        $allowedCTypes = $this->getConfigValue(
            'includeCTypesInGridelementRendering',
            $options
        );
        foreach ($allowedCTypes as $allowedCType) {
            $allowedCTypes[] = $allowedCType.'.';
        }

        return $allowedCTypes;
    }
}
