<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\ResourceLocator\Strategy;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator\Mapper\ResourceLocatorMapperInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorGeneratorInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategy;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Util\SuluNodeHelper;

class ResourceLocatorStrategyTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ResourceLocatorMapperInterface>
     */
    private $mapper;

    /**
     * @var ObjectProphecy<PathCleanupInterface>
     */
    private $cleaner;

    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private $structureManager;

    /**
     * @var ObjectProphecy<ContentTypeManagerInterface>
     */
    private $contentTypeManager;

    /**
     * @var ObjectProphecy<SuluNodeHelper>
     */
    private $nodeHelper;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<ResourceLocatorGeneratorInterface>
     */
    private $resourceLocatorGenerator;

    /**
     * @var ResourceLocatorStrategy
     */
    private $resourceLocatorStrategy;

    public function setUp(): void
    {
        $this->mapper = $this->prophesize(ResourceLocatorMapperInterface::class);
        $this->cleaner = $this->prophesize(PathCleanupInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->nodeHelper = $this->prophesize(SuluNodeHelper::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->resourceLocatorGenerator = $this->prophesize(ResourceLocatorGeneratorInterface::class);

        $this->resourceLocatorStrategy = $this->getMockForAbstractClass(
            ResourceLocatorStrategy::class,
            [
                $this->mapper->reveal(),
                $this->cleaner->reveal(),
                $this->structureManager->reveal(),
                $this->contentTypeManager->reveal(),
                $this->nodeHelper->reveal(),
                $this->documentInspector->reveal(),
                $this->documentManager->reveal(),
                $this->resourceLocatorGenerator->reveal(),
            ]
        );
    }

    public function testGenerate(): void
    {
        $document = $this->prophesize(ParentBehavior::class);

        $this->documentManager->find('123-123-123', 'de', ['load_ghost_content' => false])
            ->willReturn($document->reveal());

        $this->documentInspector->getUuid($document->reveal())->willReturn('123-123-123');

        $this->mapper->loadByContentUuid('123-123-123', 'sulu_io', 'de', null)
            ->willThrow(ResourceLocatorNotFoundException::class);

        $this->resourceLocatorGenerator->generate('test', null)->willReturn('/test');
        $this->cleaner->cleanup('/test', 'de')->willReturn('/test');
        $this->mapper->getUniquePath('/test', 'sulu_io', 'de', null, null)->willReturn('/test');

        $this->assertEquals(
            $this->resourceLocatorStrategy->generate('test', '123-123-123', 'sulu_io', 'de'),
            '/test'
        );
    }

    public function testGenerateWithParentDocument(): void
    {
        $document = $this->prophesize(ParentBehavior::class);
        $parentDocument = $this->prophesize(ParentBehavior::class);
        $document->getParent()->willReturn($parentDocument);

        $this->documentManager->find('123-123-123', 'de', ['load_ghost_content' => false])
            ->willReturn($document->reveal());
        $this->documentManager->find('456-456-456', 'de', ['load_ghost_content' => false])
            ->willReturn($parentDocument->reveal());

        $this->documentInspector->getUuid($document->reveal())->willReturn('123-123-123');
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('456-456-456');

        $this->mapper->loadByContentUuid('123-123-123', 'sulu_io', 'de', null)
            ->willThrow(ResourceLocatorNotFoundException::class);
        $this->mapper->loadByContentUuid('456-456-456', 'sulu_io', 'de', null)
            ->willReturn('/parent');

        $this->resourceLocatorGenerator->generate('test', '/parent')->willReturn('/parent/test');
        $this->cleaner->cleanup('/parent/test', 'de')->willReturn('/parent/test');
        $this->mapper->getUniquePath('/parent/test', 'sulu_io', 'de', null, null)->willReturn('/parent/test');

        $this->assertEquals(
            $this->resourceLocatorStrategy->generate('test', '123-123-123', 'sulu_io', 'de'),
            '/parent/test'
        );
    }

    public function testGenerateWithParent(): void
    {
        $this->resourceLocatorGenerator->generate('test', null)->willReturn('/test');
        $this->cleaner->cleanup('/test', 'de')->willReturn('/test');
        $this->mapper->getUniquePath('/test', 'sulu_io', 'de', null, null)->willReturn('/test');

        $this->assertEquals(
            $this->resourceLocatorStrategy->generate('test', null, 'sulu_io', 'de'),
            '/test'
        );
    }
}
