<?php
/**
 * @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 */

/**
 * benötigte Klassen einbinden.
 */

/**
 * Model für eine Suggestion.
 */
class tx_mksearch_model_Suggestion extends \Sys25\RnBase\Domain\Model\BaseModel
{
    /**
     * When we migrated from extending tx_rnbase_model_base to extending \Sys25\RnBase\Domain\Model\BaseModel
     * we lost our public $record property. Problem is that this property is used in JavaScript to create
     * the suggestions. So to keep backwards compatibility we add $record as public.
     *
     * @var array
     */
    public $record;

    protected function init($rowOrUid = null)
    {
        parent::init($rowOrUid);
        $this->record = $this->getRecord();

        return null;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_Suggestion.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_Suggestion.php'];
}
