<?php

/**
 * Model for indices.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_model_internal_Keyword extends \Sys25\RnBase\Domain\Model\BaseModel
{
    /**
     * Return this model's table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'tx_mksearch_keywords';
    }
}
