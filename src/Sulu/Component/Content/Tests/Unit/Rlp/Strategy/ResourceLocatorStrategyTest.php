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
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
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

class ResourceLocatorStrategyTest extends SuluTestCase
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
        $this->cleaner = $this->getContainer()->get('sulu.content.path_cleaner');
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->nodeHelper = $this->prophesize(SuluNodeHelper::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->resourceLocatorGenerator = $this->getContainer()->get('sulu.content.resource_locator.strategy.tree_generator');

        $this->resourceLocatorStrategy = $this->getMockForAbstractClass(
            ResourceLocatorStrategy::class,
            [
                $this->mapper->reveal(),
                $this->cleaner,
                $this->structureManager->reveal(),
                $this->contentTypeManager->reveal(),
                $this->nodeHelper->reveal(),
                $this->documentInspector->reveal(),
                $this->documentManager->reveal(),
                $this->resourceLocatorGenerator,
            ]
        );
    }

    public function testGenerate()
    {
        $title = 'test';
        $uuid = '123-123-123';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $expectedPath = '/test';

        $document = $this->prophesize(ParentBehavior::class);

        $this->documentManager->find($uuid, $languageCode, ['load_ghost_content' => false])
            ->willReturn($document->reveal());
        $this->documentInspector->getUuid($document->reveal())
            ->willReturn($uuid);

        $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, null)
            ->willThrow(ResourceLocatorNotFoundException::class);
        $this->mapper->getUniquePath($expectedPath, $webspaceKey, $languageCode, null)
            ->willReturn($expectedPath);

        $result = $this->resourceLocatorStrategy->generate($title, $uuid, $webspaceKey, $languageCode);
        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateLatinExtended()
    {
        $title = 'tytuł testu w rozszerzonej łacinie';
        $uuid = '123-123-123';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $expectedPath = '/tytul-testu-w-rozszerzonej-lacinie';

        $document = $this->prophesize(ParentBehavior::class);

        $this->documentManager->find($uuid, $languageCode, ['load_ghost_content' => false])
            ->willReturn($document->reveal());
        $this->documentInspector->getUuid($document->reveal())
            ->willReturn($uuid);

        $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, null)
            ->willThrow(ResourceLocatorNotFoundException::class);
        $this->mapper->getUniquePath($expectedPath, $webspaceKey, $languageCode, null)
            ->willReturn($expectedPath);

        $result = $this->resourceLocatorStrategy->generate($title, $uuid, $webspaceKey, $languageCode);
        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateNonLatin()
    {
        $title = 'тестовий заголовок з і, ї, є, ґ';
        $uuid = '123-123-123';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $expectedPath = '/testovii-zagolovok-z-i-yi-ie-g';

        $document = $this->prophesize(ParentBehavior::class);

        $this->documentManager->find($uuid, $languageCode, ['load_ghost_content' => false])
            ->willReturn($document->reveal());
        $this->documentInspector->getUuid($document->reveal())
            ->willReturn($uuid);

        $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, null)
            ->willThrow(ResourceLocatorNotFoundException::class);
        $this->mapper->getUniquePath($expectedPath, $webspaceKey, $languageCode, null)
            ->willReturn($expectedPath);

        $result = $this->resourceLocatorStrategy->generate($title, $uuid, $webspaceKey, $languageCode);
        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateSpecialChars()
    {
        $title = '@#$%&*()';
        $uuid = '123-123-123';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';

        $expectedPath = '/cd5af187dd19cfc9256678a9824d5e9f';

        $document = $this->prophesize(ParentBehavior::class);

        $this->documentManager->find($uuid, $languageCode, ['load_ghost_content' => false])
            ->willReturn($document->reveal());
        $this->documentInspector->getUuid($document->reveal())
            ->willReturn($uuid);

        $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, null)
            ->willThrow(ResourceLocatorNotFoundException::class);
        $this->mapper->getUniquePath($expectedPath, $webspaceKey, $languageCode, null)
            ->willReturn($expectedPath);

        $result = $this->resourceLocatorStrategy->generate($title, $uuid, $webspaceKey, $languageCode);
        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateWithParentDocument()
    {
        $title = 'test';
        $uuid = '123-123-123';
        $parentUuid = '456-456-456';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $segmentKey = null;

        $expectedPath = '/parent/test';
        $expectedParentPath = '/parent';

        $document = $this->prophesize(ParentBehavior::class);
        $parentDocument = $this->prophesize(ParentBehavior::class);
        $document->getParent()->willReturn($parentDocument);

        $this->documentManager->find($uuid, $languageCode, ['load_ghost_content' => false])
            ->willReturn($document->reveal());
        $this->documentManager->find($parentUuid, $languageCode, ['load_ghost_content' => false])
            ->willReturn($parentDocument->reveal());
        $this->documentInspector->getUuid($document->reveal())
            ->willReturn($uuid);
        $this->documentInspector->getUuid($parentDocument->reveal())
            ->willReturn($parentUuid);

        $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey)
            ->willThrow(ResourceLocatorNotFoundException::class);
        $this->mapper->loadByContentUuid($parentUuid, $webspaceKey, $languageCode, $segmentKey)
            ->willReturn($expectedParentPath);
        $this->mapper->getUniquePath($expectedPath, $webspaceKey, $languageCode, $segmentKey)
            ->willReturn($expectedPath);

        $result = $this->resourceLocatorStrategy->generate($title, $uuid, $webspaceKey, $languageCode);
        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateWithParent()
    {
        $title = 'test';
        $webspaceKey = 'sulu_io';
        $languageCode = 'de';
        $segmentKey = null;
        $expectedPath = '/test';

        $this->mapper->getUniquePath($expectedPath, $webspaceKey, $languageCode, $segmentKey)
            ->willReturn('/test');

        $result = $this->resourceLocatorStrategy->generate($title, null, $webspaceKey, $languageCode, $segmentKey);
        $this->assertEquals($expectedPath, $result);
    }
}
