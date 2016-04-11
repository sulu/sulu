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

use Sulu\Component\HttpCache\Handler\DebugHandler;
use Symfony\Component\HttpFoundation\ParameterBag;

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

    public function setUp()
    {
        parent::setUp();

        $this->parameterBag = new ParameterBag();
        $this->response = $this->prophesize('Symfony\Component\HttpFoundation\Response');
        $this->response->headers = $this->parameterBag;
        $this->structure = $this->prophesize('Sulu\Component\Content\Compat\StructureInterface');
        $this->page = $this->prophesize('Sulu\Component\Content\Compat\PageInterface');

        $this->handlerNames = ['one', 'two', 'three'];
        $this->proxyClientName = 'foo';

        $this->handler = new DebugHandler(
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
        $this->page->getCacheLifeTime()->willReturn('300');
        $this->page->getUuid()->willReturn('1234');
        $this->handler->updateResponse($this->response->reveal(), $this->page->reveal());

        $this->assertEquals('300', $this->parameterBag->get(DebugHandler::HEADER_STRUCTURE_TTL));
    }
}
