<?php

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\Content\Document\Subscriber\ExtensionSubscriber;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ExtensionSubscriberTest extends SubscriberTestCase
{
    public function setUp()
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
     * It should hydrate data from extensions
     */
    public function testHydrateExtensionsData()
    {
        $expectedData = array(
            'foo' => 'bar',
        );

        $document = new TestExtensionDocument();

        $this->hydrateEvent->getDocument()->willReturn($document);
        $this->inspector->getWebspace($document)->willReturn('sulu_io');
        $this->namespaceRegistry->getPrefix('extension_localized')->willReturn('ext_prefix');
        $this->extensionManager->getExtensions('foobar')->willReturn(array(
            $this->extension->reveal()
        ));
        $this->extension->getName()->willReturn('ext_1');
        $this->extension->setLanguageCode('de', 'ext_prefix', '')->shouldBeCalled();
        $this->extension->load(
            $this->node->reveal(),
            'sulu_io',
            'de'
        )->willReturn($expectedData);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());

        $this->assertEquals(array(
            'ext_1' => $expectedData,
        ), $document->getExtensionsData());
    }

    /**
     * It should persist data from extensions
     */
    public function testPersistExtensionsData()
    {
        $document = new TestExtensionDocument(
            array(
                'ext_1' => array(
                    'foo' => 'bar',
                )
            )
        );

        $this->persistEvent->getDocument()->willReturn($document);
        $this->inspector->getWebspace($document)->willReturn('sulu_io');
        $this->namespaceRegistry->getPrefix('extension_localized')->willReturn('ext_prefix');
        $this->extensionManager->getExtension('foobar', 'ext_1')->willReturn($this->extension->reveal());
        $this->extension->setLanguageCode('de', 'ext_prefix', '')->shouldBeCalled();
        $this->extension->save(
            $this->node->reveal(),
            array('foo' => 'bar'),
            'sulu_io',
            'de'
        )->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());

    }
}

class TestExtensionDocument implements ExtensionBehavior
{
    private $extensions;

    public function __construct(array $extensions = array())
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

    public function getStructureType() 
    {
        return 'foobar';
    }
    
    public function setStructureType($structureType)
    {
    }

    public function getContent() 
    {
    }

    public function getLocale() 
    {
    }
}
