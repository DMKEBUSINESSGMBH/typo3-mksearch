<?php

namespace Elastica\Multi;

use Elastica\Response;

/**
 * Elastica multi search result set
 * List of result sets for each search request.
 *
 * @author munkie
 */
class ResultSet implements \Iterator, \ArrayAccess, \Countable
{
    /**
     * Result Sets.
     *
     * @var array|\Elastica\ResultSet[] Result Sets
     */
    protected $_resultSets = [];

    /**
     * Current position.
     *
     * @var int Current position
     */
    protected $_position = 0;

    /**
     * Response.
     *
     * @var \Elastica\Response Response object
     */
    protected $_response;

    /**
     * Constructs ResultSet object.
     *
     * @param \Elastica\Response    $response
     * @param \Elastica\ResultSet[] $resultSets
     */
    public function __construct(Response $response, $resultSets)
    {
        $this->_response = $response;
        $this->_resultSets = $resultSets;
    }

    /**
     * @return array|\Elastica\ResultSet[]
     */
    public function getResultSets()
    {
        return $this->_resultSets;
    }

    /**
     * Returns response object.
     *
     * @return \Elastica\Response Response object
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * There is at least one result set with error.
     *
     * @return bool
     */
    public function hasError()
    {
        foreach ($this->getResultSets() as $resultSet) {
            if ($resultSet->getResponse()->hasError()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Elastica\ResultSet
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->_resultSets[$this->key()];
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->_position;
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->_position;
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return isset($this->_resultSets[$this->key()]);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->_resultSets);
    }

    /**
     * @param string|int $offset
     *
     * @return bool true on success or false on failure
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->_resultSets[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed can return all value types
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->_resultSets[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_resultSets[] = $value;
        } else {
            $this->_resultSets[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->_resultSets[$offset]);
    }
}
