<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

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
 * Class PutFilesIntoQueueListenerTest.
 *
 * @author  Hannes Bochmann
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class PutFilesIntoQueueListenerTest extends \Sys25\RnBase\Testing\BaseTestCase
{
    public function testRenderWhenDefaultValueShouldNotBeSetAndAfterFileAddedEvent(): void
    {
        $folder = $this->getMockBuilder(Folder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testRenderWhenDefaultValueShouldNotBeSet(new AfterFileAddedEvent($this->getFileMock(), $folder));
    }

    public function testRenderWhenDefaultValueShouldNotBeSetAndAfterFileDeletedEvent(): void
    {
        $folder = $this->getMockBuilder(Folder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testRenderWhenDefaultValueShouldNotBeSet(new AfterFileDeletedEvent($this->getFileMock(), $folder));
    }

    public function testRenderWhenDefaultValueShouldNotBeSetAndAfterFileCopiedEvent(): void
    {
        $folder = $this->getMockBuilder(Folder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testRenderWhenDefaultValueShouldNotBeSet(
            new AfterFileCopiedEvent(
                $this->getMockBuilder(FileInterface::class)->getMock(),
                $folder,
                '',
                $this->getFileMock()
            )
        );
    }

    public function testRenderWhenDefaultValueShouldNotBeSetAndAfterFileMovedEvent(): void
    {
        $folder = $this->getMockBuilder(Folder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testRenderWhenDefaultValueShouldNotBeSet(new AfterFileMovedEvent($this->getFileMock(), $folder, $folder));
    }

    public function testRenderWhenDefaultValueShouldNotBeSetAndAfterFileRenamedEvent(): void
    {
        $this->testRenderWhenDefaultValueShouldNotBeSet(new AfterFileRenamedEvent($this->getFileMock(), ''));
    }

    public function testRenderWhenDefaultValueShouldNotBeSetAndAfterFileReplacedEvent(): void
    {
        $this->testRenderWhenDefaultValueShouldNotBeSet(new AfterFileReplacedEvent($this->getFileMock(), ''));
    }

    protected function testRenderWhenDefaultValueShouldNotBeSet(AfterFileAddedEvent|AfterFileDeletedEvent|AfterFileCopiedEvent|AfterFileMovedEvent|AfterFileRenamedEvent|AfterFileReplacedEvent $event): void
    {
        $indexService = $this->getMockBuilder('tx_mksearch_service_internal_Index')
            ->getMock();
        $indexService->expects(self::once())
            ->method('addRecordToIndex')
            ->with('sys_file', 123);

        $eventListener = new PutFilesIntoQueueListener($indexService);
        $eventListener->handleEvent($event);
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
