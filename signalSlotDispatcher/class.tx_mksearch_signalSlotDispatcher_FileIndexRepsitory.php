<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_service
 *  @author Hannes Bochmann
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
 * tx_mksearch_signalSlotDispatcher_FileIndexRepsitory
 *
 * @package 		TYPO3
 * @subpackage		mksearch
 * @author 			Hannes Bochmann <dev@dmk-ebusiness.de>
 * @license 		http://www.gnu.org/licenses/lgpl.html
 * 					GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_signalSlotDispatcher_FileIndexRepsitory {

	/**
	 * @param integer || array $fileData
	 */
	public function putFileIntoQueue($data){
		$uid = is_array($data) ? $data['uid'] : $data;

		$internalIndexService = $this->getInternalIndexService();
		$internalIndexService->addRecordToIndex('sys_file', $uid);

		// testen ob singnal slots korrekt registriert
	}

	/**
	 *
	 * @return tx_mksearch_service_internal_Index
	 */
	protected function getInternalIndexService() {
		return tx_mksearch_util_ServiceRegistry::getIntIndexService();
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/signalSlotDispatcher/class.tx_mksearch_signalSlotDispatcher_FileIndexRepsitory.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/signalSlotDispatcher/class.tx_mksearch_signalSlotDispatcher_FileIndexRepsitory.php']);
}