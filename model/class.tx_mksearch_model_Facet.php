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
 * benötigte Klassen einbinden.
 */

/**
 * Model für eine Facette.
 */
class tx_mksearch_model_Facet extends \Sys25\RnBase\Domain\Model\BaseModel
{
    const TYPE_FIELD = 'type_field';
    const TYPE_PIVOT = 'type_pivot';
    const TYPE_QUERY = 'type_query';
    const TYPE_RANGE = 'type_range';
    const TYPE_DATE = 'type_date';

    private $childs = [];

    /**
     * Gibt ein Facet Model zurück.
     *
     * @param string $field
     * @param string $id
     * @param mixed  $label Das Label kann ein String, oder ein Array sein
     * @param int    $count
     * @param bool   $head
     */
    public function __construct($field, $id, $label, $count, $head = false)
    {
        $this->setProperty('field', $field);
        $this->setProperty('id', $id);
        $this->setProperty('uid', $id);
        $this->uid = $id;
        if (is_array($label)) {
            // Bei den gruppierten Facets gibt es nicht nur ein Label, sondern mehrere Datenfelder
            $this->setProperty(array_merge($this->getProperty(), $label));
        } else {
            $this->setProperty('label', $label);
        }
        $this->setProperty('count', $count);
        $this->setProperty('head', $head);
        $this->setProperty('type', self::TYPE_FIELD); // Als default ein Field-Facet verwenden
    }

    /**
     * Gibt die Art der Fassette zurück.
     *
     * @return string
     */
    public function getFacetType()
    {
        return $this->getProperty('type');
    }

    /**
     * Setzt die Art der Fassette.
     *
     * @param string $type
     */
    public function setFacetType($type)
    {
        $this->setProperty('type', $type);
    }

    /**
     * adds one ore more child facets.
     *
     * @param mixed <multitype:tx_mksearch_model_Facet, tx_mksearch_model_Facet> $child
     *
     * @return tx_mksearch_model_Facet
     */
    public function addChild($child)
    {
        if ($child instanceof tx_mksearch_model_Facet) {
            $this->childs[] = $child;
        } elseif (is_array($child)) {
            foreach ($child as $sub) {
                $this->addChild($sub);
            }
        }

        return $this;
    }

    /**
     * returns all childs a child facet.
     *
     * @param array <multitype:tx_mksearch_model_Facet, tx_mksearch_model_Facet> $child
     *
     * @return tx_mksearch_model_Facet
     */
    public function setChilds(array $childs)
    {
        $this->childs = [];
        $this->addChild($childs);

        return $this;
    }

    /**
     * returns all childs a child facet.
     *
     * @return multitype:tx_mksearch_model_Facet $child
     */
    public function getChilds()
    {
        return $this->childs;
    }

    /**
     * there are childs?
     *
     * @return bool
     */
    public function hasChilds()
    {
        return !empty($this->childs);
    }

    /**
     * The UID of a facet might not be an integer.
     *
     * @return array|int|string|null
     */
    public function getUid()
    {
        return $this->getProperty('uid');
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_Facet.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_Facet.php'];
}
