<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Document\Subscriber\CopyLocaleSubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyLocaleEvent;
use Sulu\Component\Route\Document\Behavior\RoutableBehavior;

class CopyLocaleSubscriberTest extends SubscriberTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyPoolInterface>
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var CopyLocaleSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);

        $this->subscriber = new CopyLocaleSubscriber(
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->resourceLocatorStrategyPool->reveal()
        );
    }

    public function testHandleCopyLocale(): void
    {
        /** @var CopyLocaleEvent|ObjectProphecy $event */
        $event = $this->prophesize(CopyLocaleEvent::class);
        $event->getLocale()->willReturn('en');
        $event->getDestLocale()->willReturn('de');

        $structureData = [
            'foo' => 'bar',
        ];

        $extensionData = [
            'bar' => 'baz',
        ];

        /** @var StructureInterface|ObjectProphecy $structure */
        $structure = $this->prophesize(StructureInterface::class);
        $structure->toArray()->willReturn($structureData);

        /** @var PageDocument|ObjectProphecy $document */
        $document = $this->prophesize(PageDocument::class);
        $document->getUuid()->willReturn('page-uuid');
        $document->getWebspaceName()->willReturn('sulu_io');
        $document->getTitle()->willReturn('A page');
        $document->getStructureType()->willReturn('default');
        $document->getStructure()->willReturn($structure->reveal());
        $document->getExtensionsData()->willReturn($extensionData);

        $event->getDocument()->willReturn($document->reveal());

        /** @var PageDocument|ObjectProphecy $parentDocument */
        $parentDocument = $this->prophesize(PageDocument::class);
        $this->documentInspector->getParent($document->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-page-uuid');

        /** @var ResourceLocatorStrategyInterface|ObjectProphecy $resourceLocatorStrategy */
        $resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
        $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey('sulu_io')->willReturn($resourceLocatorStrategy->reveal());

        /** @var PageDocument|ObjectProphecy $destDocument */
        $destDocument = $this->prophesize(PageDocument::class);
        $this->documentManager->find('page-uuid', 'de')->willReturn($destDocument->reveal());

        /** @var StructureInterface|ObjectProphecy $destStructure */
        $destStructure = $this->prophesize(StructureInterface::class);

        $destDocument->setLocale('de')->shouldBeCalled();
        $destDocument->setTitle('A page')->shouldBeCalled();
        $destDocument->getTitle()->willReturn('A page');
        $destDocument->setStructureType('default')->shouldBeCalled();
        $destDocument->getStructure()->willReturn($destStructure->reveal());
        $destStructure->bind($structureData)->shouldBeCalled();
        $destDocument->setExtensionsData($extensionData)->shouldBeCalled();

        $resourceLocatorStrategy->generate(
            'A page',
            'parent-page-uuid',
            'sulu_io',
            'de'
        )->willReturn('/a-page');
        $destDocument->setResourceSegment('/a-page')->shouldBeCalled();

        $this->documentManager->persist($destDocument->reveal(), 'de', ['omit_modified_domain_event' => true])->shouldBeCalled();

        $event->setDestDocument($destDocument->reveal())->shouldBeCalled();

        $this->subscriber->handleCopyLocale($event->reveal());
    }

    public function testHandleCopyLocalePageTreeRoute(): void
    {
        /** @var CopyLocaleEvent|ObjectProphecy $event */
        $event = $this->prophesize(CopyLocaleEvent::class);
        $event->getLocale()->willReturn('en');
        $event->getDestLocale()->willReturn('de');

        $structureData = [
            'foo' => 'bar',
            'routePath' => [
                'page' => [
                    'uuid' => null,
                    'path' => '',
                ],
                'path' => '/overview/new',
                'suffix' => '/new',
            ],
        ];

        /** @var StructureInterface|ObjectProphecy $structure */
        $structure = $this->prophesize(StructureInterface::class);
        $structure->toArray()->willReturn($structureData);

        /** @var PageDocument|ObjectProphecy $document */
        $document = $this->prophesize(PageDocument::class);
        $document->getStructure()->willReturn($structure->reveal());

        /** @var RoutableBehavior|ObjectProphecy $destDocument */
        $destDocument = $this->prophesize(RoutableBehavior::class);
        $routeInterface = $this->prophesize(RouteInterface::class);
        $destDocument->setRoutePath('/new')->shouldBeCalled();
        $destDocument->getRoute()->willReturn($routeInterface->reveal());

        $structure = $this->subscriber->checkPageTreeRoute($destDocument->reveal(), $document->reveal(), 'de');
        $expectedStructure = [
            'foo' => 'bar',
            'routePath' => [
                'page' => [
                    'uuid' => null,
                    'path' => '',
                ],
                'path' => '/new',
                'suffix' => '/new',
            ],
        ];

        $this->assertSame($expectedStructure, $structure);
    }

    public function testHandleCopyLocalePageTreeRouteParentNotPublished(): void
    {
        /** @var CopyLocaleEvent|ObjectProphecy $event */
        $event = $this->prophesize(CopyLocaleEvent::class);
        $event->getLocale()->willReturn('en');
        $event->getDestLocale()->willReturn('de');

        $structureData = [
            'foo' => 'bar',
            'routePath' => [
                'page' => [
                    'uuid' => '2ad86c23-04b7-41e0-b2bf-086b7d43d4a2',
                    'path' => 'overview',
                ],
                'path' => '/overview/new',
                'suffix' => '/new',
            ],
        ];

        /** @var StructureInterface|ObjectProphecy $structure */
        $structure = $this->prophesize(StructureInterface::class);
        $structure->toArray()->willReturn($structureData);

        /** @var PageDocument|ObjectProphecy $document */
        $document = $this->prophesize(PageDocument::class);

        $document->getStructure()->willReturn($structure->reveal());
        $pageDocument = $this->prophesize(PageDocument::class);
        $pageDocument->getWorkflowStage()->willReturn(WorkflowStage::TEST);
        $this->documentManager->find('2ad86c23-04b7-41e0-b2bf-086b7d43d4a2', 'de')->willReturn($pageDocument->reveal());

        /** @var RoutableBehavior|ObjectProphecy $destDocument */
        $destDocument = $this->prophesize(RoutableBehavior::class);
        $routeInterface = $this->prophesize(RouteInterface::class);
        $destDocument->setRoutePath('/new')->shouldBeCalled();
        $destDocument->getRoute()->willReturn($routeInterface->reveal());

        $structure = $this->subscriber->checkPageTreeRoute($destDocument->reveal(), $document->reveal(), 'de');
        $expectedStructure = [
            'foo' => 'bar',
            'routePath' => [
                'page' => [
                    'uuid' => null,
                    'path' => '',
                ],
                'path' => '/new',
                'suffix' => '/new',
            ],
        ];

        $this->assertSame($expectedStructure, $structure);
    }

    public function testHandleCopyLocalePageTreeRouteParent(): void
    {
        /** @var CopyLocaleEvent|ObjectProphecy $event */
        $event = $this->prophesize(CopyLocaleEvent::class);
        $event->getLocale()->willReturn('en');
        $event->getDestLocale()->willReturn('de');

        $structureData = [
            'foo' => 'bar',
            'routePath' => [
                'page' => [
                    'uuid' => '2ad86c23-04b7-41e0-b2bf-086b7d43d4a2',
                    'path' => '/overview',
                ],
                'path' => '/overview/new',
                'suffix' => '/new',
            ],
        ];

        /** @var StructureInterface|ObjectProphecy $structure */
        $structure = $this->prophesize(StructureInterface::class);
        $structure->toArray()->willReturn($structureData);

        /** @var PageDocument|ObjectProphecy $document */
        $document = $this->prophesize(PageDocument::class);

        $document->getStructure()->willReturn($structure->reveal());
        $pageDocument = $this->prophesize(PageDocument::class);
        $pageDocument->getWorkflowStage()->willReturn(WorkflowStage::PUBLISHED);
        $propertyInterface = $this->prophesize(PropertyInterface::class);
        $propertyInterface->getValue()->willReturn('/overview');

        $structureInterface = $this->prophesize(StructureInterface::class);
        $structureInterface->getProperty('url')->willReturn($propertyInterface->reveal());

        $pageDocument->getStructure()->willReturn($structureInterface->reveal());
        $this->documentManager->find('2ad86c23-04b7-41e0-b2bf-086b7d43d4a2', 'de')->willReturn($pageDocument->reveal());

        /** @var RoutableBehavior|ObjectProphecy $destDocument */
        $destDocument = $this->prophesize(RoutableBehavior::class);
        $routeInterface = $this->prophesize(RouteInterface::class);
        $destDocument->setRoutePath('/overview/new')->shouldBeCalled();
        $destDocument->getRoute()->willReturn($routeInterface->reveal());

        $structure = $this->subscriber->checkPageTreeRoute($destDocument->reveal(), $document->reveal(), 'de');
        $expectedStructure = [
            'foo' => 'bar',
            'routePath' => [
                'page' => [
                    'uuid' => '2ad86c23-04b7-41e0-b2bf-086b7d43d4a2',
                    'path' => '/overview',
                ],
                'path' => '/overview/new',
                'suffix' => '/new',
            ],
        ];

        $this->assertSame($expectedStructure, $structure);
    }
}
