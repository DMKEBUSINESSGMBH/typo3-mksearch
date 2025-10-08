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
 * Indexer service for dam.media called by the "mksearch" extension.
 */
class tx_mksearch_indexer_FAL extends tx_mksearch_indexer_BaseMedia
{
    /**
     * Return content type identification.
     * This identification is part of the indexed data
     * and is used on later searches to identify the search results.
     * You're completely free in the range of values, but take care
     * as you at the same time are responsible for
     * uniqueness (i.e. no overlapping with other content types) and
     * consistency (i.e. recognition) on indexing and searching data.
     */
    public static function getContentType(): array
    {
        return ['core', 'file'];
    }

    /**
     * Liefert den namen zur Basistabelle.
     */
    protected function getBaseTableName(): string
    {
        return 'sys_file';
    }

    /**
     * @param string $tableName
     * @param array  $sourceRecord
     * @param array  $options
     *
     * @return bool|tx_mksearch_interface_IndexerDocument|null
     */
    public function prepareSearchData(
        $tableName,
        $sourceRecord,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options,
    ) {
        // get meta data, too, and fill the abstract
        if ($resourceFile = $this->getFileFromRecord($sourceRecord)) {
            $sourceRecord = $resourceFile->getProperties();
            if ($options['queueReferencedRecords'] ?? false) {
                $this->queueReferencedRecords($resourceFile);
            }
        }

        return parent::prepareSearchData($tableName, $sourceRecord, $indexDoc, $options);
    }

    /**
     * Den Relativen Server-Pfad zur Datei.
     *
     * @param string $tableName
     * @param array  $sourceRecord
     */
    protected function getRelFileName($tableName, $sourceRecord): string
    {
        // falls wir keine ressource haben, bauen die url selbst zusammen.
        $relativeFileName = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'].($sourceRecord['identifier'] ?? '');
        // wir haben ein indiziertes dokument
        if (isset($sourceRecord['uid']) && intval($sourceRecord['uid']) > 0) {
            $resourceFile = $this->getFileFromRecord($sourceRecord);
            if ($resourceFile) {
                // getPublicUrl macht ein rawurlencode, womit aber Umlaute etc.
                // enkodiert werden. Im Dateisystem werden diese aber nciht enkodiert stehen
                // womit wir das wieder dekodieren müssen. Es gibt leider
                // keine besser Möglichkeit an den unbehandelten Pfad zur Datei
                // inkl. Pfad vom Storage zu kommen.
                $relativeFileName = rawurldecode((string) $resourceFile->getPublicUrl());
            }
        }

        // make sure there is no leading forward slash which get's added since TYPO3 11.x
        return ltrim($relativeFileName, '/');
    }

    protected function getFileFromRecord(array $sourceRecord): TYPO3\CMS\Core\Resource\File|false
    {
        $resourceStorage = $this->getResourceStorage($sourceRecord['storage'] ?? 0);
        // wir holen uns die url von dem storage, falls vorhanden
        if ($resourceStorage instanceof TYPO3\CMS\Core\Resource\ResourceStorage) {
            return new TYPO3\CMS\Core\Resource\File($sourceRecord, $resourceStorage);
        }

        return false;
    }

    /**
     * @param int $storageUid
     *
     * @return TYPO3\CMS\Core\Resource\ResourceStorage
     */
    protected function getResourceStorage($storageUid)
    {
        if (TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($storageUid)) {
            /**
             * @var ResourceFactory
             */
            $fileFactory = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                TYPO3\CMS\Core\Resource\ResourceFactory::class
            );

            return $fileFactory->getStorageObject($storageUid);
        }

        return null;
    }

    protected function queueReferencedRecords(TYPO3\CMS\Core\Resource\File $file): void
    {
        $indexService = $this->getInternalIndexService();
        /** @var TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getQueryBuilderForTable('sys_file_reference');

        $result = $queryBuilder
            ->select('tablenames', 'uid_foreign')
            ->from('sys_file_reference')->where($queryBuilder->expr()->eq(
                'uid_local',
                $queryBuilder->createNamedParameter($file->getUid(), TYPO3\CMS\Core\Database\Connection::PARAM_INT)
            ))->executeQuery();

        while ($reference = $result->fetchAssociative()) {
            if ($reference['tablenames'] && $reference['uid_foreign']) {
                $indexService->addRecordToIndex($reference['tablenames'], $reference['uid_foreign']);
            }
        }
    }

    /**
     * Liefert die Dateiendung.
     *
     * @param string $tableName
     * @param array  $sourceRecord
     *
     * @return string
     */
    protected function getFileExtension($tableName, $sourceRecord)
    {
        return $sourceRecord['extension'] ?? '';
    }

    /**
     * Liefert den Pfad zur Datei (ohne Dateinamen).
     *
     * @param string $tableName
     * @param array  $sourceRecord
     */
    protected function getFilePath($tableName, $sourceRecord): string
    {
        $path = $this->getRelFileName($tableName, $sourceRecord);

        return dirname($path).'/';
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_indexer_BaseMedia::stopIndexing()
     */
    protected function stopIndexing(
        $tableName,
        $sourceRecord,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options,
    ) {
        if ('sys_file_metadata' == $tableName) {
            $this->getInternalIndexService()->addRecordToIndex(
                'sys_file',
                $sourceRecord['file']
            );

            return true;
        }

        return parent::stopIndexing($tableName, $sourceRecord, $indexDoc, $options);
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_indexer_BaseMedia::hasDocToBeDeleted()
     */
    protected function hasDocToBeDeleted(
        $tableName,
        $sourceRecord,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options = [],
    ): bool {
        $filePath = $this->getFilePath($tableName, $sourceRecord);
        if (!TYPO3\CMS\Core\Utility\PathUtility::isAbsolutePath($filePath)) {
            $filePath = Sys25\RnBase\Utility\Environment::getPublicPath().$filePath;
        }

        return ($sourceRecord['deleted'] ?? false) || ($sourceRecord['missing'] ?? false) || !file_exists($filePath.($sourceRecord['name'] ?? ''));
    }

    /**
     * @return tx_mksearch_service_internal_Base
     */
    protected function getInternalIndexService()
    {
        return tx_mksearch_util_ServiceRegistry::getIntIndexService();
    }

    /**
     * Return the default Typoscript configuration for this indexer.
     *
     * Note that this config is not used for actual indexing
     * but only serves as assistance when actually configuring an indexer!
     * Hence all possible configuration options should be set or
     * at least be mentioned to provide an easy-to-access inline documentation!
     */
    public function getDefaultTSConfig(): string
    {
        $defaultTsConfig = parent::getDefaultTSConfig();

        return $defaultTsConfig.<<<CFG

# put all references into queue when indexing a file
#queueReferencedRecords = 1

CFG;
    }
}
