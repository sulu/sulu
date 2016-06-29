<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Search;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Massive\Bundle\SearchBundle\Search\SearchQuery;
use Massive\Bundle\SearchBundle\Search\SearchQueryBuilder;
use Massive\Bundle\SearchBundle\Search\SearchResult;
use Prophecy\Argument;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Search\ListResponse;
use Sulu\Component\Rest\ListBuilder\Search\SearchListBuilder;

class SearchListBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var string
     */
    private $indexName = 'test';

    /**
     * @var string
     */
    private $locale = 'de';

    /**
     * @var SearchListBuilder
     */
    private $listBuilder;

    public function setUp()
    {
        parent::setUp();

        $this->searchManager = $this->prophesize(SearchManagerInterface::class);

        $this->searchManager->createSearch(Argument::any())->will(
            function ($arguments) {
                return new SearchQueryBuilder($this->reveal(), new SearchQuery($arguments[0]));
            }
        );

        $this->listBuilder = new SearchListBuilder($this->indexName, $this->locale, $this->searchManager->reveal());
    }

    public function testExecute()
    {
        $this->searchManager->search(
            Argument::that(
                function (SearchQuery $query) {
                    return $this->assertQuery($query);
                }
            )
        )->shouldBeCalled()->willReturn(new SearchResult([], 0));
        $this->searchManager->getIndexNames()->willReturn([$this->indexName]);

        $result = $this->listBuilder->execute();

        $this->assertInstanceOf(ListResponse::class, $result);
    }

    public function testExecuteSearch()
    {
        $this->searchManager->search(
            Argument::that(
                function (SearchQuery $query) {
                    return $this->assertQuery($query, '+(title:("test" OR test* OR test~))');
                }
            )
        )->shouldBeCalled()->willReturn(new SearchResult([], 0));
        $this->searchManager->getIndexNames()->willReturn([$this->indexName]);

        $this->listBuilder->search('test');
        $this->listBuilder->addSearchField(new FieldDescriptor('title'));

        $result = $this->listBuilder->execute();

        $this->assertInstanceOf(ListResponse::class, $result);
    }

    public function testExecuteSort()
    {
        $this->searchManager->search(
            Argument::that(
                function (SearchQuery $query) {
                    return $this->assertQuery($query, '+(title:("test" OR test* OR test~))', ['created' => 'asc']);
                }
            )
        )->shouldBeCalled()->willReturn(new SearchResult([], 0));
        $this->searchManager->getIndexNames()->willReturn([$this->indexName]);

        $this->listBuilder->search('test');
        $this->listBuilder->addSearchField(new FieldDescriptor('title'));
        $this->listBuilder->sort(new FieldDescriptor('created'), 'asc');

        $result = $this->listBuilder->execute();

        $this->assertInstanceOf(ListResponse::class, $result);
    }

    public function testExecuteOffsetLimit()
    {
        $this->searchManager->search(
            Argument::that(
                function (SearchQuery $query) {
                    return $this->assertQuery(
                        $query,
                        '+(title:("test" OR test* OR test~))',
                        ['created' => 'asc'],
                        20,
                        40
                    );
                }
            )
        )->shouldBeCalled()->willReturn(new SearchResult([], 0));
        $this->searchManager->getIndexNames()->willReturn([$this->indexName]);

        $this->listBuilder->search('test');
        $this->listBuilder->addSearchField(new FieldDescriptor('title'));
        $this->listBuilder->sort(new FieldDescriptor('created'), 'asc');
        $this->listBuilder->setCurrentPage(3);
        $this->listBuilder->limit(20);

        $result = $this->listBuilder->execute();

        $this->assertInstanceOf(ListResponse::class, $result);
    }

    public function testExecuteNoIndex()
    {
        $this->searchManager->search(Argument::any())->shouldNotBeCalled();
        $this->searchManager->getIndexNames()->willReturn([]);

        $result = $this->listBuilder->execute();

        $this->assertInstanceOf(ListResponse::class, $result);
    }

    /**
     * Assert query content.
     *
     * @param SearchQuery $query
     * @param string $queryString
     * @param array $sortings
     * @param null $limit
     * @param int $offset
     *
     * @return bool
     */
    public function assertQuery(SearchQuery $query, $queryString = '', $sortings = [], $limit = null, $offset = 0)
    {
        return $query->getIndexes() === [$this->indexName]
            && $query->getLocale() === $this->locale
            && $query->getQueryString() === $queryString
            && $query->getSortings() === $sortings
            && $query->getLimit() === $limit
            && $query->getOffset() === $offset;
    }
}
