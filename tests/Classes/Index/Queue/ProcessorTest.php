<?php
namespace DMK\Mksearch\Tests\ViewIndex\Queue;

use DMK\Mksearch\Index\Queue\Processor;


class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testTriggerQueueIndexingWithEmptyQueue()
    {
        $indexSrvMock = $this->getMockBuilder(\tx_mksearch_service_internal_Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->setMethods(['retrieveQueueItems'])
            ->getMock();
        $processor->expects($this->once())
            ->method('retrieveQueueItems')
            ->with(['limit'=>10])
            ->willReturn([]);
        
        $this->assertEquals(0, $processor->triggerQueueIndexing($indexSrvMock, ['limit'=>10]));
    }

    public function testTriggerQueueIndexing()
    {
        $indexSrvMock = $this->getMockBuilder(\tx_mksearch_service_internal_Index::class)
            ->disableOriginalConstructor()
            ->setMethods(['findAll'])
            ->getMock();
        $indexSrvMock->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->setMethods(['retrieveQueueItems', 'log', 'deleteOldQueueEntries', 'deleteQueueEntries'])
            ->getMock();
        $processor->expects($this->once())
            ->method('retrieveQueueItems')
            ->with(['limit'=>10])
            ->willReturn([
                $this->buildQueueItemNews(),
            ]);
        $this->assertEquals(0, $processor->triggerQueueIndexing($indexSrvMock, ['limit'=>10]));
    }
    private function buildIndex()
    {
//        $index = new \tx_mksearch_model_internal_Index();
    }
    private function buildQueueItemNews()
    {
        return [
            'uid' => 2,
            'cr_date' => '',
            'prefer' => 0,
            'recid' => 4,
            'tablename' => 'tt_news',
            'data'=>null,
            'resolver' => ''
            
        ];
    }
}

