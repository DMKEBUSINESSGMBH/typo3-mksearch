<?php

interface Apache_Solr_Compatibility_CompatibilityLayer
{
    /**
     * Creates a commit command XML string.
     *
     * @param bool  $expungeDeletes Defaults to false, merge segments with deletes away
     * @param bool  $waitFlush      Defaults to true,  block until index changes are flushed to disk
     * @param bool  $waitSearcher   Defaults to true, block until a new searcher is opened and registered as the main query searcher, making the changes visible
     * @param float $timeout        Maximum expected duration (in seconds) of the commit operation on the server (otherwise, will throw a communication exception). Defaults to 1 hour
     * @param bool  $softCommit     defaults to false, perform a soft commit instead of a hard commit
     *
     * @return string An XML string
     */
    public function createCommitXml($expungeDeletes = false, $waitFlush = true, $waitSearcher = true, $timeout = 3600, $softCommit = false);

    /**
     * Creates an optimize command XML string.
     *
     * @param bool  $waitFlush
     * @param bool  $waitSearcher
     * @param float $timeout      Maximum expected duration of the commit operation on the server (otherwise, will throw a communication exception)
     *
     * @return string An XML string
     */
    public function createOptimizeXml($waitFlush = true, $waitSearcher = true);
}
