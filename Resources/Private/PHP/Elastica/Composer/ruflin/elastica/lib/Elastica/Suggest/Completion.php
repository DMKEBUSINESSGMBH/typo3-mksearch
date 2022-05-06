<?php

namespace Elastica\Suggest;

/**
 * Completion suggester.
 *
 * @author Igor Denisenko <im.denisenko@yahoo.com>
 *
 * @see   https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters-completion.html
 */
class Completion extends AbstractSuggest
{
    /**
     * Set fuzzy parameter.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters-completion.html#fuzzy
     *
     * @param array $fuzzy
     *
     * @return $this
     */
    public function setFuzzy(array $fuzzy)
    {
        return $this->setParam('fuzzy', $fuzzy);
    }
}
