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
/**
 * DMK\Mksearch\ViewHelpers\Format$CropViewHelper
 *
 * nähere Infos in Configuration/XClasses.php
 *
 * @package 		TYPO3
 * @subpackage		mksearch
 * @author 			Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license 		http://www.gnu.org/licenses/lgpl.html
 * 					GNU Lesser General Public License, version 3 or later
 */
class CropViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Format\CropViewHelper {

	/**
	 * nähere Infos in Configuration/XClasses.php
	 *
	 * @return void
	 */
	protected function simulateFrontendEnvironment() {
		if(!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
			parent::simulateFrontendEnvironment();
		}
	}

	/**
	 * @return void
	 * @see simulateFrontendEnvironment()
	 */
	protected function resetFrontendEnvironment() {
		if(!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
			parent::resetFrontendEnvironment();
		}
	}
}
