<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <dev@dmk-ebusiness.de>
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
 * Indexer service for core.tt_address called by the "mksearch" extension.
 */
class tx_mksearch_indexer_TtAddressAddress implements tx_mksearch_interface_Indexer
{
    /**
     * Return content type identification.
     * This identification is part of the indexed data
     * and is used on later searches to identify the search results.
     * You're completely free in the range of values, but take care
     * as you at the same time are responsible for
     * uniqueness (i.e. no overlapping with other content types) and
     * consistency (i.e. recognition) on indexing and searching data.
     *
     * @return array
     */
    public static function getContentType()
    {
        return ['tt_address', 'address'];
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_Indexer::prepareSearchData()
     */
    public function prepareSearchData($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options)
    {
        if ('tt_address' != $tableName) {
            if (\Sys25\RnBase\Utility\Logger::isWarningEnabled()) {
                \Sys25\RnBase\Utility\Logger::warn(__METHOD__.': Unknown table "'.$tableName.'" given.', 'mksearch', ['tableName' => $tableName, 'sourceRecord' => $rawData]);
            }

            return null;
        }

        // include, exclude etc. prüfen
        if (!$this->isIndexableRecord($rawData, $options)) {
            return null; // no need to index
        }

        // TODO: basicly this indexer could inherit from tx_mksearch_indexer_Base
        if ($this->stopIndexing($tableName, $rawData, $indexDoc, $options)) {
            return null;
        }

        $abort = false;
        $boost = 1.0;

        $indexDoc->setUid($rawData['uid']);

        if ($this->hasDocToBeDeleted($rawData, $options)) {
            $indexDoc->setDeleted(true);

            return $indexDoc;
        }

        // Hook to append indexer
        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'indexer_TtAddress_prepareData_beforeAddFields',
            [
                'rawData' => &$rawData,
                'options' => $options,
                'indexDoc' => &$indexDoc,
                'boost' => &$boost,
                'abort' => &$abort,
            ],
            $this
        );

        // Abbrechen, wenn im Hook gesetzt.
        if (false !== $abort) {
            return $abort;
        }

        $indexDoc->addField('hidden', $rawData['hidden'], 'keyword', $boost, 'int');
        $indexDoc->addField('deleted', $rawData['deleted'], 'keyword', $boost, 'int');

        $indexDoc->setTimestamp($rawData['tstamp']);
        $name = $this->getName($rawData);
        $indexDoc->setTitle($name);

        $indexDoc->addField('pid', $rawData['pid'], 'keyword');

        $indexDoc->addField('name_s', $name, 'unindexed', $boost, 'string');
        $indexDoc->addField('gender_s', $rawData['gender'], 'unindexed', $boost, 'string');
        $indexDoc->addField('first_name_s', $rawData['first_name'], 'unindexed', $boost, 'string');
        $indexDoc->addField('middle_name_s', $rawData['middle_name'], 'unindexed', $boost, 'string');
        $indexDoc->addField('last_name_s', $rawData['last_name'], 'unindexed', $boost, 'string');
        $indexDoc->addField('birthday_i', $rawData['birthday'] > 0 ? $rawData['birthday'] : 0, 'unindexed', $boost, 'int');
        $indexDoc->addField('title_name_s', $rawData['title'], 'unindexed', $boost, 'string');
        $indexDoc->addField('email_s', $rawData['email'], 'unindexed', $boost, 'string');
        $indexDoc->addField('phone_s', $rawData['phone'], 'unindexed', $boost, 'string');
        $indexDoc->addField('mobile_s', $rawData['mobile'], 'unindexed', $boost, 'string');
        $indexDoc->addField('www_s', $rawData['www'], 'unindexed', $boost, 'string');
        $indexDoc->addField('address_s', $rawData['address'], 'unindexed', $boost, 'string');
        $indexDoc->addField('building_s', $rawData['building'], 'unindexed', $boost, 'string');
        $indexDoc->addField('room_s', $rawData['room'], 'unindexed', $boost, 'string');
        $indexDoc->addField('company_s', $rawData['company'], 'unindexed', $boost, 'string');
        $indexDoc->addField('city_s', $rawData['city'], 'unindexed', $boost, 'string');
        $indexDoc->addField('zip_s', $rawData['zip'], 'unindexed', $boost, 'string');
        $indexDoc->addField('region_s', $rawData['region'], 'unindexed', $boost, 'string');
        $indexDoc->addField('country_s', $rawData['country'], 'unindexed', $boost, 'string');
        $indexDoc->addField('fax_s', $rawData['fax'], 'unindexed', $boost, 'string');
        $indexDoc->addField('description_s', $rawData['description'], 'unindexed', $boost, 'text');

        // @TODO: adressgruppen integrieren!
        if (!empty($rawData['addressgroup'])) {
            $indexDoc->addField('addressgroup_i', $rawData['addressgroup'], 'unindexed', $boost, 'int');
        }

        $sContent = $this->getContentFromFields($rawData, $options['content.'] ?? []);
        $indexDoc->setContent($sContent);

        $sContent = $this->getContentFromFields($rawData, $options['abstract.'] ?? []);
        $indexDoc->setAbstract('', 1);

        // Hook to append indexer
        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'indexer_TtAddress_prepareData_afterAddFields',
            [
                'rawData' => &$rawData,
                'options' => $options,
                'indexDoc' => &$indexDoc,
            ],
            $this
        );

