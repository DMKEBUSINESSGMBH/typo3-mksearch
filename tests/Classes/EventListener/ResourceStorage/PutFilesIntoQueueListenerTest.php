<?php

namespace DMK\Mksearch\EventListener\ResourceStorage;

use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;

/**
 *  Copyright notice.
 *
 *  (c) Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * Class PutFilesIntoQueueListenerTest.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class PutFilesIntoQueueListenerTest extends \Sys25\RnBase\Testing\BaseTestCase
{
    /**
     * @group unit
     *
     * @dataProvider dataProviderHandleEvent
     */
    public function testRenderWhenDefaultValueShouldNotBeSet($event)
    {
        $indexService = $this->getMockBuilder('tx_mksearch_service_internal_Index')
            ->getMock();
        $indexService->expects(self::once())
            ->method('addRecordToIndex')
            ->with('sys_file', 123);

        $eventListener = new PutFilesIntoQueueListener($indexService);
        $eventListener->handleEvent($event);
    }

    /**
     * @return string[][][]
     */
    public function dataProviderHandleEvent()
    {
        $folder = $this->getMockBuilder(Folder::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            [new AfterFileAddedEvent($this->getFileMock(), $folder)],
            [new AfterFileDeletedEvent($this->getFileMock(), $folder)],
            [new AfterFileCopiedEvent(
                $this->getMockBuilder(FileInterface::class)->getMock(),
                $folder,
                '',
                $this->getFileMock()
            )],
            [new AfterFileMovedEvent($this->getFileMock(), $folder, $folder)],
            [new AfterFileRenamedEvent($this->getFileMock(), '')],
            [new AfterFileReplacedEvent($this->getFileMock(), '')],
        ];
    }

    protected function getFileMock(): FileInterface
    {
        $file = $this->getMockBuilder(FileInterface::class)
            ->getMock();
        $file->expects(self::once())
            ->method('getProperty')
            ->with('uid')
            ->willReturn(123);

        return $file;
    }
}
