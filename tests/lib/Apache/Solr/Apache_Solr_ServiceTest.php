<?php

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class Apache_Solr_ServiceTest extends tx_mksearch_tests_Testcase
{
    public function testCommitCallsSendRawPostWithWaitFlushParameterIfNotSolr4(): void
    {
        $service = $this->getMockBuilder('Apache_Solr_Service')
            ->onlyMethods(['_sendRawPost'])
            ->getMock();
        $service->setCompatibilityLayer(new Apache_Solr_Compatibility_Solr3CompatibilityLayer());

        $expectedUrl = 'http://localhost:8180/solr/update?wt=json';
        $expectedRawPostWithWaitFlushParameter =
            '<commit expungeDeletes="false" waitFlush="true" waitSearcher="true" />';
        $expectedTimeout = 3600;

        $service->expects($this->once())
            ->method('_sendRawPost')
            ->with($expectedUrl, $expectedRawPostWithWaitFlushParameter, $expectedTimeout);

        $service->commit();
    }

    public function testCommitCallsSendRawPostWithoutWaitFlushParameterIfSolr4(): void
    {
        $service = $this->getMockBuilder('Apache_Solr_Service')
            ->onlyMethods(['_sendRawPost'])
            ->getMock();
        $service->setCompatibilityLayer(new Apache_Solr_Compatibility_Solr4CompatibilityLayer());

        $expectedUrl = 'http://localhost:8180/solr/update?wt=json';
        $expectedRawPostWithWaitFlushParameter =
            '<commit expungeDeletes="false" softCommit="false" waitSearcher="true" />';
        $expectedTimeout = 3600;

        $service->expects($this->once())
            ->method('_sendRawPost')
            ->with($expectedUrl, $expectedRawPostWithWaitFlushParameter, $expectedTimeout);

        $service->commit();
    }

    public function testOptimizeCallsSendRawPostWithWaitFlushParameterIfNotSolr4(): void
    {
        $service = $this->getMockBuilder('Apache_Solr_Service')
            ->onlyMethods(['_sendRawPost'])
            ->getMock();
        $service->setCompatibilityLayer(new Apache_Solr_Compatibility_Solr3CompatibilityLayer());

        $expectedUrl = 'http://localhost:8180/solr/update?wt=json';
        $expectedRawPostWithWaitFlushParameter =
            '<optimize waitFlush="true" waitSearcher="true" />';
        $expectedTimeout = 3600;

        $service->expects($this->once())
            ->method('_sendRawPost')
            ->with($expectedUrl, $expectedRawPostWithWaitFlushParameter, $expectedTimeout);

        $service->optimize();
    }

    public function testOptimizeCallsSendRawPostWithoutWaitFlushParameterIfSolr4(): void
    {
        $service = $this->getMockBuilder('Apache_Solr_Service')
            ->onlyMethods(['_sendRawPost'])
            ->getMock();
        $service->setCompatibilityLayer(new Apache_Solr_Compatibility_Solr4CompatibilityLayer());

        $expectedUrl = 'http://localhost:8180/solr/update?wt=json';
        $expectedRawPostWithWaitFlushParameter =
            '<optimize waitSearcher="true" />';
        $expectedTimeout = 3600;

        $service->expects($this->once())
            ->method('_sendRawPost')
            ->with($expectedUrl, $expectedRawPostWithWaitFlushParameter, $expectedTimeout);

        $service->optimize();
    }
}
