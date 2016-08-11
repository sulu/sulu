<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Tests\Unit\Handler;

use Prophecy\Argument;
use Sulu\Component\HttpCache\Handler\TagsHandler;

class TagsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var StructureInterface
     */
    private $structure;

    public function setUp()
    {
        $this->structure = $this->prophesize('Sulu\Component\Content\Compat\StructureInterface');
        $this->proxyCache = $this->prophesize('FOS\HttpCache\ProxyClient\Invalidation\BanInterface');
        $this->parameterBag = $this->prophesize('Symfony\Component\HttpFoundation\ParameterBag');
        $this->response = $this->prophesize('Symfony\Component\HttpFoundation\Response');
        $this->response->headers = $this->parameterBag;
        $this->property1 = $this->prophesize('Sulu\Component\Content\Compat\PropertyInterface');
        $this->property2 = $this->prophesize('Sulu\Component\Content\Compat\PropertyInterface');
        $this->contentType1 = $this->prophesize('Sulu\Component\Content\ContentTypeInterface');
        $this->contentType2 = $this->prophesize('Sulu\Component\Content\ContentTypeInterface');
        $this->contentTypeManager = $this->prophesize('Sulu\Component\Content\ContentTypeManager');

        $this->handler = new TagsHandler(
            $this->proxyCache->reveal(),
            $this->contentTypeManager->reveal()
        );
    }

    public function testInvalidateStructure()
    {
        $this->structure->getUuid()->willReturn('this-is-uuid');

        $this->proxyCache->ban([
            TagsHandler::TAGS_HEADER => '(structure\-this\-is\-uuid)(,.+)?$',
        ])->shouldBeCalled();
        $this->proxyCache->flush()->shouldBeCalled();

        $this->handler->invalidateStructure($this->structure->reveal());

        $this->handler->flush();
    }

    public function testUpdateResponse()
    {
        $expectedTags = [
            'structure-1', 'structure-2', 'structure-3', 'structure-4',
        ];

        $this->structure->getUuid()->willReturn('1');
        $this->structure->getProperties(true)->willReturn([
            $this->property1->reveal(),
            $this->property2->reveal(),
        ]);
        $this->property1->getContentTypeName()->willReturn('type1');
        $this->property2->getContentTypeName()->willReturn('type2');

        $this->contentTypeManager->get('type1')->willReturn($this->contentType1);
        $this->contentTypeManager->get('type2')->willReturn($this->contentType2);

        $this->contentType1->getReferencedUuids(Argument::any())->willReturn([
            '2',
        ]);
        $this->contentType2->getReferencedUuids(Argument::any())->willReturn([
            '3',
            '4',
        ]);

        $this->parameterBag->set('X-Cache-Tags', implode(',', $expectedTags))->shouldBeCalled();

        $this->handler->updateResponse($this->response->reveal(), $this->structure->reveal());
    }
}
