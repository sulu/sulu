<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Subscriber\ResourceSegmentSubscriber;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class ResourceSegmentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var ResourceLocatorStrategyInterface
     */
    private $resourceLocatorStrategy;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var ResourceSegmentBehavior
     */
    private $document;

    /**
     * @var StructureMetadata
     */
    private $structureMetadata;

    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var ResourceSegmentSubscriber
     */
    private $resourceSegmentSubscriber;

    /**
     * @var PropertyMetadata
     */
    private $propertyMetaData;

    public function setUp()
    {
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
        $this->document = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);

        $this->structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->propertyMetaData = $this->prophesize(PropertyMetadata::class);
        $this->propertyMetaData->getName()->willReturn('url');
        $this->structureMetadata->getPropertyByTagName('sulu.rlp')->willReturn($this->propertyMetaData->reveal());
        $this->encoder->localizedSystemName('url', 'de')->willReturn('i18n:de-url');
        $this->encoder->localizedSystemName('url', 'en')->willReturn('i18n:en-url');

        $this->documentInspector->getStructureMetadata($this->document->reveal())
            ->willReturn($this->structureMetadata->reveal());

        $this->defaultSession = $this->prophesize(SessionInterface::class);
        $this->liveSession = $this->prophesize(SessionInterface::class);

        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);
        $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey(Argument::any())->willReturn($this->resourceLocatorStrategy->reveal());

        $this->resourceSegmentSubscriber = new ResourceSegmentSubscriber(
            $this->encoder->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->resourceLocatorStrategyPool->reveal(),
            $this->defaultSession->reveal(),
            $this->liveSession->reveal()
        );
    }

    public function testHydrate()
    {
        $segment = '/test';

        $event = $this->prophesize(AbstractMappingEvent::class);

        $event->getDocument()->willReturn($this->document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $event->getNode()->willReturn($node->reveal());

        $this->documentInspector->getOriginalLocale($this->document->reveal())->willReturn('de');

        $node->getPropertyValueWithDefault('i18n:de-url', '')->willReturn($segment);

        // Asserts
        $this->document->setResourceSegment($segment)->shouldBeCalled();

        $this->resourceSegmentSubscriber->handleHydrate($event->reveal());
    }

    public function testPersistDocument()
    {
        $segment = '/test';

        $event = $this->prophesize(PersistEvent::class);

        $event->getDocument()->willReturn($this->document->reveal());

        $localizedProperty = $this->prophesize(PropertyInterface::class);
        $localizedProperty->getName()->willReturn('i18n:de-url');

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getProperty('url')->willReturn($localizedProperty->reveal());
        $this->document->getStructure()->willReturn($structure->reveal());

        $this->document->getResourceSegment()->willReturn($segment);

        // Asserts
        $localizedProperty->setValue($segment)->shouldBeCalled();

        $this->resourceSegmentSubscriber->handlePersistDocument($event->reveal());
    }

    public function testPersistRouteWithoutLocale()
    {
        $event = $this->prophesize(PublishEvent::class);
        $document = $this->prophesize(ResourceSegmentBehavior::class);
        $document->willImplement(StructureBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn(null)->shouldBeCalled();
        $this->resourceLocatorStrategy->save($document->reveal(), Argument::any())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }

    public function testPersistRoute()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getLocale()->willReturn('de');

        $this->documentInspector->getWebspace($this->document->reveal())->willReturn('sulu_io');

        $this->document->getRedirectType()->willReturn(RedirectType::NONE);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->resourceLocatorStrategy->save($this->document->reveal(), null)->shouldBeCalled();
        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }

    public function testPersistRouteForRedirect()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getLocale()->willReturn('de');

        $this->document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->resourceLocatorStrategy->save(Argument::any())->shouldNotBeCalled();
        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }

    public function testUpdateMovedDocument()
    {
        $parentDocument = $this->prophesize(ResourceSegmentBehavior::class);

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de', 'en']);
        $this->documentInspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getParent($this->document->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getPath($this->document->reveal())->willReturn('/cmf/sulu_io/contents/german/child');
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-uuid');

        $defaultNode = $this->prophesize(NodeInterface::class);
        $defaultNode->getPropertyValue('i18n:de-url')->willReturn('/german/parent/child');
        $defaultNode->getPropertyValue('i18n:en-url')->willReturn('/english/parent/child');
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->hasProperty('i18n:de-url')->willReturn(true);
        $liveNode->hasProperty('i18n:en-url')->willReturn(true);
        $liveNode->getPropertyValue('i18n:de-url')->willReturn('/german/child');
        $liveNode->getPropertyValue('i18n:en-url')->willReturn('/english/child');

        $this->defaultSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($defaultNode);
        $this->liveSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($liveNode);

        $germanDocument = $this->getDocumentMock();
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'de')->willReturn($germanDocument);
        $this->resourceLocatorStrategy->getChildPart('/german/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->getChildPart('/german/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $this->resourceLocatorStrategy->getInputType()->willReturn(ResourceLocatorStrategyInterface::INPUT_TYPE_LEAF);
        $defaultNode->setProperty('i18n:de-url', '/german/parent/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:de-url', '/german/parent/child')->shouldBeCalled();
        $germanDocument->setResourceSegment('/german/parent/child')->shouldBeCalled();
        $this->resourceLocatorStrategy->save($germanDocument, null)->shouldBeCalled();
        $germanDocument->setResourceSegment('/german/child')->shouldBeCalled();

        $englishDocument = $this->getDocumentMock();
        $englishDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'en')->willReturn($englishDocument);
        $this->resourceLocatorStrategy->getChildPart('/english/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->getChildPart('/english/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'en')->willReturn('/english/parent/child');
        $defaultNode->setProperty('i18n:en-url', '/english/parent/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:en-url', '/english/parent/child')->shouldBeCalled();
        $englishDocument->setResourceSegment('/english/parent/child')->shouldBeCalled();
        $this->resourceLocatorStrategy->save($englishDocument, null)->shouldBeCalled();
        $englishDocument->setResourceSegment('/english/child')->shouldBeCalled();

        $this->resourceSegmentSubscriber->updateMovedDocument($event->reveal());
    }

    public function testUpdateMovedDocumentWithRedirects()
    {
        $parentDocument = $this->prophesize(ResourceSegmentBehavior::class);

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de', 'en', 'fr']);
        $this->documentInspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getParent($this->document->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getPath($this->document->reveal())->willReturn('/cmf/sulu_io/contents/german/child');
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-uuid');

        $defaultNode = $this->prophesize(NodeInterface::class);
        $defaultNode->getPropertyValue('i18n:de-url')->willReturn('/german/parent/child');
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->hasProperty('i18n:de-url')->willReturn(true);
        $liveNode->getPropertyValue('i18n:de-url')->willReturn('/german/child');

        $this->defaultSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($defaultNode);
        $this->liveSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($liveNode);

        $germanDocument = $this->getDocumentMock();
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'de')->willReturn($germanDocument);
        $germanDocument->getResourceSegment()->willReturn('/german/child');
        $this->resourceLocatorStrategy->getChildPart('/german/child')->willReturn('child');
        $this->resourceLocatorStrategy->getChildPart('/german/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $this->resourceLocatorStrategy->getInputType()->willReturn(ResourceLocatorStrategyInterface::INPUT_TYPE_LEAF);
        $defaultNode->setProperty('i18n:de-url', '/german/parent/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:de-url', '/german/parent/child')->shouldBeCalled();
        $germanDocument->setResourceSegment('/german/parent/child')->shouldBeCalled();
        $this->resourceLocatorStrategy->save($germanDocument, null)->shouldBeCalled();
        $germanDocument->setResourceSegment('/german/child')->shouldBeCalled();

        $englishDocument = $this->getDocumentMock();
        $englishDocument->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->documentManager->find('uuid', 'en')->willReturn($englishDocument);
        $this->resourceLocatorStrategy->save($englishDocument, null)->shouldNotBeCalled();

        $frenchDocument = $this->getDocumentMock();
        $frenchDocument->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->documentManager->find('uuid', 'fr')->willReturn($frenchDocument);
        $this->resourceLocatorStrategy->save($frenchDocument, null)->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->updateMovedDocument($event->reveal());
    }

    public function testUpdateMovedDocumentForWrongDocument()
    {
        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn(new \stdClass());

        $this->documentInspector->getLocales(Argument::cetera())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->updateMovedDocument($event->reveal());
    }

    public function testUpdateMovedDocumentWithGhostParent()
    {
        $parentDocument = $this->prophesize(ResourceSegmentBehavior::class);

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $defaultNode = $this->prophesize(NodeInterface::class);
        $defaultNode->getPropertyValue('i18n:de-url')->willReturn('/german/parent/child');
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->hasProperty('i18n:de-url')->willReturn(true);
        $liveNode->getPropertyValue('i18n:de-url')->willReturn('/german/child');

        $this->defaultSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($defaultNode);
        $this->liveSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($liveNode);

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de']);
        $this->documentInspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getParent($this->document->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getPath($this->document->reveal())->willReturn('/cmf/sulu_io/contents/german/child');
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-uuid');

        $germanDocument = $this->getDocumentMock();
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'de')->willReturn($germanDocument);
        $germanDocument->getResourceSegment()->willReturn('/german/child');
        $this->resourceLocatorStrategy->getChildPart('/german/child')->willReturn('child');
        $this->resourceLocatorStrategy->getChildPart('/german/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'de')->willReturn('/child');
        $this->resourceLocatorStrategy->getInputType()->willReturn(ResourceLocatorStrategyInterface::INPUT_TYPE_LEAF);
        $defaultNode->setProperty('i18n:de-url', '/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:de-url', '/child')->shouldBeCalled();
        $germanDocument->setResourceSegment(Argument::any())->shouldBeCalled();
        $this->resourceLocatorStrategy->save($germanDocument, null)->shouldBeCalled();
        $germanDocument->setResourceSegment(Argument::any())->shouldBeCalled();

        $this->resourceSegmentSubscriber->updateMovedDocument($event->reveal());
    }

    public function testUpdateMovedDocumentOnlyDraft()
    {
        $parentDocument = $this->prophesize(ResourceSegmentBehavior::class);

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de']);
        $this->documentInspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getParent($this->document->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getPath($this->document->reveal())->willReturn('/cmf/sulu_io/contents/german/child');
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-uuid');

        $defaultNode = $this->prophesize(NodeInterface::class);
        $defaultNode->getPropertyValue('i18n:de-url')->willReturn('/german/parent/child');
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->hasProperty('i18n:de-url')->willReturn(false);

        $this->defaultSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($defaultNode);
        $this->liveSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($liveNode);

        $germanDocument = $this->getDocumentMock();
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'de')->willReturn($germanDocument);
        $this->resourceLocatorStrategy->getChildPart('/german/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $this->resourceLocatorStrategy->getInputType()->willReturn(ResourceLocatorStrategyInterface::INPUT_TYPE_LEAF);
        $defaultNode->setProperty('i18n:de-url', '/german/parent/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:de-url', Argument::any())->shouldNotBeCalled();
        $this->resourceLocatorStrategy->save($germanDocument, null)->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->updateMovedDocument($event->reveal());
    }

    public function testUpdateCopiedDocument()
    {
        $parentDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $copiedDocument = $this->prophesize(ResourceSegmentBehavior::class);

        $event = $this->prophesize(CopyEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getCopiedPath()->willReturn('/cmf/sulu_io/contents/page/parent/child');
        $this->documentInspector->getLocale($this->document->reveal())->willReturn('de');
        $this->documentManager->find('/cmf/sulu_io/contents/page/parent/child', 'de')
            ->willReturn($copiedDocument->reveal());
        $this->documentInspector->getUuid($copiedDocument->reveal())->willReturn('copy-uuid');

        $this->documentInspector->getLocales($copiedDocument->reveal())->willReturn(['de', 'en']);
        $this->documentInspector->getWebspace($copiedDocument->reveal())->willReturn('sulu_io');
        $this->documentInspector->getParent($copiedDocument->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getPath($copiedDocument->reveal())->willReturn('/cmf/sulu_io/contents/german/child');
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-uuid');

        $defaultNode = $this->prophesize(NodeInterface::class);
        $defaultNode->getPropertyValue('i18n:de-url')->willReturn('/german/parent/child');
        $defaultNode->getPropertyValue('i18n:en-url')->willReturn('/english/parent/child');
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->hasProperty('i18n:de-url')->willReturn(false);
        $liveNode->hasProperty('i18n:en-url')->willReturn(false);

        $this->defaultSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($defaultNode);
        $this->liveSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($liveNode);

        $germanDocument = $this->getDocumentMock();
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('copy-uuid', 'de')->willReturn($germanDocument);
        $germanDocument->getResourceSegment()->willReturn('/german/child');
        $this->resourceLocatorStrategy->getChildPart('/german/child')->willReturn('child');
        $this->resourceLocatorStrategy->getChildPart('/german/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $defaultNode->setProperty('i18n:de-url', '/german/parent/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:de-url', Argument::any())->shouldNotBeCalled();

        $englishDocument = $this->getDocumentMock();
        $englishDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('copy-uuid', 'en')->willReturn($englishDocument);
        $englishDocument->getResourceSegment()->willReturn('/english/child');
        $this->resourceLocatorStrategy->getChildPart('/english/child')->willReturn('child');
        $this->resourceLocatorStrategy->getChildPart('/english/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'en')->willReturn('/english/parent/child');
        $defaultNode->setProperty('i18n:en-url', '/english/parent/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:en-url', Argument::any())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->updateCopiedDocument($event->reveal());
    }

    public function testUpdateCopiedDocumentWithRedirects()
    {
        $parentDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $copiedDocument = $this->prophesize(ResourceSegmentBehavior::class);

        $event = $this->prophesize(CopyEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getCopiedPath()->willReturn('/cmf/sulu_io/contents/page/parent/child');
        $this->documentInspector->getLocale($this->document->reveal())->willReturn('de');
        $this->documentManager->find('/cmf/sulu_io/contents/page/parent/child', 'de')
            ->willReturn($copiedDocument->reveal());
        $this->documentInspector->getUuid($copiedDocument->reveal())->willReturn('copy-uuid');

        $this->documentInspector->getLocales($copiedDocument->reveal())->willReturn(['de', 'en', 'fr']);
        $this->documentInspector->getWebspace($copiedDocument->reveal())->willReturn('sulu_io');
        $this->documentInspector->getParent($copiedDocument->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getPath($copiedDocument->reveal())->willReturn('/cmf/sulu_io/contents/german/child');
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-uuid');

        $defaultNode = $this->prophesize(NodeInterface::class);
        $defaultNode->getPropertyValue('i18n:de-url')->willReturn('/german/parent/child');
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->hasProperty('i18n:de-url')->willReturn(false);

        $this->defaultSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($defaultNode);
        $this->liveSession->getNode('/cmf/sulu_io/contents/german/child')->willReturn($liveNode);

        $germanDocument = $this->getDocumentMock();
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('copy-uuid', 'de')->willReturn($germanDocument);
        $germanDocument->getResourceSegment()->willReturn('/german/child');
        $this->resourceLocatorStrategy->getChildPart('/german/child')->willReturn('child');
        $this->resourceLocatorStrategy->getChildPart('/german/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $defaultNode->setProperty('i18n:de-url', '/german/parent/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:de-url', Argument::any())->shouldNotBeCalled();

        $englishDocument = $this->getDocumentMock();
        $englishDocument->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->documentManager->find('copy-uuid', 'en')->willReturn($englishDocument);

        $frenchDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $frenchDocument->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->documentManager->find('copy-uuid', 'fr')->willReturn($frenchDocument);

        $this->resourceSegmentSubscriber->updateCopiedDocument($event->reveal());
    }

    public function testUpdateCopiedDocumentForWrongDocument()
    {
        $document = new \stdClass();

        $event = $this->prophesize(CopyEvent::class);
        $event->getCopiedPath()->willReturn('/cmf/sulu_io/contents/page/parent/child');
        $event->getDocument()->willReturn($document);
        $this->documentInspector->getLocale($document)->willReturn('de');

        $this->documentInspector->getLocales(Argument::cetera())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->updateCopiedDocument($event->reveal());
    }

    public function testUpdateCopiedDocumentWithGhostParent()
    {
        $parentDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $copiedDocument = $this->prophesize(ResourceSegmentBehavior::class);

        $event = $this->prophesize(CopyEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getCopiedPath()->willReturn('/cmf/sulu_io/contents/page/parent/child');
        $this->documentInspector->getLocale($this->document->reveal())->willReturn('de');
        $this->documentManager->find('/cmf/sulu_io/contents/page/parent/child', 'de')
            ->willReturn($copiedDocument->reveal());
        $this->documentInspector->getUuid($copiedDocument->reveal())->willReturn('copy-uuid');

        $this->documentInspector->getLocales($copiedDocument->reveal())->willReturn(['de']);
        $this->documentInspector->getWebspace($copiedDocument->reveal())->willReturn('sulu_io');
        $this->documentInspector->getParent($copiedDocument->reveal())->willReturn($parentDocument->reveal());
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getUuid($parentDocument->reveal())->willReturn('parent-uuid');
        $this->documentInspector->getPath($copiedDocument->reveal())
            ->willReturn('/cmf/sulu_io/contents/page/parent/child');

        $defaultNode = $this->prophesize(NodeInterface::class);
        $defaultNode->getPropertyValue('i18n:de-url')->willReturn('/german/parent/child');
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->hasProperty('i18n:de-url')->willReturn(false);

        $this->defaultSession->getNode('/cmf/sulu_io/contents/page/parent/child')->willReturn($defaultNode);
        $this->liveSession->getNode('/cmf/sulu_io/contents/page/parent/child')->willReturn($liveNode);

        $germanDocument = $this->getDocumentMock();
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('copy-uuid', 'de')->willReturn($germanDocument);
        $germanDocument->getResourceSegment()->willReturn('/german/child');
        $this->resourceLocatorStrategy->getChildPart('/german/child')->willReturn('child');
        $this->resourceLocatorStrategy->getChildPart('/german/parent/child')->willReturn('child');
        $this->resourceLocatorStrategy->generate('child', 'parent-uuid', 'sulu_io', 'de')->willReturn('/child');
        $defaultNode->setProperty('i18n:de-url', '/child')->shouldBeCalled();
        $liveNode->setProperty('i18n:de-url', Argument::any())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->updateCopiedDocument($event->reveal());
    }

    private function getDocumentMock()
    {
        $document = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class);

        $this->documentInspector->getStructureMetadata(
            $document->reveal())->willReturn($this->structureMetadata->reveal()
        );

        return $document;
    }
}
