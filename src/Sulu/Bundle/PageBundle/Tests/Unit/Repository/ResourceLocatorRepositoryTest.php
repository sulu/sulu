<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Repository\ResourceLocatorRepository;
use Sulu\Bundle\PageBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Types\ResourceLocator\ResourceLocatorInformation;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ResourceLocatorRepositoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ResourceLocatorRepositoryInterface
     */
    private $repository;

    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private $structureManager;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyInterface>
     */
    private $resourceLocatorStrategy;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyPoolInterface>
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    protected function setUp(): void
    {
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);

        $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey(Argument::any())
             ->willReturn($this->resourceLocatorStrategy->reveal());

        $this->repository = new ResourceLocatorRepository(
            $this->resourceLocatorStrategyPool->reveal(),
            $this->structureManager->reveal()
        );
    }

    public function testGenerateWithParentUuid(): void
    {
        $parts = [
            'title' => 'news',
            'subtitle' => 'football',
        ];
        $parentUuid = '0123456789abcdef';
        $webspace = 'sulu_io';
        $locale = 'en';
        $template = 'default';

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getPropertiesByTagName('sulu.rlp.part')->willReturn([
            new Property('subtitle', 'subtitle', 'subtitle'),
            new Property('title', 'title', 'title'),
        ]);
        $this->structureManager->getStructure($template)->willReturn($structure->reveal());

        $resourcelocator = '/parent/news-football';
        $this->resourceLocatorStrategy->generate('news-football', $parentUuid, $webspace, $locale, null)
             ->willReturn($resourcelocator);

        $result = $this->repository->generate($parts, $parentUuid, $webspace, $locale, $template);
        $this->assertEquals($result['resourceLocator'], $resourcelocator);
    }

    public function testGenerate(): void
    {
        $parts = [
            'title' => 'news',
            'subtitle' => 'football',
        ];
        $webspace = 'sulu_io';
        $locale = 'en';
        $template = 'default';

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getPropertiesByTagName('sulu.rlp.part')->willReturn([
            new Property('subtitle', 'subtitle', 'subtitle'),
            new Property('title', 'title', 'title'),
        ]);
        $this->structureManager->getStructure($template)->willReturn($structure->reveal());

        $resourcelocator = '/news-football';
        $this->resourceLocatorStrategy->generate('news-football', null, $webspace, $locale, null)
             ->willReturn($resourcelocator);

        $result = $this->repository->generate($parts, null, $webspace, $locale, $template);
        $this->assertEquals($result['resourceLocator'], $resourcelocator);
    }

    public function testGetHistory(): void
    {
        $uuid = '0123456789abcdef';
        $webspace = 'sulu_io';
        $locale = 'en';

        $this->resourceLocatorStrategy->loadHistoryByContentUuid($uuid, $webspace, $locale)->willReturn([
            new ResourceLocatorInformation('/test1', null, 1),
            new ResourceLocatorInformation('/test2', null, 1),
            new ResourceLocatorInformation('/test3', null, 1),
        ]);

        $result = $this->repository->getHistory($uuid, $webspace, $locale);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, \count($result['_embedded']['page_resourcelocators']));
        $this->assertEquals('/test1', $result['_embedded']['page_resourcelocators'][0]['resourcelocator']);
        $this->assertEquals('/test3', $result['_embedded']['page_resourcelocators'][2]['resourcelocator']);
    }

    public function testDelete(): void
    {
        $resourcelocator = '/test';
        $webspace = 'sulu_io';
        $locale = 'en';

        $this->resourceLocatorStrategy->deleteById($resourcelocator, $locale, null)->shouldBeCalled();
        $this->repository->delete($resourcelocator, $webspace, $locale);
    }

    public function testDeleteWithSegment(): void
    {
        $resourcelocator = '/test';
        $webspace = 'sulu_io';
        $locale = 'en';
        $segment = 'live';

        $this->resourceLocatorStrategy->deleteById($resourcelocator, $locale, $segment)->shouldBeCalled();
        $this->repository->delete($resourcelocator, $webspace, $locale, $segment);
    }
}
