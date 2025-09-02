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
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Document\Subscriber\CopyLocaleSubscriber;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyLocaleEvent;

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
        /** @var ObjectProphecy<CopyLocaleEvent> $event */
        $event = $this->prophesize(CopyLocaleEvent::class);
        $event->getLocale()->willReturn('en');
        $event->getDestLocale()->willReturn('de');

        $structureData = [
            'foo' => 'bar',
        ];

        $extensionData = [
            'bar' => 'baz',
        ];

        /** @var ObjectProphecy<StructureInterface> $structure */
        $structure = $this->prophesize(StructureInterface::class);
        $structure->toArray()->willReturn($structureData);

        /** @var ObjectProphecy<PageDocument> $document */
        $document = $this->prophesize(PageDocument::class);
        $document->getUuid()->willReturn('page-uuid');
        $document->getWebspaceName()->willReturn('sulu_io');
        $document->getTitle()->willReturn('A page');
        $document->getStructureType()->willReturn('default');
        $document->getStructure()->willReturn($structure->reveal());
        $document->getExtensionsData()->willReturn($extensionData);

        $event->getDocument()->willReturn($document->reveal());

        /** @var ObjectProphecy<PageDocument> $parentDocument */
        $parentDocument = $this->prophesize(PageDocument::class);
        $this->documentInspector->getParent($document->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-page-uuid');

        /** @var ObjectProphecy<ResourceLocatorStrategyInterface> $resourceLocatorStrategy */
        $resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
        $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey('sulu_io')->willReturn($resourceLocatorStrategy->reveal());

        /** @var ObjectProphecy<PageDocument> $destDocument */
        $destDocument = $this->prophesize(PageDocument::class);
        $this->documentManager->find('page-uuid', 'de')->willReturn($destDocument->reveal());

        /** @var ObjectProphecy<StructureInterface> $destStructure */
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
}
