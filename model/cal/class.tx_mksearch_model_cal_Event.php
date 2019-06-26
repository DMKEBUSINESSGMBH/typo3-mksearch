<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2013-2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 * Cal model.
 *
 * @author Hannes Bochmann
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_model_cal_Event extends tx_rnbase_model_base
{
    /**
     * Calender model for this event.
     *
     * @var tx_mksearch_model_cal_Calendar
     */
    private $calendar = null;

    /**
     * Location model for this event.
     *
     * @var tx_mksearch_model_cal_Location
     */
    private $location = null;

    /**
     * Category models for this event.
     *
     * @var array[tx_mksearch_model_cal_Category]
     */
    private $categories = array();

    /**
     * Tablename.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'tx_cal_event';
    }

    /**
     * Calender model for this event.
     *
     * @return tx_mksearch_model_cal_Calendar
     */
    public function getCalendar()
    {
        if (null === $this->calendar) {
            $this->calendar = tx_rnbase::makeInstance(
                'tx_mksearch_model_cal_Calendar',
                $this->record['calendar_id']
            );
        }

        return $this->calendar;
    }

    /**
     * Location model for this event.
     *
     * @return tx_mksearch_model_cal_Location
     */
    public function getLocation()
    {
        if (null === $this->location) {
            $this->location = tx_rnbase::makeInstance(
                'tx_mksearch_model_cal_Location',
                $this->record['location_id']
            );
        }

        return $this->location;
    }

    /**
     * Category models for this event.
     *
     * @return null|array[tx_mksearch_model_cal_Category]
     */
    public function getCategories()
    {
        if (empty($this->categories)) {
            $categoriesByEvent = $this->getCategoriesByEvent();

            foreach ($categoriesByEvent as $categoryByEvent) {
                $this->categories[] = tx_rnbase::makeInstance(
                    'tx_mksearch_model_cal_Category',
                    $categoryByEvent['uid_foreign']
                );
            }
        }

        return $this->categories;
    }

    /**
     * Category mm rows for this event.
     *
     * @return array
     */
    protected function getCategoriesByEvent()
    {
        return Tx_Rnbase_Database_Connection::getInstance()->doSelect(
            'uid_foreign',
            'tx_cal_event_category_mm AS MM JOIN tx_cal_category AS CAT ON '.
            'MM.uid_foreign = CAT.uid',
            array(
                //da MM keine TCA hat
                'enablefieldsoff' => true,
                //keine versteckten kategorien
                'where' => 'MM.uid_local = '.intval($this->getUid()).' AND CAT.hidden = 0',
            )
        );
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/cal/class.tx_mksearch_model_cal_Event.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/cal/class.tx_mksearch_model_cal_Event.php'];
}
