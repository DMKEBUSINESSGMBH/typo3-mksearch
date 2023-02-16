<?php

namespace DMK\Mksearch\EventListener\ResourceStorage;

use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;

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
 * Class PutFilesIntoQueueListener.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class PutFilesIntoQueueListener
{
    /**
     * @var \tx_mksearch_service_internal_Index
     */
    protected $indexService;

    public function __construct(\tx_mksearch_service_internal_Index $indexService)
    {
        $this->indexService = $indexService;
    }

    /**
     * @param AfterFileAddedEvent|AfterFileDeletedEvent|AfterFileCopiedEvent|AfterFileMovedEvent|AfterFileRenamedEvent|AfterFileReplacedEvent $event
     */
    public function handleEvent($event): void
    {
        $file = $event instanceof AfterFileCopiedEvent ? $event->getNewFile() : $event->getFile();
        $this->indexService->addRecordToIndex('sys_file', $file->getProperty('uid'));
    }
}
