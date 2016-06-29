<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Search;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Massive\Bundle\SearchBundle\Search\SearchResult;
use Sulu\Component\Rest\ListBuilder\AbstractListBuilder;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Search\FieldDescriptor\SearchFieldDescriptor;
use Sulu\Exception\FeatureNotImplementedException;

/**
 * The list-builder implementation for massive-search.
 */
class SearchListBuilder extends AbstractListBuilder
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var string
     */
    private $indexName;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var SearchResult
     */
    private $result;

    /**
     * @var SearchFieldDescriptor[]
     */
    protected $selectFields = [];

    /**
     * @var SearchFieldDescriptor[]
     */
    protected $searchFields = [];

    /**
     * @var SearchFieldDescriptor[]
     */
    protected $sortFields = [];

    /**
     * @param string $indexName
     * @param string $locale
     * @param SearchManagerInterface $searchManager
     */
    public function __construct($indexName, $locale, SearchManagerInterface $searchManager)
    {
        $this->indexName = $indexName;
        $this->locale = $locale;
        $this->searchManager = $searchManager;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->getResult()->getTotal();
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return new ListResponse($this->getResult(), $this->selectFields);
    }

    /**
     * Execute search and returns result.
     *
     * The result will be cached to avoid multiple executions.
     *
     * @return SearchResult
     */
    private function getResult()
    {
        if ($this->result !== null) {
            return $this->result;
        }

        if (!in_array($this->indexName, $this->searchManager->getIndexNames(), true)) {
            return new SearchResult([], 0);
        }

        $searchString = [];
        foreach ($this->searchFields as $field) {
            $searchString[] = $this->createSearch($field->getFieldName(), $this->search);
        }

        $query = $this->searchManager->createSearch(implode(' ', $searchString))
            ->index($this->indexName)
            ->locale($this->locale)
            ->setLimit($this->limit)
            ->setOffset($this->limit * ($this->page - 1));

        for ($i = 0, $length = count($this->sortFields); $i < $length; ++$i) {
            $query->addSorting($this->sortFields[$i]->getFieldName(), $this->sortOrders[$i]);
        }

        return $this->result = $query->execute();
    }

    /**
     * Wraps query with fuzzy logic.
     *
     * @param string $name
     * @param string $query
     *
     * @return string
     */
    private function createSearch($name, $query)
    {
        $queryString = '';
        if (strlen($query) < 3) {
            $queryString .= sprintf('+(%s:"%s") ', $name, $this->escapeDoubleQuotes($query));
        } else {
            $queryValues = explode(' ', $query);
            foreach ($queryValues as $queryValue) {
                if (strlen($queryValue) > 2) {
                    $queryString .= sprintf(
                        '+(%s:("%s" OR %s* OR %s~))',
                        $name,
                        $this->escapeDoubleQuotes($queryValue),
                        preg_replace('/([^\pL\s\d])/u', '?', $queryValue),
                        preg_replace('/([^\pL\s\d])/u', '', $queryValue)
                    );
                } else {
                    $queryString .= sprintf('+(%s:"%s") ', $name, $this->escapeDoubleQuotes($queryValue));
                }
            }
        }

        return $queryString;
    }

    /**
     * Escapes given query.
     *
     * @param string $query
     *
     * @return string
     */
    private function escapeDoubleQuotes($query)
    {
        return str_replace('"', '\\"', $query);
    }

    /**
     * {@inheritdoc}
     */
    public function createBetweenExpression(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function createInExpression(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function createWhereExpression(FieldDescriptorInterface $fieldDescriptor, $value, $comparator)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function createAndExpression(array $expressions)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function createOrExpression(array $expressions)
    {
        throw new FeatureNotImplementedException();
    }
}