        return $indexDoc;
    }

    protected function getName(array $ttAddressRecord): string
    {
        return join(
            ' ',
            array_filter(
                [
                    $ttAddressRecord['first_name'] ?? '',
                    $ttAddressRecord['middle_name'] ?? '',
                    $ttAddressRecord['last_name'] ?? '',
                ]
            )
        );
    }

    /**
     * @param array $sourceRecord
     * @param array $options
     *
     * @return bool
     */
    protected function isIndexableRecord($sourceRecord, $options)
    {
        $ret = tx_mksearch_util_Indexer::getInstance()
                ->isOnIndexablePage($sourceRecord, $options);

        return $ret;
    }

    /**
     * Sets the index doc to deleted if neccessary.
     *
     * @param array $sourceRecord
     * @param array $options
     *
     * @return bool
     */
    protected function hasDocToBeDeleted($sourceRecord, $options)
    {
        if (// vom indexer entfernen wenn
            // gelöscht
            $sourceRecord['deleted']
            // hidden
            || (isset($options['removeIfHidden']) && $options['removeIfHidden'] && $sourceRecord['hidden'])
        ) {
            return true;
        }

        // else
        return false;
    }

    /**
     * Shall we break the indexing for the current data?
     *
     * when an indexer is configured for more than one table
     * the index process may be different for the tables.
     * overwrite this method in your child class to stop processing and
     * do something different like putting a record into the queue
     * if it's not the table that should be indexed
     *
     * @param string                                $tableName
     * @param array                                 $sourceRecord
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return bool
     */
    protected function stopIndexing(
        $tableName,
        $sourceRecord,
        tx_mksearch_interface_IndexerDocument $indexDoc,
        $options
    ) {
        return $this->getIndexerUtility()->stopIndexing(
            $tableName,
            $sourceRecord,
            $indexDoc,
            $options
        );
    }

    /**
     * @return tx_mksearch_util_Indexer
     */
    protected function getIndexerUtility()
    {
        return tx_mksearch_util_Indexer::getInstance();
    }

    /**
     * erzeugt Inhalt aus den feldern anhand der Konfiguration.
     *
     * @param array $sourceRecord
     * @param array $options
     *
     * @return string
     */
    protected function getContentFromFields($sourceRecord, $options)
    {
        $aContent = [];
        $aContentFields = \Sys25\RnBase\Utility\Strings::trimExplode(',', $options['fields'] ?? '', true);

        foreach ($aContentFields as $field) {
            if (array_key_exists($field, $sourceRecord) && !empty($sourceRecord[$field])) {
                $aContent[] = trim($sourceRecord[$field]);
            }
        }
        $wrap = \Sys25\RnBase\Utility\Strings::trimExplode('|', $options['wrap'] ?? '', true);
        if (2 != count($wrap)) {
            $wrap = ['', ''];
        }
        $sContent = $wrap[0].implode($wrap[1].$wrap[0], $aContent).$wrap[1];

        // Decode HTML
        $sContent = trim(tx_mksearch_util_Misc::html2plain($sContent));

        return $sContent;
    }

    /**
     * Return the default Typoscript configuration for this indexer.
     *
     * @return string
     */
    public function getDefaultTSConfig()
    {
        return <<<CONF
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields {
#   my_fixed_field_singlevalue = first
#   my_fixed_field_multivalue {
#      0 = first
#      1 = second
#   }
# }

### vom indexer entfernen, wenn auf Hidden gesetzt
removeIfHidden = 1
content {
  ### Felder, die in das conten feld des indexers geschrieben werden.
  fields = name,title,first_name,middle_name,last_name,address,zip,city,country,email,description
  ### Wie werden die Felder für den Content gewrapt?
  wrap = |
}
## siehe content, per default benötigen wir allerdings keinen abstract, da ttaddress ein eigenes template hat
abstract {
  wrap = <span>|</span>
}

### delete from or abort indexing for the record if isIndexableRecord or no record?
 deleteIfNotIndexable = 0

### White lists:
include {
  ### Nur Einträge auf diesen Seiten werden indiziert. (Komma getrennt)
  pages =
  ### Nur Einträge auf diesen Seiten werden indiziert.
  pages {
    #0 = 415
  }
}
### Black lists
exclude {
  ### Einträge auf diesen Seiten werden ignoriert. (Komma getrennt)
  pages =
  ### Nur Einträge auf diesen Seiten werden indiziert.
  pages {
    #0 = 415
  }
}

# you should always configure the root pageTree for this indexer in the includes. mostly the domain
# include.pageTrees {
#   0 = pid-of-domain
# }

# should a special workspace be indexed?
# default is the live workspace (ID = 0)
# comma separated list of workspace IDs
#workspaceIds = 1,2,3
CONF;
    }
}
