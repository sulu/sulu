<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Bridge;

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\Content\Structure\Factory\StructureFactoryInterface;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use PHPCR\PropertyInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;

class DocumentInspectorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->pathRegistry = $this->prophesize(PathSegmentRegistry::class);
        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->document = new \stdClass;
        $this->node = $this->prophesize(NodeInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactory::class);
        $this->structureFactory = $this->prophesize(StructureFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->proxyFactory = $this->prophesize(ProxyFactory::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->documentInspector = new DocumentInspector(
            $this->documentRegistry->reveal(),
            $this->pathRegistry->reveal(),
            $this->namespaceRegistry->reveal(),
            $this->proxyFactory->reveal(),
            $this->metadataFactory->reveal(),
            $this->structureFactory->reveal(),
            $this->encoder->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    /**
     * It should return the webspace for the given document
     *
     * @dataProvider provideGetWebspace
     */
    public function testGetWebspace($path, $expectedWebspace)
    {
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->pathRegistry->getPathSegment('base')->willReturn('cmf');
        $this->node->getPath()->willReturn($path);

        $webspace = $this->documentInspector->getWebspace($this->document);
        $this->assertEquals($expectedWebspace, $webspace);
    }

    public function provideGetWebspace()
    {
        return array(
            array('/cmf/sulu_io/content/articles/article-one', 'sulu_io'),
            array('/cmfcontent/articles/article-one', null),
            array('/cmf/webspace_five', null),
            array('/cmf/webspace_five/foo/bar/dar/ding', 'webspace_five'),
            array('', null),
            array('asdasd', null),
        );
    }

    /**
     * It should return the Structure for a document implementing ContentBehavior
     */
    public function testGetStructure()
    {
        $structure = new \stdClass;
        $document = $this->prophesize(ContentBehavior::class);
        $document->getStructureType()->willReturn('foo');

        $this->metadataFactory->getMetadataForClass(get_class($document->reveal()))->willReturn($this->metadata->reveal());
        $this->metadata->getAlias()->willReturn('page');
        $this->structureFactory->getStructure('page', 'foo')->willReturn($structure);
        $result = $this->documentInspector->getStructure($document->reveal());
        $this->assertSame($structure, $result);
    }

    /**
     * It should return the available localizations for the document
     */
    public function testGetLocales()
    {
        $propertyNames = array(
            'foo:aa-template',
            'foo:bb-template',
            'doo:dd-template',
            'foo:cc-barbar',
            'foo:ee-template',
        );

        $expectedLocales = array('aa', 'bb', 'cc', 'ee');

        $properties = array();
        foreach ($propertyNames as $propertyName) {
            $property = $this->prophesize(PropertyInterface::class);
            $property->getName()->willReturn($propertyName);
            $properties[] = $property;
        }

        $document = $this->prophesize(ContentBehavior::class);
        $this->documentRegistry->getNodeForDocument($document)->willReturn($this->node->reveal());
        $this->namespaceRegistry->getPrefix('system_localized')->willReturn('foo');
        $this->node->getProperties()->willReturn($properties);

        $locales = $this->documentInspector->getLocales($document->reveal());

        $this->assertEquals(
            $expectedLocales,
            $locales
        );
    }

    /**
     * It should return the webspace name for a given node
     *
     * @dataProvider provideWebspace
     */
    public function testWebspace($path, $expectedWebspace)
    {
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->getPath()->willReturn($path);
        $this->pathRegistry->getPathSegment('base')->willReturn('cmf');
        $webspace = $this->documentInspector->getWebspace($this->document);

        $this->assertEquals($expectedWebspace, $webspace);
    }

    public function provideWebspace()
    {
        return array(
            array(
                '/cmf/foobar/bar',
                'foobar',
            ),
            array(
                '/foo/foo',
                null,
            ),
            array(
                '/cmf/hello',
                null
            ),
        );
    }
}
