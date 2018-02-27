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
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
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
     * @var ReferenceStorePoolInterface
     */
    private $referenceStorePool;

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

    public function setUp()
    {
        $this->structure = $this->prophesize(StructureInterface::class);
        $this->proxyCache = $this->prophesize(BanInterface::class);
        $this->parameterBag = $this->prophesize(ParameterBag::class);
        $this->response = $this->prophesize(Response::class);
        $this->response->headers = $this->parameterBag->reveal();
        $this->referenceStorePool = $this->prophesize(ReferenceStorePoolInterface::class);

        $this->handler = new TagsHandler(
            $this->proxyCache->reveal(), $this->referenceStorePool->reveal()
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

    public function testInvalidateReference()
    {
        $this->proxyCache->ban(
            [
                TagsHandler::TAGS_HEADER => '(' . preg_quote('test-1') . ')(,.+)?$',
            ]
        )->shouldBeCalled();
        $this->proxyCache->flush()->shouldBeCalled();

        $this->handler->invalidateReference('test', 1);

        $this->handler->flush();
    }

    public function testInvalidateReferenceDuplicated()
    {
        $this->proxyCache->ban(
            [
                TagsHandler::TAGS_HEADER => '(' . preg_quote('test-1') . ')(,.+)?$',
            ]
        )->shouldBeCalledTimes(1);
        $this->proxyCache->flush()->shouldBeCalled();

        $this->handler->invalidateReference('test', 1);
        $this->handler->invalidateReference('test', 1);

        $this->handler->flush();
    }

    public function testUpdateResponse()
    {
        $id = Uuid::uuid4()->toString();

        $articles = [Uuid::uuid4()->toString(), Uuid::uuid4()->toString()];
        $articleStore = $this->prophesize(ReferenceStoreInterface::class);
        $articleStore->getAll()->willReturn($articles);

        $contacts = [1];
        $contactStore = $this->prophesize(ReferenceStoreInterface::class);
        $contactStore->getAll()->willReturn($contacts);

        $this->structure->getUuid()->willReturn($id);

        $this->referenceStorePool->getStores()->willReturn(
            ['article' => $articleStore->reveal(), 'contact' => $contactStore->reveal()]
        );

        $this->parameterBag->set('X-Cache-Tags', implode(',', array_merge([$id], $articles, ['contact-1'])))
            ->shouldBeCalled();

        $this->handler->updateResponse($this->response->reveal(), $this->structure->reveal());
    }
}
