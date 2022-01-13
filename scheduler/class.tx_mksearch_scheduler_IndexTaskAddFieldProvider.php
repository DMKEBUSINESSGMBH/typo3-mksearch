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
define('FIELD_ITEMS', 'amountOfItems');

/**
 * tx_mksearch_scheduler_IndexTaskAddFieldProvider.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_scheduler_IndexTaskAddFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * This method is used to define new fields for adding or editing a task
     * In this case, it adds an email field.
     *
     * @param array                                                     $taskInfo:        reference to the array containing the info used in the add/edit form
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask                    $task:            when editing, reference to the current task object. Null when adding.
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule: reference to the calling object (Scheduler's BE module)
     *
     * @return array Array containg all the information pertaining to the additional fields
     *               The array is multidimensional, keyed to the task class name and each field's id
     *               For each field it provides an associative sub-array with the following:
     *               ['code']        => The HTML code for the field
     *               ['label']       => The label of the field (possibly localized)
     *               ['cshKey']      => The CSH key for the field
     *               ['cshLabel']    => The code of the CSH label
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule)
    {
        $action = $schedulerModule->getCurrentAction();
        // Initialize extra field value
        if (!array_key_exists(FIELD_ITEMS, $taskInfo) || empty($taskInfo[FIELD_ITEMS])) {
            if ('add' == $action) {
                // New task
                $taskInfo[FIELD_ITEMS] = '';
            } elseif ('edit' == $action) {
                // Editing a task, set to internal value if data was not submitted already
                $taskInfo[FIELD_ITEMS] = $task->getAmountOfItems();
            } else {
                // Otherwise set an empty value, as it will not be used anyway
                $taskInfo[FIELD_ITEMS] = '';
            }
        }

        // Write the code for the field
        $fieldID = 'field_'.FIELD_ITEMS;
        // Note: Name qualifier MUST be "tx_scheduler" as the tx_scheduler's BE module is used!
        $fieldCode = '<input type="text" name="tx_scheduler['.FIELD_ITEMS.']" id="'.$fieldID.
                        '" value="'.$taskInfo[FIELD_ITEMS].'" size="10" />';
        $additionalFields = [];
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:scheduler_indexTask_field_'.FIELD_ITEMS,
            'cshKey' => '_MOD_web_txschedulerM1',
        ];

        return $additionalFields;
    }

    /**
     * This method checks any additional data that is relevant to the specific task
     * If the task class is not relevant, the method is expected to return true.
     *
     * @param array                                                     $submittedData:   reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule: reference to the calling object (Scheduler's BE module)
     *
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule)
    {
        return true;
    }

    /**
     * This method is used to save any additional input into the current task object
     * if the task class matches.
     *
     * @param array                           $submittedData: array containing the data submitted by the user
     * @param tx_mksearch_scheduler_IndexTask $task:          reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        $task->setAmountOfItems($submittedData[FIELD_ITEMS]);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_IndexTaskAddFieldProvider.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_IndexTaskAddFieldProvider.php'];
}
