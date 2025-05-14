<?php

class Apache_Solr_Compatibility_Solr4CompatibilityLayer implements Apache_Solr_Compatibility_CompatibilityLayer, Apache_Solr_Compatibility_AddDocumentXmlCreator
{
    /**
     * Creates a commit command XML string.
     *
     * @param bool  $expungeDeletes Defaults to false, merge segments with deletes away
     * @param bool  $waitFlush      defaults to true, is ignored
     * @param bool  $waitSearcher   defaults to true, block until a new searcher is opened and registered as the main query searcher, making the changes visible
     * @param float $timeout        Maximum expected duration (in seconds) of the commit operation on the server (otherwise, will throw a communication exception). Defaults to 1 hour
     * @param bool  $softCommit     defaults to false, perform a soft commit instead of a hard commit
     *
     * @return string An XML string
     */
    public function createCommitXml($expungeDeletes = false, $waitFlush = true, $waitSearcher = true, $timeout = 3600, $softCommit = false): string
    {
        $expungeValue = $expungeDeletes ? 'true' : 'false';
        $searcherValue = $waitSearcher ? 'true' : 'false';
        $softCommitValue = $softCommit ? 'true' : 'false';

        return '<commit expungeDeletes="'.$expungeValue.'" softCommit="'.$softCommitValue.'" waitSearcher="'.$searcherValue.'" />';
    }

    /**
     * Creates an optimize command XML string.
     *
     * @param bool  $waitFlush    is ignored
     * @param bool  $waitSearcher
     * @param float $timeout      Maximum expected duration of the commit operation on the server (otherwise, will throw a communication exception)
     *
     * @return string An XML string
     */
    public function createOptimizeXml($waitFlush = true, $waitSearcher = true): string
    {
        $searcherValue = $waitSearcher ? 'true' : 'false';

        return '<optimize waitSearcher="'.$searcherValue.'" />';
    }

    /**
     * Creates an add command XML string.
     *
     * @param string $rawDocuments       string containing XML representation of documents
     * @param bool   $allowDups
     * @param bool   $overwritePending
     * @param bool   $overwriteCommitted
     * @param int    $commitWithin       The number of milliseconds that a document must be committed within,
     *                                   see {@link http://wiki.apache.org/solr/UpdateXmlMessages#The_Update_Schema} for details. If left empty
     *                                   this property will not be set in the request.
     *
     * @return string An XML string
     */
    public function createAddDocumentXmlFragment(
        $rawDocuments,
        $allowDups = false,
        $overwritePending = true,
        $overwriteCommitted = true,
        $commitWithin = 0
    ): string {
        $dupValue = $allowDups ? 'false' : 'true';

        $commitWithin = (int) $commitWithin;
        $commitWithinString = $commitWithin > 0 ? sprintf(' commitWithin="%d"', $commitWithin) : '';

        $addXmlFragment = sprintf('<add overwrite="%s"%s>', $dupValue, $commitWithinString);
        $addXmlFragment .= $rawDocuments;

        return $addXmlFragment . '</add>';
    }
}
