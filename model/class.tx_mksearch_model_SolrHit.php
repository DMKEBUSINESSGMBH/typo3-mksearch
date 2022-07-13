<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 DMK E-Business GmbH
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
 * Model for search hits from solt.
 *
 * As the base data doesn't come from a real table but gets filled
 * by the search engine some things are different from an usual
 * rn_base model. We use it anyway to keep all the remaining nice
 * functions like automatic marker filling etc.
 */
class tx_mksearch_model_SolrHit extends \Sys25\RnBase\Domain\Model\BaseModel implements tx_mksearch_interface_SearchHit
{
    private $solrDoc;

    /**
     * Initialiaze model and fill it with data if provided.
     *
     * @param $rowOrUid
     */
    public function init($rowOrUid = null)
    {
        $solrDoc = $rowOrUid;
        if (!$solrDoc instanceof Apache_Solr_Document) {
            throw new InvalidArgumentException('The solr doc has to be an object instance of "Apache_Solr_Document",'.'"'.(is_object($solrDoc) ? get_class($solrDoc) : gettype($solrDoc)).'" given.', 1370252783);
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
     *
     * @return string
     */
    public function getTableName()
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
    public function getColumnNames()
    {
        return 0;
    }

    /**
     * @see #getColumnNames()
     */
    public function getTCAColumns()
    {
        return 0;
    }
}
