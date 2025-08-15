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
     * @param array $aOptions
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    protected function hasDocToBeDeleted(
        Sys25\RnBase\Domain\Model\DataInterface $oModel,
        tx_mksearch_interface_IndexerDocument $oIndexDoc,
        $aOptions = [],
    ): bool {
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
     * @param array $aOptions
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    protected function hasNonGridelementDocToBeDeleted(
        Sys25\RnBase\Domain\Model\DataInterface $oModel,
        tx_mksearch_interface_IndexerDocument $oIndexDoc,
        $aOptions = [],
    ): bool {
        // @codingStandardsIgnoreEnd
        return parent::hasDocToBeDeleted($oModel, $oIndexDoc, $aOptions);
    }

    /**
     * Adds the parent to index.
     */
    protected function addGridelementsContainerToIndex(
        Sys25\RnBase\Domain\Model\DataInterface $oModel,
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
     */
    protected function getContentByContentType(
        array $rawData,
        array $options,
    ): string {
        if (!$this->isGridelement($rawData)) {
            return '';
        }

        return $this->getGridelementElementContent($rawData, $options);
    }

    /**
     * Is the given record an gridelement?
     */
    protected function isGridelement(
        array $rawData,
    ): bool {
        return
            TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements')
            && 'gridelements_pi1' == $rawData['CType']
        ;
    }

    /**
     * Fetches the content of an grid element.
     *
     * @return string
     */
    protected function getGridelementElementContent(
        array $record,
        array $options,
    ) {
        $pageIdOfRecord = (int) $record['pid'];
        tx_mksearch_util_Indexer::prepareTSFE($pageIdOfRecord, $options['lang'] ?? 0);

        /** @var TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
        $cObj = $GLOBALS['TSFE']->cObj;
        $setup = $this->getTypoScriptConfiguration($cObj, $options, $pageIdOfRecord);
        // This is needed so the BackendConfigurationManager loads the TypoScript for the current tt_content
        // record during it's rendering and not for the page that is selected in the BE page tree.
        $originalPageId = $this->setCurrentPageIdInConfigurationManager($pageIdOfRecord);

        $cObj->start($record, 'tt_content');

        $content = $cObj->cObjGetSingle(
            $setup['tt_content.']['gridelements_pi1'],
            $setup['tt_content.']['gridelements_pi1.']
        );
        // Make sure to reset the page id so the configuration manager will load the TypoScript for the page that is
        // selected in the BE page tree if it's needed after this point.
        $this->setCurrentPageIdInConfigurationManager($originalPageId);

        return $content;
    }

    protected function getTypoScriptConfiguration(
        TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj,
        array $options,
        int $pageIdOfRecord,
    ): array {
        $allowedCTypes = $this->getAllowedCTypes($options);

        $setup = $cObj->getRequest()->getAttribute('frontend.typoscript')->getSetupArray();

        if (is_array($allowedCTypes)) {
            // This configuration is used in the overwrite for
            // GridElementsTeam\Gridelements\DataProcessing\GridChildrenProcessor so we can exclude unwanted records
            // when gridelements is used with data processing lib content element
            $setup['tt_content.']['gridelements_pi1.']['includeCTypesInGridelementRendering.'] = $allowedCTypes;

            // We remove TypoScript configuration for unwanted content types which is used when the old gridelements
            // plugin is used.
            foreach ($setup['tt_content.'] as $currentCType => $conf) {
                if ('key.' == $currentCType) {
                    continue;
                }

                // Config der nicht definierten ContentTypen entfernen, damit
                // Elemente nicht durch Gridelements gerendert werden
                if (!in_array($currentCType, $allowedCTypes)) {
                    unset($setup['tt_content.'][$currentCType]);
                }
            }

            $frontendTypoScript = $cObj->getRequest()->getAttribute('frontend.typoscript');
            $frontendTypoScript->setSetupArray($setup);
            $cObj->setRequest($cObj->getRequest()->withAttribute('frontend.typoscript', $frontendTypoScript));

            // Put in runtime cache for TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager so
            // includeCTypesInGridelementRendering is available at this point
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Cache\CacheManager::class)->getCache(
                'runtime'
            )->set('extbase-backend-typoscript-pageId-'.$pageIdOfRecord, $setup);
        }

        return $setup;
    }

    protected function setCurrentPageIdInConfigurationManager(?int $pageIdOfRecord): ?int
    {
        if (Sys25\RnBase\Utility\TYPO3::isTYPO130OrHigher()) {
            $runtimeCache = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Cache\CacheManager::class)
                ->getCache('runtime');
            $originalPageId = $runtimeCache->get('extbase-backend-typoscript-currentPageId');
            $runtimeCache->set('extbase-backend-typoscript-currentPageId', $pageIdOfRecord);
        } else {
            // As it might have happened that the BackendConfigurationManager has retrieved the current page id already
            // the request is not checked anymore. So we need to set the currentPageId variable through reflection.
            $property = new ReflectionProperty(
                TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::class,
                'currentPageId'
            );
            $manager = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::class
            );
            $originalPageId = $property->getValue($manager);
            $property->setValue($manager, $pageIdOfRecord);
        }

        return $originalPageId;
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
