<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Document;

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use PHPCR\NodeInterface;


class DocumentInspectorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->pathRegistry = $this->prophesize(PathSegmentRegistry::class);
        $this->document = new \stdClass;
        $this->node = $this->prophesize(NodeInterface::class);
        $this->documentInspector = new DocumentInspector(
            $this->documentRegistry->reveal(),
            $this->pathRegistry->reveal()
        );
    }

    /**
     * It should return the current locale for the given document
     */
    public function testGetLocale()
    {
        $this->documentRegistry->getLocaleForDocument($this->document)->willReturn('de');

        $result = $this->documentInspector->getLocale($this->document);
        $this->assertEquals('de', $result);
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

}

