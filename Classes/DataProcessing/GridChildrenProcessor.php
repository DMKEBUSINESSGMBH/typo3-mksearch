<?php

namespace DMK\Mksearch\DataProcessing;

/***************************************************************
 *  Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <kontakt@dmk-ebusiness.de>
 * All rights reserved
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
 * Class GridChildrenProcessor.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class GridChildrenProcessor extends \GridElementsTeam\Gridelements\DataProcessing\GridChildrenProcessor
{
    protected function processChildRecord(array $record)
    {
        // $this->contentObjectConfiguration['includeCTypesInGridelementRendering.'] is set in
        // tx_mksearch_indexer_ttcontent_Gridelements and used to include only certain CTypes in the rendering
        // during indexing when gridelements is rendered through the data processing lib.
        if (
            ($this->contentObjectConfiguration['includeCTypesInGridelementRendering.'] ?? [])
            && \tx_mksearch_service_internal_Index::isIndexingInProgress()
            && !in_array($record['CType'], $this->contentObjectConfiguration['includeCTypesInGridelementRendering.'])
        ) {
            return;
        }

        parent::processChildRecord($record);
    }
}
