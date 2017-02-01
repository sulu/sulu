<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\ResourceLocator\Strategy;

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

class ResourceLocatorStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceLocatorMapperInterface
     */
    private $mapper;

    /**
     * @var PathCleanupInterface
     */
    private $cleaner;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ResourceLocatorGeneratorInterface
     */
    private $resourceLocatorGenerator;

    /**
     * @var ResourceLocatorStrategy
     */
    private $resourceLocatorStrategy;

    public function setUp()
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

    public function testGenerate()
    {
        $document = $this->prophesize(ParentBehavior::class);

        $this->documentManager->find('123-123-123', 'de', ['load_ghost_content' => false])
            ->willReturn($document->reveal());

        $this->documentInspector->getUuid($document->reveal())->willReturn('123-123-123');

        $this->mapper->loadByContentUuid('123-123-123', 'sulu_io', 'de', null)
            ->willThrow(ResourceLocatorNotFoundException::class);

        $this->resourceLocatorGenerator->generate('test', null)->willReturn('/test');
        $this->cleaner->cleanup('/test', 'de')->willReturn('/test');
        $this->mapper->getUniquePath('/test', 'sulu_io', 'de', null)->willReturn('/test');

        $this->assertEquals(
            $this->resourceLocatorStrategy->generate('test', '123-123-123', 'sulu_io', 'de'),
            '/test'
        );
    }

    public function testGenerateWithParentDocument()
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
        $this->mapper->getUniquePath('/parent/test', 'sulu_io', 'de', null)->willReturn('/parent/test');

        $this->assertEquals(
            $this->resourceLocatorStrategy->generate('test', '123-123-123', 'sulu_io', 'de'),
            '/parent/test'
        );
    }

    public function testGenerateWithParent()
    {
        $this->resourceLocatorGenerator->generate('test', null)->willReturn('/test');
        $this->cleaner->cleanup('/test', 'de')->willReturn('/test');
        $this->mapper->getUniquePath('/test', 'sulu_io', 'de', null)->willReturn('/test');

        $this->assertEquals(
            $this->resourceLocatorStrategy->generate('test', null, 'sulu_io', 'de'),
            '/test'
        );
    }
}
