<?php

namespace DMK\Mksearch\ViewHelpers\Format;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
/*
 * DMK\Mksearch\ViewHelpers\Format$CropViewHelper
 *
 * nähere Infos in Configuration/XClasses.php
 *
 * @package         TYPO3
 * @subpackage      mksearch
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
\tx_rnbase::load('tx_rnbase_util_TYPO3');
if (\tx_rnbase_util_TYPO3::isTYPO70OrHigher()) {
    class CropViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Format\CropViewHelper
    {
        /**
         * nähere Infos in Configuration/XClasses.php.
         */
        protected static function simulateFrontendEnvironment()
        {
            if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
                parent::simulateFrontendEnvironment();
            }
        }

        /**
         * @see simulateFrontendEnvironment()
         */
        protected static function resetFrontendEnvironment()
        {
            if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
                parent::resetFrontendEnvironment();
            }
        }
    }
} else {
    // needed in TYPO3 6.2 without composer (question is why?)
    \tx_rnbase::load('tx_mksearch_service_internal_Index');
    class CropViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Format\CropViewHelper
    {
        /**
         * nähere Infos in Configuration/XClasses.php.
         */
        protected function simulateFrontendEnvironment()
        {
            if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
                parent::simulateFrontendEnvironment();
            }
        }

        /**
         * @see simulateFrontendEnvironment()
         */
        protected function resetFrontendEnvironment()
        {
            if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
                parent::resetFrontendEnvironment();
            }
        }
    }
}
