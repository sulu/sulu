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

use PHPCR\NodeInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Subscriber\ExtensionSubscriber;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\NamespaceRegistry;

class ExtensionSubscriberTest extends SubscriberTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $inspector;

    /**
     * @var ObjectProphecy<NamespaceRegistry>
     */
    private $namespaceRegistry;

    /**
     * @var ObjectProphecy<ExtensionManagerInterface>
     */
    private $extensionManager;

    /**
     * @var ObjectProphecy<ExtensionInterface>
     */
    private $extension;

    /**
     * @var ObjectProphecy<DocumentAccessor>
     */
    private $documentAccessor;

    /**
     * @var ExtensionSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->extensionManager = $this->prophesize(ExtensionManagerInterface::class);
        $this->extension = $this->prophesize(ExtensionInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->documentAccessor = $this->prophesize(DocumentAccessor::class);

        $this->subscriber = new ExtensionSubscriber(
            $this->encoder->reveal(),
            $this->extensionManager->reveal(),
            $this->inspector->reveal(),
            $this->namespaceRegistry->reveal()
        );

        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('de');
        $this->hydrateEvent->getAccessor()->willReturn($this->documentAccessor->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getLocale()->willReturn('de');
    }

    /**
     * It should hydrate data from extensions.
     */
    public function testHydrateExtensionsData(): void
    {
        $expectedData = [
            'foo' => 'bar',
        ];

        $document = new TestExtensionDocument();

        $this->hydrateEvent->getLocale()->shouldNotBeCalled();

        $this->hydrateEvent->getDocument()->willReturn($document);
        $this->inspector->getWebspace($document)->willReturn('sulu_io');
        $this->inspector->getLocale($document)->shouldBeCalled()->willReturn('de');
        $this->namespaceRegistry->getPrefix('extension_localized')->willReturn('ext_prefix');
        $this->extensionManager->getExtension('foobar', 'ext_1')->willReturn(
            $this->extension->reveal()
        );
        $this->extension->getName()->willReturn('ext_1');
        $this->extension->setLanguageCode('de', 'ext_prefix', '')->shouldBeCalled();
        $this->extension->load(
            $this->node->reveal(),
            'sulu_io',
            'de'
        )->willReturn($expectedData);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());

        $this->assertEquals(
            $document->getExtensionsData()['ext_1'],
            $expectedData
        );
    }

    /**
     * It should return early if the locale is null.
     */
    public function testPersistLocaleIsNull(): void
    {
        $document = new TestExtensionDocument();
        $this->persistEvent->getLocale()->willReturn(null);
        $this->persistEvent->getDocument()->willReturn($document);
        $this->extensionManager->getExtensions()->shouldNotBeCalled();

        $this->subscriber->saveExtensionData($this->persistEvent->reveal());
    }

    /**
     * It should persist data from extensions.
     */
    public function testPersistExtensionsData(): void
    {
        $document = new TestExtensionDocument(
            [
                'ext_1' => [
                    'foo' => 'bar',
                ],
            ]
        );

        $this->persistEvent->getDocument()->willReturn($document);
        $this->inspector->getWebspace($document)->willReturn('sulu_io');
        $this->inspector->getLocale($document)->shouldBeCalled()->willReturn('de');
        $this->namespaceRegistry->getPrefix('extension_localized')->willReturn('ext_prefix');
        $this->extensionManager->getExtensions('foobar')->willReturn([
            'ext_1' => $this->extension->reveal(),
        ]);
        $this->extension->getName()->willReturn('ext_1');
        $this->extension->setLanguageCode('de', 'ext_prefix', '')->shouldBeCalled();
        $this->extension->save(
            $this->node->reveal(),
            ['foo' => 'bar'],
            'sulu_io',
            'de'
        )->shouldBeCalled();

        $this->subscriber->saveExtensionData($this->persistEvent->reveal());
    }
}

class TestExtensionDocument implements ExtensionBehavior
{
    private $extensions;

    public function __construct(array $extensions = [])
    {
        $this->extensions = $extensions;
    }

    public function getExtensionsData()
    {
        return $this->extensions;
    }

    public function setExtensionsData($data)
    {
        $this->extensions = $data;
    }

    public function setExtension($name, $data)
    {
    }

    public function getStructureType()
    {
        return 'foobar';
    }

    public function setStructureType($structureType)
    {
    }

    public function getStructure()
    {
    }

    public function getLocale()
    {
    }

    public function setLocale($locale)
    {
    }

    public function getOriginalLocale()
    {
    }

    public function setOriginalLocale($locale)
    {
    }
}
