<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 René Nitzsche <dev@dmk-ebusiness.de>
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

class tx_mksearch_scheduler_IndexTask extends Tx_Rnbase_Scheduler_Task
{
    /**
     * Was used as the scheduler options before making the extension compatible with TYPO3 9. But as private
     * class variables can't be serialized anymore (@see __makeUp() method) this variable can't be used anymore.
     *
     * @var int
     *
     * @deprecated can be removed including the __wakeup() method when support for TYPO3 8.7 and below is dropped.
     */
    private $amountOfItems;

    /**
     * Amount of items to be indexed at one run.
     *
     * @var int
     */
    protected $amountOfItemsToIndexPerRun;

    /**
     * After the update to TYPO3 9 the private $options variable can't be serialized and therefore not saved in the
     * database anymore as our parent implemented the __sleep() method to return the class variables which should be
     * serialized/saved. So to keep the possibly saved $options we need to move them to $schedulerOptions if present.
     * Otherwise the $options will be lost after the scheduler is executed/saved.
     */
    public function __wakeup()
    {
        if (method_exists(parent::class, '__wakeup')) {
            parent::__wakeup();
        }

        if ($this->amountOfItems && !$this->amountOfItemsToIndexPerRun) {
            $this->amountOfItemsToIndexPerRun = $this->amountOfItems;
        }
    }

    /**
     * Function executed from the Scheduler.
     * Sends an email.
     */
    public function execute()
    {
        $success = true;

        if (!$this->areMultipleExecutionsAllowed()) {
            $this->getExecution()->setMultiple(true);
            $this->save();
        }

        try {
            $rows = tx_mksearch_util_ServiceRegistry::getIntIndexService()->triggerQueueIndexing($this->getAmountOfItems());
            if (!empty($rows)) {//sonst gibts ne PHP Warning bei array_merge
                $rows = count(call_user_func_array('array_merge', array_values($rows)));
            }
            $msg = sprintf($rows ? '%d item(s) indexed' : 'No items in indexing queue.', $rows);
            if ($rows) { // TODO: Schalter im Task anlegen.
                tx_rnbase_util_Logger::info($msg, 'mksearch');
            }
        } catch (Exception $e) {
            tx_rnbase_util_Logger::fatal('Indexing failed!', 'mksearch', ['Exception' => $e->getMessage()]);
            //Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
            //also machen wir das manuell
            if ($addr = tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
                //die Mail soll immer geschickt werden
                $aOptions = ['ignoremaillock' => true];
                tx_rnbase_util_Misc::sendErrorMail($addr, 'tx_mksearch_scheduler_IndexTask', $e, $aOptions);
            }
            $success = false;
        }

        return $success;
    }

    /**
     * Return amount of items.
     *
     * @return int
     */
    public function getAmountOfItems()
    {
        return $this->amountOfItemsToIndexPerRun;
    }

    /**
     * Set amount of items.
     *
     * @param int $val
     */
    public function setAmountOfItems($val)
    {
        $val = intval($val);

        if ($val <= 0) {
            throw new Exception('tx_mksearch_scheduler_TaskTriggerIndexingQueue->setAmountOfItems(): Invalid amount of items given!');
        }
        // else
        $this->amountOfItemsToIndexPerRun = $val;
    }

    /**
     * This method returns the destination mail address as additional information.
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        return sprintf(
            $GLOBALS['LANG']->sL('LLL:EXT:mksearch/locallang_db.xml:scheduler_indexTask_taskinfo'),
            $this->getAmountOfItems(),
            $this->getItemsInQueue()
        );
    }

    private function getItemsInQueue()
    {
        return tx_mksearch_util_ServiceRegistry::getIntIndexService()->countItemsInQueue();
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_IndexTask.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_IndexTask.php'];
}
