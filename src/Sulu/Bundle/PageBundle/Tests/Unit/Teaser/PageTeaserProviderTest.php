<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Teaser;

use Massive\Bundle\SearchBundle\Search\Field;
use Massive\Bundle\SearchBundle\Search\QueryHit;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Massive\Bundle\SearchBundle\Search\SearchQueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\PageTeaserProvider;
use Sulu\Bundle\PageBundle\Teaser\PHPCRPageTeaserProvider;
use Sulu\Bundle\PageBundle\Teaser\Teaser;
use Sulu\Bundle\SearchBundle\Search\Document;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageTeaserProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SearchManagerInterface>
     */
    private $searchManager;

    /**
     * @var ObjectProphecy<SearchQueryBuilder>
     */
    private $search;

    /**
     * @var PageTeaserProvider
     */
    private $pageTeaserProvider;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    protected function setUp(): void
    {
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->search = $this->prophesize(SearchQueryBuilder::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->translator->trans(Argument::cetera())->willReturnArgument(0);

        $this->searchManager->getIndexNames()->willReturn([
            'page_sulu_io',
            'page_sulu_io_published',
            'page_example',
            'page_example_published',
        ]);

        $this->pageTeaserProvider = new PageTeaserProvider(
            $this->searchManager->reveal(),
            $this->translator->reveal(),
            false
        );
    }

    public function testConfiguration(): void
    {
        $configuration = $this->pageTeaserProvider->getConfiguration();

        $viewProperty = new \ReflectionProperty(TeaserConfiguration::class, 'view');
        $viewProperty->setAccessible(true);

        $resultToViewProperty = new \ReflectionProperty(TeaserConfiguration::class, 'resultToView');
        $resultToViewProperty->setAccessible(true);

        $this->assertEquals('sulu_page.page_edit_form', $viewProperty->getValue($configuration));
        $this->assertEquals(
            ['id' => 'id', 'attributes/webspaceKey' => 'webspace'],
            $resultToViewProperty->getValue($configuration)
        );
    }

    public function testFind(): void
    {
        $data = [
            '123-123-123' => [
                'title' => 'Test 1',
                'excerptTitle' => 'Excerpt 1',
                'excerptDescription' => 'This is a test',
                'excerptMore' => 'Read more ...',
                '__url' => '/test/1',
                'excerptImages' => \json_encode(['ids' => [1, 2, 3]]),
                '_structure_type' => 'default',
                '_teaser_description' => '',
                'webspace_key' => 'sulu_test',
            ],
            '456-456-456' => [
                'title' => 'Test 2',
                'excerptTitle' => '',
                'excerptDescription' => '',
                'excerptMore' => '',
                '__url' => '/test/2',
                'excerptImages' => \json_encode([]),
                '_structure_type' => 'overview',
                '_teaser_description' => '',
                'webspace_key' => 'sulu_blog',
            ],
        ];
        $ids = \array_keys($data);

        $this->searchManager->createSearch(
            Argument::that(
                function($searchQuery) use ($ids) {
                    return 0 <= \strpos($searchQuery, \sprintf('__id:"%s"', $ids[0]))
                        && 0 <= \strpos($searchQuery, \sprintf('__id:"%s"', $ids[1]));
                }
            )
        )->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->indexes(['page_sulu_io_published', 'page_example_published'])
            ->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->locale('de')->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->setLimit(2)->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->execute()->willReturn(
            [$this->createQueryHit($ids[0], $data[$ids[0]]), $this->createQueryHit($ids[1], $data[$ids[1]])]
        );

        $result = $this->pageTeaserProvider->find($ids, 'de');

        $this->assertCount(2, $result);

        $this->assertTeaser($ids[0], $data[$ids[0]], $result[0]);
        $this->assertTeaser($ids[1], $data[$ids[1]], $result[1]);
    }

    public function testFindShowDrafts(): void
    {
        $pageTeaserProvider = new PageTeaserProvider(
            $this->searchManager->reveal(),
            $this->translator->reveal(),
            true
        );

        $data = [
            '123-123-123' => [
                'title' => 'Test 1',
                'excerptTitle' => 'Excerpt 1',
                'excerptDescription' => 'This is a test',
                'excerptMore' => 'Read more ...',
                '__url' => '/test/1',
                'excerptImages' => \json_encode(['ids' => [1, 2, 3]]),
                '_structure_type' => 'default',
                '_teaser_description' => '',
                'webspace_key' => 'sulu_test',
            ],
        ];
        $ids = \array_keys($data);

        $this->searchManager->createSearch(
            Argument::that(
                function($searchQuery) use ($ids) {
                    return 0 <= \strpos($searchQuery, \sprintf('__id:"%s"', $ids[0]));
                }
            )
        )->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->indexes(['page_sulu_io', 'page_example'])->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->locale('de')->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->setLimit(1)->willReturn($this->search->reveal())->shouldBeCalled();
        $this->search->execute()->willReturn(
            [$this->createQueryHit($ids[0], $data[$ids[0]])]
        );

        $result = $pageTeaserProvider->find($ids, 'de');

        $this->assertCount(1, $result);

        $this->assertTeaser($ids[0], $data[$ids[0]], $result[0]);
    }

    public function testGetConfigurationFromPHPCRPageTeaserProvider(): void
    {
        $configuration = $this->prophesize(TeaserConfiguration::class);

        $phpcrPageTeaserProvider = $this->prophesize(PHPCRPageTeaserProvider::class);
        $phpcrPageTeaserProvider->getConfiguration()->willReturn($configuration->reveal());

        $pageTeaserProvider = new PageTeaserProvider(
            $this->searchManager->reveal(),
            $this->translator->reveal(),
            false,
            $phpcrPageTeaserProvider->reveal()
        );

        $this->assertSame($configuration->reveal(), $pageTeaserProvider->getConfiguration());
    }

    public function testFindFromPHPCRPageTeaserProvider(): void
    {
        $teaser = $this->prophesize(Teaser::class);

        $phpcrPageTeaserProvider = $this->prophesize(PHPCRPageTeaserProvider::class);
        $phpcrPageTeaserProvider->find(['abc'], 'en')->willReturn([$teaser->reveal()]);

        $pageTeaserProvider = new PageTeaserProvider(
            $this->searchManager->reveal(),
            $this->translator->reveal(),
            false,
            $phpcrPageTeaserProvider->reveal()
        );

        $this->assertSame($teaser->reveal(), $pageTeaserProvider->find(['abc'], 'en')[0]);
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
            function($arguments) use ($data) {
                return \in_array($arguments[0], \array_keys($data));
            }
        );

        return $queryHit->reveal();
    }

    private function assertTeaser($id, array $expected, Teaser $teaser): void
    {
        $this->assertEquals($id, $teaser->getId());
        $this->assertEquals('pages', $teaser->getType());

        $this->assertEquals(
            '' !== $expected['excerptTitle'] ? $expected['excerptTitle'] : $expected['title'],
            $teaser->getTitle()
        );
        $this->assertEquals($expected['excerptDescription'], $teaser->getDescription());
        $this->assertEquals($expected['excerptMore'], $teaser->getMoreText());
        $this->assertEquals($this->getMedia(\json_decode($expected['excerptImages'], true)), $teaser->getMediaId());
        $this->assertEquals($expected['__url'], $teaser->getUrl());

        $this->assertEquals(
            ['structureType' => $expected['_structure_type'], 'webspaceKey' => $expected['webspace_key']],
            $teaser->getAttributes()
        );
    }

    private function getMedia(array $data)
    {
        if (!\array_key_exists('ids', $data)) {
            return;
        }

        return \reset($data['ids']) ?: null;
    }
}
