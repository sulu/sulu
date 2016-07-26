<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Repository;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepository;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Types\Rlp\ResourceLocatorInformation;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * @group unit
 * @group repository
 */
class ResourceLocatorRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceLocatorRepositoryInterface
     */
    private $repository;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    protected function setUp()
    {
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->rlpStrategy = $this->prophesize(RlpStrategyInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);

        $this->repository = new ResourceLocatorRepository(
            $this->rlpStrategy->reveal(),
            $this->structureManager->reveal(),
            $this->documentManager->reveal()
        );
    }

    public function testGenerateWithParentUuid()
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

        $parent = $this->prophesize(BasePageDocument::class);
        $parent->getPublished()->willReturn(true);
        $this->documentManager->find($parentUuid, $locale, ['load_ghost_content' => false])->willReturn($parent->reveal());

        $parentPath = '/parent';
        $this->rlpStrategy->loadByContent($parent)->willReturn($parentPath);

        $title = 'news-football';
        $path = '/parent/news-football';
        $this->rlpStrategy->generate($title, $parentPath, $webspace, $locale, null)->willReturn($path);

        $result = $this->repository->generate($parts, $parentUuid, null, $webspace, $locale, $template);
        $this->assertEquals($result['resourceLocator'], $path);
    }

    public function testGenerateWithUnpublishedParent()
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

        $grandParent = $this->prophesize(BasePageDocument::class);
        $grandParent->getPublished()->willReturn(true);

        $parent = $this->prophesize(PageDocument::class);
        $parent->getPublished()->willReturn(false);
        $parent->getParent()->willReturn($grandParent->reveal());

        $this->documentManager->find($parentUuid, $locale, ['load_ghost_content' => false])->willReturn($parent->reveal());

        $grandParentPath = '/grandparent';
        $this->rlpStrategy->loadByContent($grandParent)->willReturn($grandParentPath);

        $title = 'news-football';
        $path = '/grandparent/news-football';
        $this->rlpStrategy->generate($title, $grandParentPath, $webspace, $locale, null)->willReturn($path);

        $result = $this->repository->generate($parts, $parentUuid, null, $webspace, $locale, $template);
        $this->assertEquals($result['resourceLocator'], $path);
    }

    public function testGenerateWithUuid()
    {
        $parts = [
            'title' => 'news',
            'subtitle' => 'football',
        ];
        $uuid = '0123456789abcdef';
        $webspace = 'sulu_io';
        $locale = 'en';
        $template = 'default';

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getPropertiesByTagName('sulu.rlp.part')->willReturn([
            new Property('subtitle', 'subtitle', 'subtitle'),
            new Property('title', 'title', 'title'),
        ]);
        $this->structureManager->getStructure($template)->willReturn($structure->reveal());

        $title = 'news-football';
        $path = '/ancestor/news-football';
        $this->rlpStrategy->generateForUuid($title, $uuid, $webspace, $locale, null)->willReturn($path);

        $result = $this->repository->generate($parts, null, $uuid, $webspace, $locale, $template);
        $this->assertEquals($result['resourceLocator'], $path);
    }

    public function testGenerate()
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

        $title = 'news-football';
        $path = '/news-football';
        $this->rlpStrategy->generate($title, '/', $webspace, $locale, null)->willReturn($path);

        $result = $this->repository->generate($parts, null, null, $webspace, $locale, $template);
        $this->assertEquals($result['resourceLocator'], $path);
    }

    public function testGetHistory()
    {
        $uuid = '0123456789abcdef';
        $webspace = 'sulu_io';
        $locale = 'en';

        $this->rlpStrategy->loadHistoryByContentUuid($uuid, $webspace, $locale)->willReturn([
            new ResourceLocatorInformation('/test1', null, 1),
            new ResourceLocatorInformation('/test2', null, 1),
            new ResourceLocatorInformation('/test3', null, 1),
        ]);

        $result = $this->repository->getHistory($uuid, $webspace, $locale);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, count($result['_embedded']['resourcelocators']));
        $this->assertEquals('/test1', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/test3', $result['_embedded']['resourcelocators'][2]['resourceLocator']);
    }

    public function testDelete()
    {
        $path = '/test';
        $webspace = 'sulu_io';
        $locale = 'en';

        $this->rlpStrategy->deleteByPath($path, $webspace, $locale, null)->shouldBeCalled();
        $this->repository->delete($path, $webspace, $locale);
    }

    public function testDeleteWithSegment()
    {
        $path = '/test';
        $webspace = 'sulu_io';
        $locale = 'en';
        $segment = 'live';

        $this->rlpStrategy->deleteByPath($path, $webspace, $locale, $segment)->shouldBeCalled();
        $this->repository->delete($path, $webspace, $locale, $segment);
    }
}
