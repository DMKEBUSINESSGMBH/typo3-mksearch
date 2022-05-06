<?php
/**
 * @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * Base service class.
 */
abstract class tx_mksearch_service_Base extends \Sys25\RnBase\Typo3Wrapper\Service\AbstractService
{
    /**
     * Return name of search class.
     *
     * @return string
     */
    abstract public function getSearchClass();

    /**
     * @return \Sys25\RnBase\Search\SearchBase
     */
    public function getSearcher()
    {
        return \Sys25\RnBase\Search\SearchBase::getInstance($this->getSearchClass());
    }

    /**
     * Search database.
     *
     * @param array $fields
     * @param array $options
     *
     * @return array[\Sys25\RnBase\Domain\Model\BaseModel]
     */
    public function search(array $fields, array $options)
    {
        $searcher = $this->getSearcher();

        // On default, return hidden and deleted fields in backend
        // @TODO: realy return deleted fields? make Konfigurable!
        if (TYPO3_MODE == 'BE' &&
        !isset($options['enablefieldsoff']) &&
        !isset($options['enablefieldsbe']) &&
        !isset($options['enablefieldsfe'])
        ) {
            $options['enablefieldsoff'] = true;
        }

        return $searcher->search($fields, $options);
    }

    /**
     * Search the item for the given uid.
     *
     * @TODO:   Achtung,
     *          \Sys25\RnBase\Search\SearchBase::getWrapperClass() ist eigentlich protected!
     *
     * @param int $ct
     *
     * @return \Sys25\RnBase\Domain\Model\BaseModel
     */
    public function get($uid)
    {
        $searcher = \Sys25\RnBase\Search\SearchBase::getInstance($this->getSearchClass());

        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($searcher->getWrapperClass(), $uid);
    }

    /**
     * Find all records.
     *
     * @return array[\Sys25\RnBase\Domain\Model\BaseModel]
     */
    public function findAll()
    {
        return $this->search([], []);
    }
}
