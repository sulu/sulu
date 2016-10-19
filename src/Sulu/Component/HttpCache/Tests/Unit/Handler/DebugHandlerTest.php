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

use Sulu\Component\Content\Compat\PageInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;
use Sulu\Component\HttpCache\Handler\DebugHandler;
use Sulu\Component\HttpCache\HandlerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

class DebugHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var string[]
     */
    private $handlerNames;

    /**
     * @var string
     */
    private $proxyClientName;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var PageInterface
     */
    private $page;

    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    public function setUp()
    {
        $this->parameterBag = new ParameterBag();
        $this->response = $this->prophesize(Response::class);
        $this->response->headers = $this->parameterBag;
        $this->structure = $this->prophesize(StructureInterface::class);
        $this->page = $this->prophesize(PageInterface::class);
        $this->cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);

        $this->handlerNames = ['one', 'two', 'three'];
        $this->proxyClientName = 'foo';

        $this->handler = new DebugHandler(
            $this->cacheLifetimeResolver->reveal(),
            $this->handlerNames,
            $this->proxyClientName
        );
    }

    public function testUpdateResponse()
    {
        $this->structure->getUuid()->willReturn('1234');

        $this->handler->updateResponse($this->response->reveal(), $this->structure->reveal());

        $this->assertEquals(implode(', ', $this->handlerNames), $this->parameterBag->get(DebugHandler::HEADER_HANDLERS));
        $this->assertEquals($this->proxyClientName, $this->parameterBag->get(DebugHandler::HEADER_CLIENT_NAME));
        $this->assertEquals('1234', $this->parameterBag->get(DebugHandler::HEADER_STRUCTURE_UUID));
    }

    public function testUpdateResponsePage()
    {
        $this->page->getCacheLifeTime()->willReturn(
            ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => '300']
        );
        $this->page->getUuid()->willReturn('1234');
        $this->cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, '300')->willReturn(300);
        $this->handler->updateResponse($this->response->reveal(), $this->page->reveal());

        $this->assertEquals(300, $this->parameterBag->get(DebugHandler::HEADER_STRUCTURE_TTL));
    }
}
