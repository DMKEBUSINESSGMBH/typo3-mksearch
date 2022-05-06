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
 * Indexes seminars with the object_type = 0.
 */
class tx_mksearch_indexer_seminars_SeminarObjectType0 extends tx_mksearch_indexer_seminars_Seminar
{
    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_Indexer::prepareSearchData()
     */
    public function prepareSearchData($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options)
    {
        // we have to init the seminar again
        $this->oSeminar = $this->getSeminar($rawData);

        // now we can start the real indexing
        // those functions are provided by our parent
        $this->indexSeminar($indexDoc);
        $this->indexSeminarCategories($indexDoc);
        $this->indexSeminarTargetGroups($indexDoc);
        // those functions are provided by this class
        $this->indexSeminarOrganizers($indexDoc);
        $this->indexSeminarPlaces($indexDoc);
        $this->indexSeminarSpeakers($indexDoc);
        $this->indexSeminarTimeslots($indexDoc);
        // @todo handle skills of speakers, tutors etc and everything
        // about lodgings, foods and payments

        return $indexDoc;
    }

    /**
     * Indexes everything about the seminar target groups.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     */
    protected function indexSeminarOrganizers(tx_mksearch_interface_IndexerDocument $indexDoc)
    {
        // Mapping which function fills which field
        $aFunctionFieldMapping = $this->getOrganizersMapping();

        $oOrganizers = $this->oSeminar->getOrganizerBag();

        $aTempIndexDoc = $this->getMultiValueFieldsByListObject($oOrganizers, $aFunctionFieldMapping);
        foreach ($aTempIndexDoc as $sIndexKey => $mValue) {
            if (!empty($mValue)) {
                $indexDoc->addField($sIndexKey, $mValue);
            }
        }
    }

    /**
     * Indexes everything about the seminar places.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     */
    protected function indexSeminarPlaces(tx_mksearch_interface_IndexerDocument $indexDoc)
    {
        // Mapping which function fills which field
        $aFunctionFieldMapping = $this->getPlacesMapping();

        $oPlaces = $this->oSeminar->getPlaces();

        $aTempIndexDoc = $this->getMultiValueFieldsByListObject($oPlaces, $aFunctionFieldMapping);
        foreach ($aTempIndexDoc as $sIndexKey => $mValue) {
            if (!empty($mValue)) {
                $indexDoc->addField($sIndexKey, $mValue);
            }
        }
    }

    /**
     * Indexes everything about the seminar speakers.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     */
    protected function indexSeminarSpeakers(tx_mksearch_interface_IndexerDocument $indexDoc)
    {
        // Mapping which function fills which field
        $aFunctionFieldMapping = $this->getSpeakersMapping();
        $oSpeakers = $this->getSpeakerBag($this->oSeminar->getUid());

        $aTempIndexDoc = $this->getMultiValueFieldsByListObject($oSpeakers, $aFunctionFieldMapping);

        foreach ($aTempIndexDoc as $sIndexKey => $mValue) {
            if (!empty($mValue)) {
                $indexDoc->addField($sIndexKey, $mValue);
            }
        }
    }

    /**
     * Indexes everything about the seminar timeslots.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     */
    protected function indexSeminarTimeslots(tx_mksearch_interface_IndexerDocument $indexDoc)
    {
        // Mapping which function fills which field
        $aTimeslots = $this->oSeminar->getTimeSlotsAsArrayWithMarkers();

        $aRecordFieldMapping = $this->getTimeslotsMapping();
        // as the speakers will be a comma separated list we have to make
        // an array out of it
        foreach ($aTimeslots as &$aTimeslot) {
            $aTimeslot['speakers'] = \Sys25\RnBase\Utility\Strings::trimExplode(',', $aTimeslot['speakers']);
        }
        $aTempIndexDoc = $this->getMultiValueFieldsByArray($aTimeslots, $aRecordFieldMapping);

        // now we index the collected fields
        $this->indexArrayByMapping($indexDoc, $aRecordFieldMapping, $aTempIndexDoc);
    }
}
