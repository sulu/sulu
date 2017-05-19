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

use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ContentBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\Handler\TagsHandler;
use Sulu\Component\HttpCache\HandlerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

class TagsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var BanInterface
     */
    private $proxyCache;

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var PropertyInterface
     */
    private $property1;

    /**
     * @var PropertyInterface
     */
    private $property2;

    public function setUp()
    {
        $this->structure = $this->prophesize(StructureInterface::class);
        $this->proxyCache = $this->prophesize(BanInterface::class);
        $this->parameterBag = $this->prophesize(ParameterBag::class);
        $this->response = $this->prophesize(Response::class);
        $this->response->headers = $this->parameterBag->reveal();
        $this->property1 = $this->prophesize(PropertyInterface::class);
        $this->property2 = $this->prophesize(PropertyInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->handler = new TagsHandler(
            $this->proxyCache->reveal(),
            $this->referenceStore->reveal()
        );
    }

    public function testInvalidateStructure()
    {
        $uuid = Uuid::uuid4()->toString();

        $this->structure->getUuid()->willReturn($uuid);

        $this->proxyCache->ban(
            [
                TagsHandler::TAGS_HEADER => '(' . preg_quote($uuid) . ')(,.+)?$',
            ]
        )->shouldBeCalled();
        $this->proxyCache->flush()->shouldBeCalled();

        $this->handler->invalidateStructure($this->structure->reveal());

        $this->handler->flush();
    }

    public function testUpdateResponse()
    {
        $ids = ['123-123-123', '123-321-123'];
        $this->structure->getUuid()->willReturn('321-123-321');

        $this->referenceStore->getAll()->willReturn($ids);

        $this->parameterBag->set('X-Cache-Tags', implode(',', array_merge(['321-123-321'], $ids)))->shouldBeCalled();

        $this->handler->updateResponse($this->response->reveal(), $this->structure->reveal());
    }
}
