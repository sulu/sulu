<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Teaser;

use Massive\Bundle\SearchBundle\Search\Field;
use Massive\Bundle\SearchBundle\Search\QueryHit;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Massive\Bundle\SearchBundle\Search\SearchQueryBuilder;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Teaser\ContentTeaserProvider;
use Sulu\Bundle\ContentBundle\Teaser\Teaser;
use Sulu\Bundle\SearchBundle\Search\Document;

class ContentTeaserProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var SearchQueryBuilder
     */
    private $search;

    /**
     * @var ContentTeaserProvider
     */
    private $contentProvider;

    protected function setUp()
    {
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->search = $this->prophesize(SearchQueryBuilder::class);

        $this->searchManager->getIndexNames()->willReturn(['page_sulu_io_published']);

        $this->contentProvider = new ContentTeaserProvider($this->searchManager->reveal());
    }

    public function testFind()
    {
        $data = [
            '123-123-123' => [
                'title' => 'Test 1',
                'excerptTitle' => 'Excerpt 1',
                'excerptDescription' => 'This is a test',
                'excerptMore' => 'Read more ...',
                '__url' => '/test/1',
                'excerptImages' => json_encode(['ids' => [1, 2, 3]]),
                '_structure_type' => 'default',
                '_teaser_description' => '',
            ],
            '456-456-456' => [
                'title' => 'Test 2',
                'excerptTitle' => '',
                'excerptDescription' => '',
                'excerptMore' => '',
                '__url' => '/test/2',
                'excerptImages' => json_encode([]),
                '_structure_type' => 'overview',
                '_teaser_description' => '',
            ],
        ];
        $ids = array_keys($data);

        $this->searchManager->createSearch(
            Argument::that(
                function ($searchQuery) use ($ids) {
                    return 0 <= strpos($searchQuery, sprintf('__id:"%s"', $ids[0]))
                        && 0 <= strpos($searchQuery, sprintf('__id:"%s"', $ids[1]));
                }
            )
        )->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->indexes(['page_sulu_io_published'])->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->execute()->willReturn(
            [$this->createQueryHit($ids[0], $data[$ids[0]]), $this->createQueryHit($ids[1], $data[$ids[1]])]
        );

        $result = $this->contentProvider->find($ids, 'de');

        $this->assertCount(2, $result);

        $this->assertTeaser($ids[0], $data[$ids[0]], $result[0]);
        $this->assertTeaser($ids[1], $data[$ids[1]], $result[1]);
    }

    private function createQueryHit($id, array $data)
    {
        $queryHit = $this->prophesize(QueryHit::class);
        $document = $this->prophesize(Document::class);
        $queryHit->getDocument()->willReturn($document->reveal());
        $queryHit->getId()->willReturn($id);
        foreach ($data as $name => $value) {
            $document->getField($name)->willReturn(new Field($name, $value));
        }

        $document->hasField(Argument::any())->will(
            function ($arguments) use ($data) {
                return in_array($arguments[0], array_keys($data));
            }
        );

        return $queryHit->reveal();
    }

    private function assertTeaser($id, array $expected, Teaser $teaser)
    {
        $this->assertEquals($id, $teaser->getId());
        $this->assertEquals('content', $teaser->getType());

        $this->assertEquals(
            '' !== $expected['excerptTitle'] ? $expected['excerptTitle'] : $expected['title'],
            $teaser->getTitle()
        );
        $this->assertEquals($expected['excerptDescription'], $teaser->getDescription());
        $this->assertEquals($expected['excerptMore'], $teaser->getMoreText());
        $this->assertEquals($this->getMedia(json_decode($expected['excerptImages'], true)), $teaser->getMediaId());
        $this->assertEquals($expected['__url'], $teaser->getUrl());

        $this->assertEquals(['structureType' => $expected['_structure_type']], $teaser->getAttributes());
    }

    private function getMedia(array $data)
    {
        if (!array_key_exists('ids', $data)) {
            return;
        }

        return reset($data['ids']) ?: null;
    }
}
