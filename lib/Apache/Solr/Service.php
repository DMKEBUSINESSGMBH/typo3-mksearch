<?php
/**
 * Copyright (c) 2007-2012, Servigistics, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  - Neither the name of Servigistics, Inc. nor the names of
 *    its contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright Copyright 2007-2012 Servigistics, Inc. (http://servigistics.com)
 * @license http://solr-php-client.googlecode.com/svn/trunk/COPYING New BSD
 *
 * @version $Id$
 *
 * @author Donovan Jimenez <djimenez@conduit-it.com>
 */

/**
 * Starting point for the Solr API. Represents a Solr server resource and has
 * methods for pinging, adding, deleting, committing, optimizing and searching.
 *
 * Example Usage:
 * <code>
 * ...
 * $solr = new Apache_Solr_Service(); //or explicitly new Apache_Solr_Service('localhost', 8180, '/solr')
 *
 * if ($solr->ping())
 * {
 * 		$solr->deleteByQuery('*:*'); //deletes ALL documents - be careful :)
 *
 * 		$document = new Apache_Solr_Document();
 * 		$document->id = uniqid(); //or something else suitably unique
 *
 * 		$document->title = 'Some Title';
 * 		$document->content = 'Some content for this wonderful document. Blah blah blah.';
 *
 * 		$solr->addDocument($document); 	//if you're going to be adding documents in bulk using addDocuments
 * 										//with an array of documents is faster
 *
 * 		$solr->commit(); //commit to see the deletes and the document
 * 		$solr->optimize(); //merges multiple segments into one
 *
 * 		//and the one we all care about, search!
 * 		//any other common or custom parameters to the request handler can go in the
 * 		//optional 4th array argument.
 * 		$solr->search('content:blah', 0, 10, array('sort' => 'timestamp desc'));
 * }
 * ...
 * </code>
 *
 * @todo Investigate using other HTTP clients other than file_get_contents built-in handler. Could provide performance
 * improvements when dealing with multiple requests by using HTTP's keep alive functionality
 */
class Apache_Solr_Service
{
    /**
     * SVN Revision meta data for this class.
     */
    const SVN_REVISION = '$Revision$';

    /**
     * SVN ID meta data for this class.
     */
    const SVN_ID = '$Id$';

    /**
     * Response writer we'll request - JSON. See http://code.google.com/p/solr-php-client/issues/detail?id=6#c1 for reasoning.
     */
    const SOLR_WRITER = 'json';

    /**
     * NamedList Treatment constants.
     */
    const NAMED_LIST_FLAT = 'flat';
    const NAMED_LIST_MAP = 'map';

    /**
     * Search HTTP Methods.
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * Servlet mappings.
     */
    const PING_SERVLET = 'admin/ping';
    const UPDATE_SERVLET = 'update';
    const SEARCH_SERVLET = 'select';
    const SYSTEM_SERVLET = 'admin/system';
    const THREADS_SERVLET = 'admin/threads';
    const EXTRACT_SERVLET = 'update/extract';

    /**
     * Server identification strings.
     *
     * @var string
     */
    protected $_host;
    protected $_port;
    protected $_path;

    /**
     * Whether {@link Apache_Solr_Response} objects should create {@link Apache_Solr_Document}s in
     * the returned parsed data.
     *
     * @var bool
     */
    protected $_createDocuments = true;

    /**
     * Whether {@link Apache_Solr_Response} objects should have multivalue fields with only a single value
     * collapsed to appear as a single value would.
     *
     * @var bool
     */
    protected $_collapseSingleValueArrays = true;

    /**
     * How NamedLists should be formatted in the output.  This specifically effects facet counts. Valid values
     * are {@link Apache_Solr_Service::NAMED_LIST_MAP} (default) or {@link Apache_Solr_Service::NAMED_LIST_FLAT}.
     *
     * @var string
     */
    protected $_namedListTreatment = self::NAMED_LIST_MAP;

    /**
     * Query delimiters. Someone might want to be able to change
     * these (to use &amp; instead of & for example), so I've provided them.
     *
     * @var string
     */
    protected $_queryDelimiter = '?';
    protected $_queryStringDelimiter = '&';
    protected $_queryBracketsEscaped = true;

    /**
     * Constructed servlet full path URLs.
     *
     * @var string
     */
    protected $_pingUrl;
    protected $_updateUrl;
    protected $_searchUrl;
    protected $_systemUrl;
    protected $_threadsUrl;
    protected $_extractUrl;

    /**
     * Keep track of whether our URLs have been constructed.
     *
     * @var bool
     */
    protected $_urlsInited = false;

    /**
     * HTTP Transport implementation (pluggable).
     *
     * @var Apache_Solr_HttpTransport_Interface
     */
    protected $_httpTransport = false;

