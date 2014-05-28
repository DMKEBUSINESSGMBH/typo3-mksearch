<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 René Nitzsche <nitzsche@das-medienkombinat.de>
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
***************************************************************/

require_once t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php';
if (!class_exists('tx_scheduler_Task')) {
	require_once t3lib_extMgm::extPath('scheduler', 'class.tx_scheduler_task.php');
}

/**
 *
 */
class tx_mksearch_scheduler_IndexTask extends tx_scheduler_Task {

	/**
	 * Amount of items to be indexed at one run
	 *
	 * @var	int
	 */
	 private $amountOfItems;

	/**
	 * Function executed from the Scheduler.
	 * Sends an email
	 *
	 * @return	void
	 */
	public function execute() {
		$success = true;

		try {
			$rows = tx_mksearch_util_ServiceRegistry::getIntIndexService()->triggerQueueIndexing($this->getAmountOfItems());
			if(!empty($rows))//sonst gibts ne PHP Warning bei array_merge
				$rows = count(call_user_func_array('array_merge', array_values($rows)));
			$msg = sprintf($rows ? '%d item(s) indexed' : 'No items in indexing queue.' , $rows);
			if($rows) // TODO: Schalter im Task anlegen.
				tx_rnbase_util_Logger::info($msg, 'mksearch');
		} catch (Exception $e) {
			tx_rnbase_util_Logger::fatal('Indexing failed!', 'mksearch', array('Exception' => $e->getMessage()));
			//Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
			//also machen wir das manuell
			if($addr = tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
				tx_rnbase::load('tx_rnbase_util_Misc');
				//die Mail soll immer geschickt werden
				$aOptions = array('ignoremaillock' => true);
				tx_rnbase_util_Misc::sendErrorMail($addr, 'tx_mksearch_scheduler_IndexTask', $e, $aOptions);
			}
			$success = false;
		}

		return $success;
	}

	/**
	 * Return amount of items
	 *
	 * @return int
	 */
	public function getAmountOfItems() {
		return $this->amountOfItems;
	}

	/**
	 * Set amount of items
	 *
	 * @param int	$val
	 * @return void
	 */
	public function setAmountOfItems($val) {
		$val = intval($val);

		if ($val <= 0)
			throw new Exception('tx_mksearch_scheduler_TaskTriggerIndexingQueue->setAmountOfItems(): Invalid amount of items given!');
		// else
		$this->amountOfItems = $val;
	}

	/**
	 * This method returns the destination mail address as additional information
	 *
	 * @return	string	Information to display
	 */
	public function getAdditionalInformation() {
		return sprintf(	$GLOBALS['LANG']->sL('LLL:EXT:mksearch/locallang_db.xml:scheduler_indexTask_taskinfo'),
			$this->getAmountOfItems(), $this->getItemsInQueue());
	}
	private function getItemsInQueue() {
		return tx_mksearch_util_ServiceRegistry::getIntIndexService()->countItemsInQueue();
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_IndexTask.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_IndexTask.php']);
}