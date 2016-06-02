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
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Subscriber\ResourceSegmentSubscriber;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
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
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

    /**
     * @var ResourceSegmentBehavior
     */
    private $document;

    /**
     * @var StructureMetadata
     */
    private $structureMetadata;

    /**
     * @var ResourceSegmentSubscriber
     */
    private $resourceSegmentSubscriber;

    public function setUp()
    {
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->rlpStrategy = $this->prophesize(RlpStrategyInterface::class);
        $this->document = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $this->structureMetadata = $this->prophesize(StructureMetadata::class);

        $this->documentInspector->getStructureMetadata($this->document->reveal())
            ->willReturn($this->structureMetadata->reveal());

        $this->resourceSegmentSubscriber = new ResourceSegmentSubscriber(
            $this->encoder->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->rlpStrategy->reveal()
        );
    }

    public function testHydrate()
    {
        $locale = 'de';
        $segment = '/test';
        $propertyName = 'url';
        $localizedPropertyName = sprintf('i18n:%s-%s', $locale, $propertyName);

        $event = $this->prophesize(AbstractMappingEvent::class);

        $event->getDocument()->willReturn($this->document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $event->getNode()->willReturn($node->reveal());

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn($propertyName);
        $this->structureMetadata->getPropertyByTagName('sulu.rlp')->willReturn($property->reveal());

        $this->documentInspector->getOriginalLocale($this->document->reveal())->willReturn($locale);
        $this->encoder->localizedSystemName($propertyName, $locale)->willReturn($localizedPropertyName);

        $node->getPropertyValueWithDefault($localizedPropertyName, '')->willReturn($segment);

        // Asserts
        $this->document->setResourceSegment($segment)->shouldBeCalled();

        $this->resourceSegmentSubscriber->handleHydrate($event->reveal());
    }

    public function testPersistDocument()
    {
        $locale = 'de';
        $segment = '/test';
        $propertyName = 'url';
        $localizedPropertyName = sprintf('i18n:%s-%s', $locale, $propertyName);

        $event = $this->prophesize(PersistEvent::class);

        $event->getDocument()->willReturn($this->document->reveal());

        $property = $this->prophesize(PropertyMetadata::class);
        $property->getName()->willReturn($propertyName);
        $this->structureMetadata->getPropertyByTagName('sulu.rlp')->willReturn($property->reveal());

        $localizedProperty = $this->prophesize(PropertyInterface::class);
        $localizedProperty->getName()->willReturn($localizedPropertyName);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getProperty($propertyName)->willReturn($localizedProperty->reveal());
        $this->document->getStructure()->willReturn($structure->reveal());

        $this->document->getResourceSegment()->willReturn($segment);

        // Asserts
        $localizedProperty->setValue($segment)->shouldBeCalled();

        $this->resourceSegmentSubscriber->handlePersistDocument($event->reveal());
    }

    public function testPersistDocumentWithoutLocale()
    {
        $event = $this->prophesize(PersistEvent::class);
        $document = $this->prophesize(ResourceSegmentBehavior::class);
        $document->willImplement(StructureBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn(null)->shouldBeCalled();
        $this->rlpStrategy->save($document->reveal(), Argument::any())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }

    public function testPersistRoute()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getLocale()->willReturn('de');

        $this->document->getRedirectType()->willReturn(RedirectType::NONE);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->rlpStrategy->save($this->document->reveal(), null)->shouldBeCalled();
        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }

    public function testPersistRouteForRedirect()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getLocale()->willReturn('de');

        $this->document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->rlpStrategy->save(Argument::any())->shouldNotBeCalled();
        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }

    public function testMoveRoutes()
    {
        $parentDocument = new \stdClass();

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de', 'en']);
        $this->documentInspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getParent($this->document->reveal())->willReturn($parentDocument);
        $this->documentInspector->getUuid($parentDocument)->willReturn('parent-uuid');

        $germanDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'de')->willReturn($germanDocument);
        $this->rlpStrategy->loadByContentUuid('parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent');
        $this->rlpStrategy->loadByContentUuid('uuid', 'sulu_io', 'de')->willReturn('/german/child');
        $this->rlpStrategy->getChildPart('/german/child')->willReturn('child');
        $this->rlpStrategy->generate('child', '/german/parent', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $germanDocument->setResourceSegment('/german/parent/child')->shouldBeCalled();
        $this->documentManager->persist($germanDocument, 'de')->shouldBeCalled();

        $englishDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $englishDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'en')->willReturn($englishDocument);
        $this->rlpStrategy->loadByContentUuid('parent-uuid', 'sulu_io', 'en')->willReturn('/english/parent');
        $this->rlpStrategy->loadByContentUuid('uuid', 'sulu_io', 'en')->willReturn('/english/child');
        $this->rlpStrategy->getChildPart('/english/child')->willReturn('child');
        $this->rlpStrategy->generate('child', '/english/parent', 'sulu_io', 'en')->willReturn('/english/parent/child');
        $englishDocument->setResourceSegment('/english/parent/child')->shouldBeCalled();
        $this->documentManager->persist($englishDocument, 'en')->shouldBeCalled();

        $this->resourceSegmentSubscriber->moveRoutes($event->reveal());
    }

    public function testMoveRoutesWithRedirects()
    {
        $parentDocument = new \stdClass();

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de', 'en', 'fr']);
        $this->documentInspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getParent($this->document->reveal())->willReturn($parentDocument);
        $this->documentInspector->getUuid($parentDocument)->willReturn('parent-uuid');

        $germanDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'de')->willReturn($germanDocument);
        $this->rlpStrategy->loadByContentUuid('parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent');
        $this->rlpStrategy->loadByContentUuid('uuid', 'sulu_io', 'de')->willReturn('/german/child');
        $this->rlpStrategy->getChildPart('/german/child')->willReturn('child');
        $this->rlpStrategy->generate('child', '/german/parent', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $germanDocument->setResourceSegment('/german/parent/child')->shouldBeCalled();
        $this->documentManager->persist($germanDocument, 'de')->shouldBeCalled();

        $englishDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $englishDocument->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->documentManager->find('uuid', 'en')->willReturn($englishDocument);
        $this->documentManager->persist($englishDocument, 'en')->shouldNotBeCalled();

        $frenchDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $frenchDocument->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->documentManager->find('uuid', 'fr')->willReturn($frenchDocument);
        $this->documentManager->persist($frenchDocument, 'fr')->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->moveRoutes($event->reveal());
    }

    public function testMoveRoutesForWrongDocument()
    {
        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn(new \stdClass());

        $this->documentInspector->getLocales(Argument::cetera())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->moveRoutes($event->reveal());
    }

    public function testMoveRoutesWithGhostParent()
    {
        $parentDocument = new \stdClass();

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de']);
        $this->documentInspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getParent($this->document->reveal())->willReturn($parentDocument);
        $this->documentInspector->getUuid($parentDocument)->willReturn('parent-uuid');

        $germanDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('uuid', 'de')->willReturn($germanDocument);
        $this->rlpStrategy->loadByContentUuid('parent-uuid', 'sulu_io', 'de')
            ->willThrow(ResourceLocatorNotFoundException::class);
        $this->rlpStrategy->loadByContentUuid('uuid', 'sulu_io', 'de')->willReturn('/german/child');
        $this->rlpStrategy->getChildPart('/german/child')->willReturn('child');
        $this->rlpStrategy->generate('child', null, 'sulu_io', 'de')->willReturn('/child');
        $germanDocument->setResourceSegment('/child')->shouldBeCalled();
        $this->documentManager->persist($germanDocument, 'de')->shouldBeCalled();

        $this->resourceSegmentSubscriber->moveRoutes($event->reveal());
    }

    public function testCopyRoutes()
    {
        $parentDocument = new \stdClass();
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
        $this->documentInspector->getParent($copiedDocument->reveal())->willReturn($parentDocument);
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getUuid($parentDocument)->willReturn('parent-uuid');

        $germanDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('copy-uuid', 'de')->willReturn($germanDocument);
        $this->rlpStrategy->loadByContentUuid('parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent');
        $this->rlpStrategy->loadByContentUuid('uuid', 'sulu_io', 'de')->willReturn('/german/child');
        $this->rlpStrategy->getChildPart('/german/child')->willReturn('child');
        $this->rlpStrategy->generate('child', '/german/parent', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $germanDocument->setResourceSegment('/german/parent/child')->shouldBeCalled();
        $this->documentManager->persist($germanDocument, 'de')->shouldBeCalled();

        $englishDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $englishDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('copy-uuid', 'en')->willReturn($englishDocument);
        $this->rlpStrategy->loadByContentUuid('parent-uuid', 'sulu_io', 'en')->willReturn('/english/parent');
        $this->rlpStrategy->loadByContentUuid('uuid', 'sulu_io', 'en')->willReturn('/english/child');
        $this->rlpStrategy->getChildPart('/english/child')->willReturn('child');
        $this->rlpStrategy->generate('child', '/english/parent', 'sulu_io', 'en')->willReturn('/english/parent/child');
        $englishDocument->setResourceSegment('/english/parent/child')->shouldBeCalled();
        $this->documentManager->persist($englishDocument, 'en')->shouldBeCalled();

        $this->resourceSegmentSubscriber->copyRoutes($event->reveal());
    }

    public function testCopyRoutesWithRedirects()
    {
        $parentDocument = new \stdClass();
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
        $this->documentInspector->getParent($copiedDocument->reveal())->willReturn($parentDocument);
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getUuid($parentDocument)->willReturn('parent-uuid');

        $germanDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('copy-uuid', 'de')->willReturn($germanDocument);
        $this->rlpStrategy->loadByContentUuid('parent-uuid', 'sulu_io', 'de')->willReturn('/german/parent');
        $this->rlpStrategy->loadByContentUuid('uuid', 'sulu_io', 'de')->willReturn('/german/child');
        $this->rlpStrategy->getChildPart('/german/child')->willReturn('child');
        $this->rlpStrategy->generate('child', '/german/parent', 'sulu_io', 'de')->willReturn('/german/parent/child');
        $germanDocument->setResourceSegment('/german/parent/child')->shouldBeCalled();
        $this->documentManager->persist($germanDocument, 'de')->shouldBeCalled();

        $englishDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $englishDocument->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->documentManager->find('copy-uuid', 'en')->willReturn($englishDocument);
        $this->documentManager->persist($englishDocument, 'en')->shouldNotBeCalled();

        $frenchDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $frenchDocument->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->documentManager->find('copy-uuid', 'fr')->willReturn($frenchDocument);
        $this->documentManager->persist($frenchDocument, 'fr')->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->copyRoutes($event->reveal());
    }

    public function testCopyRoutesForWrongDocument()
    {
        $document = new \stdClass();

        $event = $this->prophesize(CopyEvent::class);
        $event->getCopiedPath()->willReturn('/cmf/sulu_io/contents/page/parent/child');
        $event->getDocument()->willReturn($document);
        $this->documentInspector->getLocale($document)->willReturn('de');

        $this->documentInspector->getLocales(Argument::cetera())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->copyRoutes($event->reveal());
    }

    public function testCopyRoutesWithGhostParent()
    {
        $parentDocument = new \stdClass();
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
        $this->documentInspector->getParent($copiedDocument->reveal())->willReturn($parentDocument);
        $this->documentInspector->getUuid($this->document->reveal())->willReturn('uuid');
        $this->documentInspector->getUuid($parentDocument)->willReturn('parent-uuid');

        $germanDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $germanDocument->getRedirectType()->willReturn(RedirectType::NONE);
        $this->documentManager->find('copy-uuid', 'de')->willReturn($germanDocument);
        $this->rlpStrategy->loadByContentUuid('parent-uuid', 'sulu_io', 'de')
            ->willThrow(ResourceLocatorNotFoundException::class);
        $this->rlpStrategy->loadByContentUuid('uuid', 'sulu_io', 'de')->willReturn('/german/child');
        $this->rlpStrategy->getChildPart('/german/child')->willReturn('child');
        $this->rlpStrategy->generate('child', null, 'sulu_io', 'de')->willReturn('/child');
        $germanDocument->setResourceSegment('/child')->shouldBeCalled();
        $this->documentManager->persist($germanDocument, 'de')->shouldBeCalled();

        $this->resourceSegmentSubscriber->copyRoutes($event->reveal());
    }
}
