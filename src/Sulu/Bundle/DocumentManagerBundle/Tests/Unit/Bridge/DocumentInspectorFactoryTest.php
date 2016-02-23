<?php

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Bridge;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspectorFactory;

class DocumentInspectorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var PathSegmentRegistry
     */
    private $pathRegistry;

    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var StructureMetadataFactory
     */
    private $structureFactory;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var WebspaceManager
     */
    private $webspaceManager;

    /**
     * @var DocumentInspectorFactory
     */
    private $factory;

    public function setUp()
    {
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->pathRegistry = $this->prophesize(PathSegmentRegistry::class);
        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->proxyFactory = $this->prophesize(ProxyFactory::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->context = $this->prophesize(DocumentManagerContext::class);
        $this->context->getProxyFactory()->willReturn($this->proxyFactory->reveal());
        $this->context->getRegistry()->willReturn($this->documentRegistry->reveal());
;
        $this->factory = new DocumentInspectorFactory(
            $this->pathRegistry->reveal(),
            $this->namespaceRegistry->reveal(),
            $this->metadataFactory->reveal(),
            $this->structureFactory->reveal(),
            $this->encoder->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    /**
     * It should return a document inspector.
     */
    public function testGetInspector()
    {
        $this->assertInstanceOf(
            DocumentInspector::class,
            $this->factory->getInspector($this->context->reveal())
        );
    }
}
