<?php

namespace DMK\Mksearch\Backend\Form\Element;

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
 * DMK\Mksearch\Backend\Form\Element$IndexerConfigurationField.
 *
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class IndexerConfigurationField extends \TYPO3\CMS\Backend\Form\Element\TextElement
{
    /**
     * (non-PHPdoc).
     *
     * @see TYPO3\CMS\Backend\Form\Element\TextElement::render()
     */
    public function render(): array
    {
        $extKey = $this->data['databaseRow']['extkey'][0] ?? '';
        $contentType = $this->data['databaseRow']['contenttype'][0] ?? '';
        if (!($this->data['databaseRow']['configuration'] ?? false) && $extKey && $contentType) {
            try {
                $this->data['parameterArray']['itemFormElValue'] = \tx_mksearch_util_Config::getIndexerDefaultTSConfig(
                    $extKey, $contentType
                );
            } catch (\Exception $e) {
                // "Service not found" exception is thrown for invalid $extkey/$contenttype combinations
                // which may temporarily occur on changing the $extkey!
            }
        }

        return $this->callRenderOnParent();
    }

    /**
     * (non-PHPdoc).
     *
     * @see TYPO3\CMS\Backend\Form\Element\TextElement::render()
     */
    protected function callRenderOnParent()
    {
        return parent::render();
    }
}
