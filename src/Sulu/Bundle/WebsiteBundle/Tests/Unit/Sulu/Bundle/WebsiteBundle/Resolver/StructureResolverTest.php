<?php

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Prophecy\Argument;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\StructureInterface;
use Prophecy\PhpUnit\ProphecyTestCase;

class StructureResolverTest extends ProphecyTestCase
{
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var ContentTypeInterface
     */
    private $contentType;

    public function setUp()
    {
        parent::setUp();

        $this->contentTypeManager = $this->prophesize('Sulu\Component\Content\ContentTypeManagerInterface');
        $this->contentType = $this->prophesize('Sulu\Component\Content\ContentTypeInterface');

        $this->structureResolver = new StructureResolver($this->contentTypeManager->reveal());
    }

    public function testResolve()
    {
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType);

        $this->contentType->getViewData(Argument::any())->willReturn('view');
        $this->contentType->getContentData(Argument::any())->willReturn('content');

        $property = $this->prophesize('Sulu\Component\Content\PropertyInterface');
        $property->getName()->willReturn('property');
        $property->getContentTypeName()->willReturn('content_type');

        $structure = $this->prophesize('Sulu\Component\Content\StructureInterface');
        $structure->getExt()->willReturn('ext');
        $structure->getUuid()->willReturn('some-uuid');
        $structure->getProperties(true)->willReturn(array($property->reveal()));
        $structure->getCreator()->willReturn(1);
        $structure->getChanger()->willReturn(1);
        $structure->getCreated()->willReturn('date');
        $structure->getChanged()->willReturn('date');

        $expected = array(
            'extension' => 'ext',
            'uuid' => 'some-uuid',
            'view' => array(
                'property' => 'view'
            ),
            'content' => array(
                'property' => 'content'
            ),
            'creator' => 1,
            'changer' => 1,
            'created' => 'date',
            'changed' => 'date',
        );

        $this->assertEquals($expected, $this->structureResolver->resolve($structure->reveal()));
    }
}
