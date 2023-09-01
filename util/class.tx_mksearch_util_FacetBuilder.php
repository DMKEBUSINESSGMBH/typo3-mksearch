<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 - 2015 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 * Der FacetBuilder erstellt aus den Rohdaten
 * der Facets passende Objekte für das Rendering.
 *
 * @author Hannes Bochmann
 * @author Michael Wagner
 */
class tx_mksearch_util_FacetBuilder
{
    /**
     * @var \Sys25\RnBase\Domain\Model\DataModel
     */
    private $options;

    /**
     * @var tx_mksearch_util_KeyValueFacet|null
     */
    private $keyValueFacetInstance;

    /**
     * Get singelton.
     *
     * @param string $class
     * @param array  $options
     *
     * @return tx_mksearch_util_FacetBuilder
     */
    public static function getInstance($class = '', array $options = [])
    {
        static $instance;
        $class = empty($class) ? 'tx_mksearch_util_FacetBuilder' : $class;
        if (!($instance[$class] ?? '')) {
            $instance[$class] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class, $options);
        }

        return $instance[$class];
    }

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(
        array $options = []
    ) {
        $this->options = \Sys25\RnBase\Domain\Model\DataModel::getInstance($options);
    }

    /**
     * The options for this builder.
     *
     * @return \Sys25\RnBase\Domain\Model\DataModel
     */
    protected function getOptions()
    {
        return $this->options;
    }

    /**
     * @return tx_mksearch_util_KeyValueFacet
     */
    protected function getKeyValueFacetInstance()
    {
        if (null === $this->keyValueFacetInstance) {
            $this->keyValueFacetInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'tx_mksearch_util_KeyValueFacet'
            );
        }

        return $this->keyValueFacetInstance;
    }

    /**
     * Baut die Daten für die Facets zusammen.
     *
     * @param array|stdClass $facetData Alle Daten von Solr
     *
     * @return array[] Ausgabedaten
     */
    public function buildFacets($facetData)
    {
        $facetGroups = array_merge(
            $this->buildFieldFacets($facetData->facet_fields ?? null),
            $this->buildQueryFacets($facetData->facet_queries ?? null),
            $this->buildPivotFacets($facetData->facet_pivot ?? null)
        );
        // TODO: RANGE-Facet integrieren

        return $facetGroups;
    }

    /**
     * Query-Facets kommen von Solr nicht in Gruppen strukturiert. Damit wir mehrere Query-Gruppen unterscheiden
     * können, müssen die Queries IMMER mit einem Key angelegt werden. Folgende Form:.
     *
     *  <str name="facet.query">{!key="date_lastweek"}datetime:[NOW-7DAYS/DAY TO NOW]</str>
     *  <str name="facet.query">{!key="date_lastmonth"}datetime:[NOW-1MONTH/MONTH TO NOW]</str>
     *
     * Damit splitten gruppieren wir nach dem String vor dem ersten Unterstrich.
     *
     * @param array[stdClass] $facetData Query-Facet Daten von Solr
     *
     * @return array[\Sys25\RnBase\Domain\Model\BaseModel] Ausgabedaten
     */
    protected function buildQueryFacets($facetData)
    {
        $facetGroups = [];
        if (!$facetData) {
            return $facetGroups;
        }

        $uid = 0;
        foreach ($facetData as $key => $value) {
            list($groupName, $queryName) = explode('_', $key, 2);
            if (!array_key_exists($groupName, $facetGroups)) {
                $facetGroups[$groupName] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \Sys25\RnBase\Domain\Model\BaseModel::class,
                    [
                                'uid' => ++$uid,
                                'field' => $groupName,
                                'items' => [],
                        ]
                );
            }
            $items = $facetGroups[$groupName]->getProperty('items');
            $items[] = $this->getSimpleFacet(
                $groupName,
                $key,
                $value,
                tx_mksearch_model_Facet::TYPE_QUERY
            );
            $facetGroups[$groupName]->setProperty('items', $items);
        }

        return $facetGroups;
    }

    /**
     * Query-Facets kommen von Solr nicht in Gruppen strukturiert. Damit wir mehrere Query-Gruppen unterscheiden
     * können, müssen die Queries IMMER mit einem Key angelegt werden. Folgende Form:.
     *
     *  <str name="facet.query">{!key="date_lastweek"}datetime:[NOW-7DAYS/DAY TO NOW]</str>
     *  <str name="facet.query">{!key="date_lastmonth"}datetime:[NOW-1MONTH/MONTH TO NOW]</str>
     *
     * Damit splitten gruppieren wir nach dem String vor dem ersten Unterstrich.
     *
     * @param array[stdClass] $facetData Query-Facet Daten von Solr
     *
     * @return array[\Sys25\RnBase\Domain\Model\BaseModel] Ausgabedaten
     */
    protected function buildPivotFacets($facetData)
    {
        $facetGroups = [];
        if (!$facetData) {
            return $facetGroups;
        }
        $uid = 0;
        foreach ($facetData as $fields => $pivots) {
            $facetGroups[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
                [
                    'uid' => ++$uid,
                    'field' => implode('-', explode(',', $fields)),
                    'items' => $this->buildPivotChildFacets($pivots),
                ]
            );
        }

        return $facetGroups;
    }

    /**
     * Creates Hierarchical Facets.
     *
     * @param array $pivots
     *
     * @return multitype:tx_mksearch_model_Facet
     */
    protected function buildPivotChildFacets($pivots)
    {
        $fields = [];
        if (empty($pivots) || !is_array($pivots)) {
            return $fields;
        }
        foreach ($pivots as $pivot) {
            $field = $this->getSimpleFacet(
                (string) $pivot->field,
                (string) $pivot->value,
                (string) $pivot->count,
                tx_mksearch_model_Facet::TYPE_PIVOT
            );
            $field->addChild($this->buildPivotChildFacets($pivot->pivot ?? null));
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Baut die Daten für die Field-Facets zusammen.
     *
     * @param array|stdClass $facetData Field-Facet Daten von Solr
     *
     * @return array Ausgabedaten
     */
    protected function buildFieldFacets($facetData)
    {
        $facetGroups = [];
        if (!$facetData) {
            return $facetGroups;
        }
        $uid = 0;
        foreach ($facetData as $field => $facetGroup) {
            if (empty($facetGroups[$field])) {
                $facetGroups[$field] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \Sys25\RnBase\Domain\Model\BaseModel::class,
                    [
                        'uid' => ++$uid,
                        'field' => $field,
                        'items' => [],
                    ]
                );
            }
            foreach ($facetGroup as $id => $count) {
                $items = $facetGroups[$field]->getProperty('items');
                $items[] = $this->getSimpleFacet($field, $id, $count);
                $facetGroups[$field]->setProperty('items', $items);
            }
        }

        return $facetGroups;
    }

    protected function buildGroupedFacet()
    {
    }

    /**
     * Liefert eine simple Facette zurück.
     *
     * @param string $field
     * @param int    $id
     * @param int    $count
     *
     * @return tx_mksearch_model_Facet
     */
    protected function getSimpleFacet(
        $field,
        $id,
        $count,
        $facetType = tx_mksearch_model_Facet::TYPE_FIELD
    ) {
        if ($this->getKeyValueFacetInstance()->checkValue($id)) {
            $exploded = $this->getKeyValueFacetInstance()->explodeFacetValue($id);
            $raw = $id;
            $id = $exploded['key'];
            $title = $exploded['value'];
            $sorting = $exploded['sorting'];
        } else {
            $title = $id;
        }
        $facet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_Facet',
            $field,
            $id,
            $title,
            $count
        );
        $facet->setFacetType($facetType);
        if (isset($sorting)) {
            $facet->setSorting($sorting);
        }
        if (isset($raw)) {
            $facet->setLabelRaw($raw);
        }

        return $facet;
    }

    /**
     * checks the sorting field of the facets and and sorts afterwards.
     *
     * @param array $facets
     *
     * @return array
     */
    public function sortFacets(array $facets)
    {
        // field facets are an instance of \Sys25\RnBase\Domain\Model\BaseModel with childs in "items" of record
        // other facets, like pivot, are an instance of tx_mksearch_model_Facet with childs
        foreach ($facets as $facet) {
            $childs = $facet instanceof tx_mksearch_model_Facet ? $facet->getChilds() : $facet->getItems();
            if (!empty($childs) && is_array($childs)) {
                $childs = $this->sortFacets($childs);
                // set back the sorted items
                $facet instanceof tx_mksearch_model_Facet ? $facet->setChilds($childs) : $facet->setItems($childs);
            }
        }

        // we have sortalbe facets, so sort!
        if ($facet && $facet->hasSorting()) {
            $s = usort(
                $facets,
                [__CLASS__, 'cbSortFacets']
            );
        }

        return $facets;
    }

    /**
     * facet sort calback, is called by sortFacets.
     *
     * @param tx_mksearch_model_Facet $a
     * @param tx_mksearch_model_Facet $b
     *
     * @return int
     */
    public static function cbSortFacets($a, $b)
    {
        if ($a->getSorting() == $b->getSorting()) {
            return 0;
        }

        return ($a->getSorting() < $b->getSorting()) ? -1 : 1;
    }

    /**
     * Debugs the big facet array for better readability.
     *
     * @param mixed  $var
     * @param number $levels
     * @param mixed
     */
    public static function debugFacets($var, $levels = 99)
    {
        if (is_array($var)) {
            foreach ($var as &$sub) {
                $sub = self::debugFacets($sub, $levels);
            }
        } elseif ($var instanceof \Sys25\RnBase\Domain\Model\BaseModel) {
            $childs = $var instanceof tx_mksearch_model_Facet ? $var->getChilds() : $var->getItems();
            $childs = is_array($childs) ? $childs : [];
            $var = array_map('strval', $var->getProperty());
            $var['childs'] = $levels-- <= 0 ? 'length: '.count($childs) : self::debugFacets($childs, $levels);
        }

        return $var;
    }
}