    /**
     * @var Apache_Solr_Compatibility_CompatibilityLayer
     */
    protected $_compatibilityLayer;

    public string $lastUrl = '';

    /**
     * Escape a value for special query characters such as ':', '(', ')', '*', '?', etc.
     *
     * NOTE: inside a phrase fewer characters need escaped, use {@link Apache_Solr_Service::escapePhrase()} instead
     *
     * @param string $value
     *
     * @return string
     */
    public static function escape($value)
    {
        //list taken from http://lucene.apache.org/java/docs/queryparsersyntax.html#Escaping%20Special%20Characters
        $pattern = '/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }

    /**
     * Escape a value meant to be contained in a phrase for special query characters.
     *
     * @param string $value
     *
     * @return string
     */
    public static function escapePhrase($value)
    {
        $pattern = '/("|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }

    /**
     * Convenience function for creating phrase syntax from a value.
     *
     * @param string $value
     *
     * @return string
     */
    public static function phrase($value)
    {
        return '"'.self::escapePhrase($value).'"';
    }

    /**
     * Constructor. All parameters are optional and will take on default values
     * if not specified.
     *
     * @param string                              $host
     * @param string                              $port
     * @param string                              $path
     * @param Apache_Solr_HttpTransport_Interface $httpTransport
     */
    public function __construct(
        $host = 'localhost',
        $port = 8180,
        $path = '/solr/',
        $httpTransport = false,
        $compatibilityLayer = false
    ) {
        $this->setHost($host);
        $this->setPort($port);
        $this->setPath($path);

        $this->_initUrls();

        if ($httpTransport) {
            $this->setHttpTransport($httpTransport);
        }

        if (false !== $compatibilityLayer) {
            if ($compatibilityLayer instanceof Apache_Solr_Compatibility_CompatibilityLayer) {
                $this->setCompatibilityLayer($compatibilityLayer);
            } else {
                throw new Apache_Solr_InvalidArgumentException("Given compatibility layer doesn't implement Apache_Solr_Compatibility_CompatibilityLayer");
            }
        } else {
            $this->setCompatibilityLayer(new Apache_Solr_Compatibility_Solr3CompatibilityLayer());
        }

        // check that our php version is >= 5.1.3 so we can correct for http_build_query behavior later
        $this->_queryBracketsEscaped = version_compare(phpversion(), '5.1.3', '>=');
    }

    /**
     * Return a valid http URL given this server's host, port and path and a provided servlet name.
     *
     * @param string $servlet
     *
     * @return string
     */
    protected function _constructUrl($servlet, $params = [])
    {
        if (count($params)) {
            //escape all parameters appropriately for inclusion in the query string
            $escapedParams = [];

            foreach ($params as $key => $value) {
                $escapedParams[] = urlencode($key).'='.urlencode($value);
            }

            $queryString = $this->_queryDelimiter.implode($this->_queryStringDelimiter, $escapedParams);
        } else {
            $queryString = '';
        }

        $protocol = (0 === strpos((string) $this->_host, 'http', 0)) ? '' : 'http://';

        return $protocol.$this->_host.':'.$this->_port.$this->_path.$servlet.$queryString;
    }

    /**
     * Construct the Full URLs for the three servlets we reference.
     */
    protected function _initUrls()
    {
        //Initialize our full servlet URLs now that we have server information
        $this->_extractUrl = $this->_constructUrl(self::EXTRACT_SERVLET);
        $this->_pingUrl = $this->_constructUrl(self::PING_SERVLET);
        $this->_searchUrl = $this->_constructUrl(self::SEARCH_SERVLET);
        $this->_systemUrl = $this->_constructUrl(self::SYSTEM_SERVLET, ['wt' => self::SOLR_WRITER]);
        $this->_threadsUrl = $this->_constructUrl(self::THREADS_SERVLET, ['wt' => self::SOLR_WRITER]);
        $this->_updateUrl = $this->_constructUrl(self::UPDATE_SERVLET, ['wt' => self::SOLR_WRITER]);

        $this->_urlsInited = true;
    }

    protected function _generateQueryString($params)
    {
        // use http_build_query to encode our arguments because its faster
        // than urlencoding all the parts ourselves in a loop
        //
        // because http_build_query treats arrays differently than we want to, correct the query
        // string by changing foo[#]=bar (# being an actual number) parameter strings to just
        // multiple foo=bar strings. This regex should always work since '=' will be urlencoded
        // anywhere else the regex isn't expecting it
        //
        // NOTE: before php 5.1.3 brackets were not url encoded by http_build query - we've checked
        // the php version in the constructor and put the results in the instance variable. Also, before
        // 5.1.2 the arg_separator parameter was not available, so don't use it
        if ($this->_queryBracketsEscaped) {
            $queryString = http_build_query($params, null, $this->_queryStringDelimiter);

            return preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $queryString);
        } else {
            $queryString = http_build_query($params);

            return preg_replace('/\\[(?:[0-9]|[1-9][0-9]+)\\]=/', '=', $queryString);
        }
    }

    /**
     * Central method for making a get operation against this Solr Server.
     *
     * @param string $url
     * @param float  $timeout Read timeout in seconds
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If a non 200 response status is returned
     */
    protected function _sendRawGet($url, $timeout = false)
    {
        $this->lastUrl = $url; // debug info
        $httpTransport = $this->getHttpTransport();

        $httpResponse = $httpTransport->performGetRequest($url, $timeout);
        $solrResponse = new Apache_Solr_Response($httpResponse, $this->_createDocuments, $this->_collapseSingleValueArrays);

        if (200 != $solrResponse->getHttpStatus()) {
            throw new Apache_Solr_HttpTransportException($solrResponse);
        }

        return $solrResponse;
    }

    /**
     * Central method for making a post operation against this Solr Server.
     *
     * @param string $url
     * @param string $rawPost
     * @param float  $timeout     Read timeout in seconds
     * @param string $contentType
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If a non 200 response status is returned
     */
    protected function _sendRawPost($url, $rawPost, $timeout = false, $contentType = 'text/xml; charset=UTF-8')
    {
        $this->lastUrl = $url; // . "\nPOSTDATA:\n" . $rawPost; // debug info
        $httpTransport = $this->getHttpTransport();

        $httpResponse = $httpTransport->performPostRequest($url, $rawPost, $contentType, $timeout);
        $solrResponse = new Apache_Solr_Response($httpResponse, $this->_createDocuments, $this->_collapseSingleValueArrays);

        if (200 != $solrResponse->getHttpStatus()) {
            throw new Apache_Solr_HttpTransportException($solrResponse);
        }

        return $solrResponse;
    }

    /**
     * Returns the set host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Set the host used. If empty will fallback to constants.
     *
     * @param string $host
     *
     * @throws Apache_Solr_InvalidArgumentException If the host parameter is empty
     */
    public function setHost($host)
    {
        //Use the provided host or use the default
        if (empty($host)) {
            throw new Apache_Solr_InvalidArgumentException('Host parameter is empty');
        } else {
            $this->_host = $host;
        }

        if ($this->_urlsInited) {
            $this->_initUrls();
        }
    }

    /**
     * Get the set port.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * Set the port used. If empty will fallback to constants.
     *
     * @param int $port
     *
     * @throws Apache_Solr_InvalidArgumentException If the port parameter is empty
     */
    public function setPort($port)
    {
        //Use the provided port or use the default
        $port = (int) $port;

        if ($port <= 0) {
            throw new Apache_Solr_InvalidArgumentException('Port is not a valid port number');
        } else {
            $this->_port = $port;
        }

        if ($this->_urlsInited) {
            $this->_initUrls();
        }
    }

    /**
     * Get the set path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Set the path used. If empty will fallback to constants.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $path = trim($path, '/');

        if (strlen($path) > 0) {
            $this->_path = '/'.$path.'/';
        } else {
            $this->_path = '/';
        }

        if ($this->_urlsInited) {
            $this->_initUrls();
        }
    }

    /**
     * Get the current configured HTTP Transport.
     *
     * @return HttpTransportInterface
     */
    public function getHttpTransport()
    {
        // lazy load a default if one has not be set
        if (false === $this->_httpTransport) {
            $this->_httpTransport = new Apache_Solr_HttpTransport_FileGetContents();
        }

        return $this->_httpTransport;
    }

    /**
     * Set the HTTP Transport implemenation that will be used for all HTTP requests.
     *
     * @param Apache_Solr_HttpTransport_Interface
     */
    public function setHttpTransport(Apache_Solr_HttpTransport_Interface $httpTransport)
    {
        $this->_httpTransport = $httpTransport;
    }

    /**
     * @return Apache_Solr_Compatibility_CompatibilityLayer
     */
    public function getCompatibilityLayer()
    {
        return $this->_compatibilityLayer;
    }

    /**
     * @param Apache_Solr_Compatibility_CompatibilityLayer $compatibilityLayer
     */
    public function setCompatibilityLayer($compatibilityLayer)
    {
        $this->_compatibilityLayer = $compatibilityLayer;
    }

    /**
     * Set the create documents flag. This determines whether {@link Apache_Solr_Response} objects will
     * parse the response and create {@link Apache_Solr_Document} instances in place.
     *
     * @param bool $createDocuments
     */
    public function setCreateDocuments($createDocuments)
    {
        $this->_createDocuments = (bool) $createDocuments;
    }

    /**
     * Get the current state of teh create documents flag.
     *
     * @return bool
     */
    public function getCreateDocuments()
    {
        return $this->_createDocuments;
    }

    /**
     * Set the collapse single value arrays flag.
     *
     * @param bool $collapseSingleValueArrays
     */
    public function setCollapseSingleValueArrays($collapseSingleValueArrays)
    {
        $this->_collapseSingleValueArrays = (bool) $collapseSingleValueArrays;
    }

    /**
     * Get the current state of the collapse single value arrays flag.
     *
     * @return bool
     */
    public function getCollapseSingleValueArrays()
    {
        return $this->_collapseSingleValueArrays;
    }

    /**
     * Get the current default timeout setting (initially the default_socket_timeout ini setting)
     * in seconds.
     *
     * @return float
     *
     * @deprecated Use the getDefaultTimeout method on the HTTP transport implementation
     */
    public function getDefaultTimeout()
    {
        return $this->getHttpTransport()->getDefaultTimeout();
    }

    /**
     * Set the default timeout for all calls that aren't passed a specific timeout.
     *
     * @param float $timeout Timeout value in seconds
     *
     * @deprecated Use the setDefaultTimeout method on the HTTP transport implementation
     */
    public function setDefaultTimeout($timeout)
    {
        $this->getHttpTransport()->setDefaultTimeout($timeout);
    }

    /**
     * Convenience method to set authentication credentials on the current HTTP transport implementation.
     *
     * @param string $username
     * @param string $password
     */
    public function setAuthenticationCredentials($username, $password)
    {
        $this->getHttpTransport()->setAuthenticationCredentials($username, $password);
    }

    /**
     * Set how NamedLists should be formatted in the response data. This mainly effects
     * the facet counts format.
     *
     * @param string $namedListTreatment
     *
     * @throws Apache_Solr_InvalidArgumentException If invalid option is set
     */
    public function setNamedListTreatment($namedListTreatment)
    {
        switch ((string) $namedListTreatment) {
            case Apache_Solr_Service::NAMED_LIST_FLAT:
                $this->_namedListTreatment = Apache_Solr_Service::NAMED_LIST_FLAT;
                break;

            case Apache_Solr_Service::NAMED_LIST_MAP:
                $this->_namedListTreatment = Apache_Solr_Service::NAMED_LIST_MAP;
                break;

            default:
                throw new Apache_Solr_InvalidArgumentException('Not a valid named list treatement option');
        }
    }

    /**
     * Get the current setting for named list treatment.
     *
     * @return string
     */
    public function getNamedListTreatment()
    {
        return $this->_namedListTreatment;
    }

    /**
     * Set the string used to separate the path form the query string.
     * Defaulted to '?'.
     *
     * @param string $queryDelimiter
     */
    public function setQueryDelimiter($queryDelimiter)
    {
        $this->_queryDelimiter = $queryDelimiter;
    }

    /**
     * Set the string used to separate the parameters in thequery string
     * Defaulted to '&'.
     *
     * @param string $queryStringDelimiter
     */
    public function setQueryStringDelimiter($queryStringDelimiter)
    {
        $this->_queryStringDelimiter = $queryStringDelimiter;
    }

    /**
     * Call the /admin/ping servlet, can be used to quickly tell if a connection to the
     * server is able to be made.
     *
     * @param float $timeout maximum time to wait for ping in seconds, -1 for unlimited (default is 2)
     *
     * @return float Actual time taken to ping the server, FALSE if timeout or HTTP error status occurs
     */
    public function ping($timeout = 2)
    {
        $start = microtime(true);

        $httpTransport = $this->getHttpTransport();

        $httpResponse = $httpTransport->performHeadRequest($this->_pingUrl, $timeout);
        $solrResponse = new Apache_Solr_Response($httpResponse, $this->_createDocuments, $this->_collapseSingleValueArrays);

        if (200 == $solrResponse->getHttpStatus()) {
            return microtime(true) - $start;
        } else {
            return false;
        }
    }

    /**
     * Call the /admin/system servlet and retrieve system information about Solr.
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function system()
    {
        return $this->_sendRawGet($this->_systemUrl);
    }

    /**
     * Call the /admin/threads servlet and retrieve information about all threads in the
     * Solr servlet's thread group. Useful for diagnostics.
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function threads()
    {
        return $this->_sendRawGet($this->_threadsUrl);
    }

    /**
     * Raw Add Method. Takes a raw post body and sends it to the update service.  Post body
     * should be a complete and well formed "add" xml document.
     *
     * @param string $rawPost
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function add($rawPost)
    {
        return $this->_sendRawPost($this->_updateUrl, $rawPost);
    }

    /**
     * Add a Solr Document to the index.
     *
     * @param Apache_Solr_Document $document
     * @param bool                 $allowDups
     * @param bool                 $overwritePending
     * @param bool                 $overwriteCommitted
     * @param int                  $commitWithin       The number of milliseconds that a document must be committed within, see {@link http://wiki.apache.org/solr/UpdateXmlMessages#The_Update_Schema} for details.  If left empty this property will not be set in the request.
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function addDocument(Apache_Solr_Document $document, $allowDups = false, $overwritePending = true, $overwriteCommitted = true, $commitWithin = 0)
    {
        $documentXmlFragment = $this->_documentToXmlFragment($document);

        return $this->addRawDocuments(
            $documentXmlFragment,
            $allowDups,
            $overwritePending,
            $overwriteCommitted,
            $commitWithin
        );
    }

    /**
     * Add an array of Solr Documents to the index all at once.
     *
     * @param array $documents          Should be an array of Apache_Solr_Document instances
     * @param bool  $allowDups
     * @param bool  $overwritePending
     * @param bool  $overwriteCommitted
     * @param int   $commitWithin       The number of milliseconds that a document must be committed within, see {@link http://wiki.apache.org/solr/UpdateXmlMessages#The_Update_Schema} for details.  If left empty this property will not be set in the request.
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function addDocuments($documents, $allowDups = false, $overwritePending = true, $overwriteCommitted = true, $commitWithin = 0)
    {
        $documentsXmlFragment = '';

        foreach ($documents as $document) {
            if ($document instanceof Apache_Solr_Document) {
                $documentsXmlFragment .= $this->_documentToXmlFragment($document);
            }
        }

        return $this->addRawDocuments(
            $documentsXmlFragment,
            $allowDups,
            $overwritePending,
            $overwriteCommitted,
            $commitWithin
        );
    }

    /**
     * @param $documentsXmlFragment
     * @param $allowDups
     * @param $overwritePending
     * @param $overwriteCommitted
     * @param $commitWithin
     *
     * @return Apache_Solr_Response
     */
    private function addRawDocuments(
        $documentsXmlFragment,
        $allowDups,
        $overwritePending,
        $overwriteCommitted,
        $commitWithin
    ) {
        $dupValue = $allowDups ? 'true' : 'false';
        $pendingValue = $overwritePending ? 'true' : 'false';
        $committedValue = $overwriteCommitted ? 'true' : 'false';

        $commitWithin = (int) $commitWithin;
        $commitWithinString = $commitWithin > 0 ? " commitWithin=\"{$commitWithin}\"" : '';

        $compatibilityLayer = $this->getCompatibilityLayer();

        if ($compatibilityLayer instanceof Apache_Solr_Compatibility_AddDocumentXmlCreator) {
            $rawPost = $compatibilityLayer->createAddDocumentXmlFragment(
                $documentsXmlFragment,
                $allowDups,
                $overwritePending,
                $overwriteCommitted,
                $commitWithin
            );
        } else {
            $rawPost = "<add allowDups=\"{$dupValue}\" overwritePending=\"{$pendingValue}\" "
                ."overwriteCommitted=\"{$committedValue}\"{$commitWithinString}>";
            $rawPost .= $documentsXmlFragment;
            $rawPost .= '</add>';
        }

        return $this->add($rawPost);
    }

    /**
     * Create an XML fragment from a {@link Apache_Solr_Document} instance appropriate for use inside a Solr add call.
     *
     * @return string
     */
    protected function _documentToXmlFragment(Apache_Solr_Document $document)
    {
        $xml = '<doc';

        if (false !== $document->getBoost()) {
            $xml .= ' boost="'.$document->getBoost().'"';
        }

        $xml .= '>';

        foreach ($document as $key => $value) {
            $key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            $fieldBoost = $document->getFieldBoost($key);

            if (is_array($value)) {
                foreach ($value as $multivalue) {
                    $xml .= '<field name="'.$key.'"';

                    if (false !== $fieldBoost) {
                        $xml .= ' boost="'.$fieldBoost.'"';

                        // only set the boost for the first field in the set
                        $fieldBoost = false;
                    }

                    $multivalue = htmlspecialchars($multivalue, ENT_NOQUOTES, 'UTF-8');

                    $xml .= '>'.$multivalue.'</field>';
                }
            } else {
                $xml .= '<field name="'.$key.'"';

                if (false !== $fieldBoost) {
                    $xml .= ' boost="'.$fieldBoost.'"';
                }

                $value = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');

                $xml .= '>'.$value.'</field>';
            }
        }

        $xml .= '</doc>';

        // replace any control characters to avoid Solr XML parser exception
        return $this->_stripCtrlChars($xml);
    }

    /**
     * Replace control (non-printable) characters from string that are invalid to Solr's XML parser with a space.
     *
     * @param string $string
     *
     * @return string
     */
    protected function _stripCtrlChars($string)
    {
        // See:  http://w3.org/International/questions/qa-forms-utf-8.html
        // Printable utf-8 does not include any of these chars below x7F
        return preg_replace('@[\x00-\x08\x0B\x0C\x0E-\x1F]@', ' ', $string);
    }

    /**
     * Send a commit command.  Will be synchronous unless both wait parameters are set to false.
     *
     * @param bool  $expungeDeletes Defaults to false, merge segments with deletes away
     * @param bool  $waitFlush      Defaults to true,  block until index changes are flushed to disk
     * @param bool  $waitSearcher   Defaults to true, block until a new searcher is opened and registered as the main query searcher, making the changes visible
     * @param float $timeout        Maximum expected duration (in seconds) of the commit operation on the server (otherwise, will throw a communication exception). Defaults to 1 hour
     * @param bool  $softCommit     whether to perform a soft commit instead of a hard commit
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function commit($expungeDeletes = false, $waitFlush = true, $waitSearcher = true, $timeout = 3600, $softCommit = false)
    {
        $rawPost = $this->getCompatibilityLayer()->createCommitXml(
            $expungeDeletes,
            $waitFlush,
            $waitSearcher,
            $timeout,
            $softCommit
        );

        return $this->_sendRawPost($this->_updateUrl, $rawPost, $timeout);
    }

    /**
     * Send a soft commit command. Will be synchronous unless both wait parameters are set to false.
     *
     * @param bool  $expungeDeletes Defaults to false, merge segments with deletes away
     * @param bool  $waitFlush      Defaults to true,  block until index changes are flushed to disk
     * @param bool  $waitSearcher   Defaults to true, block until a new searcher is opened and registered as the main query searcher, making the changes visible
     * @param float $timeout        Maximum expected duration (in seconds) of the commit operation on the server (otherwise, will throw a communication exception). Defaults to 1 hour
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function softCommit($expungeDeletes = false, $waitFlush = true, $waitSearcher = true, $timeout = 3600)
    {
        return $this->commit($expungeDeletes, $waitFlush, $waitSearcher, $timeout, true);
    }

    /**
     * Raw Delete Method. Takes a raw post body and sends it to the update service. Body should be
     * a complete and well formed "delete" xml document.
     *
     * @param string $rawPost Expected to be utf-8 encoded xml document
     * @param float  $timeout Maximum expected duration of the delete operation on the server (otherwise, will throw a communication exception)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function delete($rawPost, $timeout = 3600)
    {
        return $this->_sendRawPost($this->_updateUrl, $rawPost, $timeout);
    }

    /**
     * Create a delete document based on document ID.
     *
     * @param string $id            Expected to be utf-8 encoded
     * @param bool   $fromPending
     * @param bool   $fromCommitted
     * @param float  $timeout       Maximum expected duration of the delete operation on the server (otherwise, will throw a communication exception)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function deleteById($id, $fromPending = true, $fromCommitted = true, $timeout = 3600)
    {
        $pendingValue = $fromPending ? 'true' : 'false';
        $committedValue = $fromCommitted ? 'true' : 'false';

        //escape special xml characters
        $id = htmlspecialchars($id, ENT_NOQUOTES, 'UTF-8');

        $rawPost = '<delete fromPending="'.$pendingValue.'" fromCommitted="'.$committedValue.'"><id>'.$id.'</id></delete>';

        return $this->delete($rawPost, $timeout);
    }

    /**
     * Create and post a delete document based on multiple document IDs.
     *
     * @param array $ids           Expected to be utf-8 encoded strings
     * @param bool  $fromPending
     * @param bool  $fromCommitted
     * @param float $timeout       Maximum expected duration of the delete operation on the server (otherwise, will throw a communication exception)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function deleteByMultipleIds($ids, $fromPending = true, $fromCommitted = true, $timeout = 3600)
    {
        $pendingValue = $fromPending ? 'true' : 'false';
        $committedValue = $fromCommitted ? 'true' : 'false';

        $rawPost = '<delete fromPending="'.$pendingValue.'" fromCommitted="'.$committedValue.'">';

        foreach ($ids as $id) {
            //escape special xml characters
            $id = htmlspecialchars($id, ENT_NOQUOTES, 'UTF-8');

            $rawPost .= '<id>'.$id.'</id>';
        }

        $rawPost .= '</delete>';

        return $this->delete($rawPost, $timeout);
    }

    /**
     * Create a delete document based on a query and submit it.
     *
     * @param string $rawQuery      Expected to be utf-8 encoded
     * @param bool   $fromPending
     * @param bool   $fromCommitted
     * @param float  $timeout       Maximum expected duration of the delete operation on the server (otherwise, will throw a communication exception)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function deleteByQuery($rawQuery, $fromPending = true, $fromCommitted = true, $timeout = 3600)
    {
        $pendingValue = $fromPending ? 'true' : 'false';
        $committedValue = $fromCommitted ? 'true' : 'false';

        // escape special xml characters
        $rawQuery = htmlspecialchars($rawQuery, ENT_NOQUOTES, 'UTF-8');

        $rawPost = '<delete fromPending="'.$pendingValue.'" fromCommitted="'.$committedValue.'"><query>'.$rawQuery.'</query></delete>';

        return $this->delete($rawPost, $timeout);
    }

    /**
     * Use Solr Cell to extract document contents. See {@link http://wiki.apache.org/solr/ExtractingRequestHandler} for information on how
     * to use Solr Cell and what parameters are available.
     *
     * NOTE: when passing an Apache_Solr_Document instance, field names and boosts will automatically be prepended by "literal." and "boost."
     * as appropriate. Any keys from the $params array will NOT be treated this way. Any mappings from the document will overwrite key / value
     * pairs in the params array if they have the same name (e.g. you pass a "literal.id" key and value in your $params array but you also
     * pass in a document isntance with an "id" field" - the document's value(s) will take precedence).
     *
     * @param string               $file     Path to file to extract data from
     * @param array                $params   optional array of key value pairs that will be sent with the post (see Solr Cell documentation)
     * @param Apache_Solr_Document $document optional document that will be used to generate post parameters (literal.* and boost.* params)
     * @param string               $mimetype optional mimetype specification (for the file being extracted)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_InvalidArgumentException if $file, $params, or $document are invalid
     */
    public function extract($file, $params = [], $document = null, $mimetype = 'application/octet-stream')
    {
        // check if $params is an array (allow null for default empty array)
        if (!is_null($params)) {
            if (!is_array($params)) {
                throw new Apache_Solr_InvalidArgumentException('$params must be a valid array or null');
            }
        } else {
            $params = [];
        }

        // if $file is an http request, defer to extractFromUrl instead
        if ('http://' == substr($file, 0, 7) || 'https://' == substr($file, 0, 8)) {
            return $this->extractFromUrl($file, $params, $document, $mimetype);
        }

        // read the contents of the file
        $contents = @file_get_contents($file);

        if (false !== $contents) {
            // add the resource.name parameter if not specified
            if (!isset($params['resource.name'])) {
                $params['resource.name'] = basename($file);
            }

            // delegate the rest to extractFromString
            return $this->extractFromString($contents, $params, $document, $mimetype);
        } else {
            throw new Apache_Solr_InvalidArgumentException("File '{$file}' is empty or could not be read");
        }
    }

    /**
     * Use Solr Cell to extract document contents. See {@link http://wiki.apache.org/solr/ExtractingRequestHandler} for information on how
     * to use Solr Cell and what parameters are available.
     *
     * NOTE: when passing an Apache_Solr_Document instance, field names and boosts will automatically be prepended by "literal." and "boost."
     * as appropriate. Any keys from the $params array will NOT be treated this way. Any mappings from the document will overwrite key / value
     * pairs in the params array if they have the same name (e.g. you pass a "literal.id" key and value in your $params array but you also
     * pass in a document isntance with an "id" field" - the document's value(s) will take precedence).
     *
     * @param string               $data     Data that will be passed to Solr Cell
     * @param array                $params   optional array of key value pairs that will be sent with the post (see Solr Cell documentation)
     * @param Apache_Solr_Document $document optional document that will be used to generate post parameters (literal.* and boost.* params)
     * @param string               $mimetype optional mimetype specification (for the file being extracted)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_InvalidArgumentException if $file, $params, or $document are invalid
     *
     * @todo Should be using multipart/form-data to post parameter values, but I could not get my implementation to work. Needs revisisted.
     */
    public function extractFromString($data, $params = [], $document = null, $mimetype = 'application/octet-stream')
    {
        // check if $params is an array (allow null for default empty array)
        if (!is_null($params)) {
            if (!is_array($params)) {
                throw new Apache_Solr_InvalidArgumentException('$params must be a valid array or null');
            }
        } else {
            $params = [];
        }

        // make sure we receive our response in JSON and have proper name list treatment
        $params['wt'] = self::SOLR_WRITER;
        $params['json.nl'] = $this->_namedListTreatment;

        // check if $document is an Apache_Solr_Document instance
        if (!is_null($document) && $document instanceof Apache_Solr_Document) {
            // iterate document, adding literal.* and boost.* fields to $params as appropriate
            foreach ($document as $field => $fieldValue) {
                // check if we need to add a boost.* parameters
                $fieldBoost = $document->getFieldBoost($field);

                if (false !== $fieldBoost) {
                    $params["boost.{$field}"] = $fieldBoost;
                }

                // add the literal.* parameter
                $params["literal.{$field}"] = $fieldValue;
            }
        }

        // params will be sent to SOLR in the QUERY STRING
        $queryString = $this->_generateQueryString($params);

        // the file contents will be sent to SOLR as the POST BODY - we use application/octect-stream as default mimetype
        return $this->_sendRawPost($this->_extractUrl.$this->_queryDelimiter.$queryString, $data, false, $mimetype);
    }

    /**
     * Use Solr Cell to extract document contents. See {@link http://wiki.apache.org/solr/ExtractingRequestHandler} for information on how
     * to use Solr Cell and what parameters are available.
     *
     * NOTE: when passing an Apache_Solr_Document instance, field names and boosts will automatically be prepended by "literal." and "boost."
     * as appropriate. Any keys from the $params array will NOT be treated this way. Any mappings from the document will overwrite key / value
     * pairs in the params array if they have the same name (e.g. you pass a "literal.id" key and value in your $params array but you also
     * pass in a document isntance with an "id" field" - the document's value(s) will take precedence).
     *
     * @param string               $url      URL
     * @param array                $params   optional array of key value pairs that will be sent with the post (see Solr Cell documentation)
     * @param Apache_Solr_Document $document optional document that will be used to generate post parameters (literal.* and boost.* params)
     * @param string               $mimetype optional mimetype specification (for the file being extracted)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_InvalidArgumentException if $url, $params, or $document are invalid
     */
    public function extractFromUrl($url, $params = [], $document = null, $mimetype = 'application/octet-stream')
    {
        // check if $params is an array (allow null for default empty array)
        if (!is_null($params)) {
            if (!is_array($params)) {
                throw new Apache_Solr_InvalidArgumentException('$params must be a valid array or null');
            }
        } else {
            $params = [];
        }

        $httpTransport = $this->getHttpTransport();

        // read the contents of the URL using our configured Http Transport and default timeout
        $httpResponse = $httpTransport->performGetRequest($url);

        // check that its a 200 response
        if (200 == $httpResponse->getStatusCode()) {
            // add the resource.name parameter if not specified
            if (!isset($params['resource.name'])) {
                $params['resource.name'] = $url;
            }

            // delegate the rest to extractFromString
            return $this->extractFromString($httpResponse->getBody(), $params, $document, $mimetype);
        } else {
            throw new Apache_Solr_InvalidArgumentException("URL '{$url}' returned non 200 response code");
        }
    }

    /**
     * Send an optimize command.  Will be synchronous unless both wait parameters are set
     * to false.
     *
     * @param bool  $waitFlush
     * @param bool  $waitSearcher
     * @param float $timeout      Maximum expected duration of the commit operation on the server (otherwise, will throw a communication exception)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException If an error occurs during the service call
     */
    public function optimize($waitFlush = true, $waitSearcher = true, $timeout = 3600)
    {
        $rawPost = $this->getCompatibilityLayer()->createOptimizeXml(
            $waitFlush,
            $waitSearcher,
            $timeout
        );

        return $this->_sendRawPost($this->_updateUrl, $rawPost, $timeout);
    }

    /**
     * Simple Search interface.
     *
     * @param string $query  The raw query string
     * @param int    $offset The starting offset for result documents
     * @param int    $limit  The maximum number of result documents to return
     * @param array  $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
     * @param string $method The HTTP method (Apache_Solr_Service::METHOD_GET or Apache_Solr_Service::METHOD::POST)
     *
     * @return Apache_Solr_Response
     *
     * @throws Apache_Solr_HttpTransportException   If an error occurs during the service call
     * @throws Apache_Solr_InvalidArgumentException If an invalid HTTP method is used
     */
    public function search($query, $offset = 0, $limit = 10, $params = [], $method = self::METHOD_GET)
    {
        // ensure params is an array
        if (!is_null($params)) {
            if (!is_array($params)) {
                // params was specified but was not an array - invalid
                throw new Apache_Solr_InvalidArgumentException('$params must be a valid array or null');
            }
        } else {
            $params = [];
        }

        // construct our full parameters

        // common parameters in this interface
        $params['wt'] = self::SOLR_WRITER;
        $params['json.nl'] = $this->_namedListTreatment;

        $params['q'] = $query;
        $params['start'] = $offset;
        $params['rows'] = $limit;

        $queryString = $this->_generateQueryString($params);

        if (self::METHOD_GET == $method) {
            return $this->_sendRawGet($this->_searchUrl.$this->_queryDelimiter.$queryString);
        } elseif (self::METHOD_POST == $method) {
            return $this->_sendRawPost($this->_searchUrl, $queryString, false, 'application/x-www-form-urlencoded; charset=UTF-8');
        } else {
            throw new Apache_Solr_InvalidArgumentException("Unsupported method '$method', please use the Apache_Solr_Service::METHOD_* constants");
        }
    }
}
