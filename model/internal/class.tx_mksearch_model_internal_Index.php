<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Model for indices.
 */
class tx_mksearch_model_internal_Index extends \Sys25\RnBase\Domain\Model\BaseModel
{
    private $options = false;

    /**
     * Index service instance.
     *
     * @var tx_mksearch_interface_SearchEngine
     */
    private $indexSrv;

    /**
     * Return this model's table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'tx_mksearch_indices';
    }

    /**
     * Returns the plain credential string for this index.
     *
     * @return string
     */
    public function getCredentialString()
    {
        return $this->getProperty('name');
    }

    /**
     * Returns the index title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getProperty('title');
    }

    /**
     * Returns the search engine type.
     *
     * @return string
     */
    public function getEngineType()
    {
        return $this->getProperty('engine');
    }

    /**
     * @return tx_mksearch_interface_SearchEngine
     */
    public function getSearchEngine()
    {
        return tx_mksearch_util_ServiceRegistry::getSearchEngine($this);
    }

    /**
     * Return options of all active indexers for this index.
     *
     * @return array
     */
    public function getIndexerOptions()
    {
        if (!$this->options) {
            /*
             * momentan werden hier normal die konfigurationen der indexer ausgelesen.
             * zusätzlich werden die konfigurationen der composites ausgelesen und später zusammen gemerged.
             *  1. besser wäre, dies hier zu mergen!
             *  2. haben wir das problem, das sich mehrere composites gegenseite ergänzen
             *     und sich auf alle indexer auswirken.
             *   Beispiel:
             *  	composite 1
             *  		indexer 1
             *  		indexer 2
             *  	composite 2
             *  		indexer 3
             *  		indexer 4
             *  	composite 3
             *  		indexer 1
             *  		indexer 3
             *  	die konfiguration von composite 1 - 3 werden zusammengeführt
             *  	und wirken sich auf alle indexer (1-4) aus.
             *  	richtig wäre:
             *  		composite 1 ist default für indexer 1 und 2
             *  		composite 2 ist default für indexer 3 und 4
             *  		composite 3 ist default für indexer 1 und 3, aber ohne default von composite 1 und 2.
             *  @TODO: umstellen!
             *  @XXX: enthält self::getIndexConfig() ebenfalls default konfigurationen?
             */

            // Prepare search of configurations
            $this->options = tx_mksearch_util_ServiceRegistry::getIntConfigService()
                ->getIndexerOptionsByIndex($this);

            if (empty($this->options['default.']) || !is_array($this->options['default.'])) {
                $this->options['default.'] = [];
            }

            // get default configuation from composite
            $compositeConfig = tx_mksearch_util_ServiceRegistry::getIntCompositeService()
                ->getIndexerOptionsByIndex($this);
            $this->options['default.'] = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                $this->options['default.'],
                $compositeConfig
            );
        }

        return $this->options;
    }

    /**
     * Returns the configuration for this index.
     *
     * @param tx_mksearch_model_internal_Index $oIndex
     *
     * @return array configuration array
     */
    public function getIndexConfig()
    {
        return tx_mksearch_util_Misc::parseTsConfig("{\n".$this->getProperty('configuration')."\n}");
    }

    public function __toString()
    {
        $out = get_class($this)."\n\nRecord:\n";
        foreach ($this->getProperty() as $key => $value) {
            $out .= $key.' = '.$value."\n";
        }
        $out = "\n\nIndexer Options:\n";
        foreach ($this->getIndexerOptions() as $key => $value) {
            $out .= $key.' = '.$value."\n";
        }

        return $out;
    }

    /**
     * Returns version number of search engine.
     *
     * @return int
     */
    public function getEngineVersion()
    {
        // FIXME: Das Feld in der db neutral gestalten
        return $this->getProperty('solrversion');
    }

    /**
     * @return int
     *
     * @deprecated use getEngineVersion()
     */
    public function getSolrVersion()
    {
        return $this->getEngineVersion();
    }

    /**
     * @return bool
     *
     * @deprecated wrong place for engine specific version check
     */
    public function isSolr4()
    {
        return 40 == $this->getSolrVersion();
    }
}
