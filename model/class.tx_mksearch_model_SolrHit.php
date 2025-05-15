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
 * Model for search hits from solt.
 *
 * As the base data doesn't come from a real table but gets filled
 * by the search engine some things are different from an usual
 * rn_base model. We use it anyway to keep all the remaining nice
 * functions like automatic marker filling etc.
 */
class tx_mksearch_model_SolrHit extends Sys25\RnBase\Domain\Model\BaseModel implements tx_mksearch_interface_SearchHit
{
    private ?Apache_Solr_Document $solrDoc = null;

    /**
     * @var int
     */
    protected $uid;

    /**
     * Initialiaze model and fill it with data if provided.
     */
    protected function init($rowOrUid = null)
    {
        $solrDoc = $rowOrUid;
        if (!$solrDoc instanceof Apache_Solr_Document) {
            throw new InvalidArgumentException('The solr doc has to be an object instance of "Apache_Solr_Document","'.get_debug_type($solrDoc).'" given.', 1370252783);
        }

        $this->solrDoc = $solrDoc;
        $uidField = $solrDoc->getField('uid');
        $this->uid = is_array($uidField) ? $uidField['value'] : 0;
        foreach ($solrDoc as $key => $value) {
            $this->setProperty($key, $value);
        }
    }

    /**
     * Returns the unique key for Solr.
     *
     * @return string
     */
    public function getSolrId()
    {
        if (!is_object($this->solrDoc)) {
            return '';
        }

        $field = $this->solrDoc->getField('id');

        return (is_array($field)) ? $field['value'] : '';
    }

    /**
     * Return name of model's base table - not used in this model.
     */
    public function getTableName(): string
    {
        return '';
    }

    /**
     * Return $TCA defined table column names.
     * As this model doesn't have a $TCA defined name,
     * return 0 like the original function, when no columns were found.
     *
     * @return 0
     */
    public function getColumnNames(): int
    {
        return 0;
    }

    /**
     * @see #getColumnNames()
     */
    public function getTCAColumns(): int
    {
        return 0;
    }
}
