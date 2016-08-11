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

use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\Handler\AggregateHandler;
use Sulu\Component\HttpCache\HandlerInterface;
use Symfony\Component\HttpFoundation\Response;

class AggregateHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInterface
     */
    private $handler;
    /**
     * @var HandlerInterface
     */
    private $handler1;

    /**
     * @var HandlerInterface
     */
    private $handler2;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var Response
     */
    private $response;

    public function setUp()
    {
        $this->structure = $this->prophesize('Sulu\Component\Content\Compat\StructureInterface');
        $this->response = $this->prophesize('Symfony\Component\HttpFoundation\Response');

        $this->handler1 = $this->prophesize('Sulu\Component\HttpCache\HandlerUpdateResponseInterface')
            ->willImplement('Sulu\Component\HttpCache\HandlerInvalidateStructureInterface')
            ->willImplement('Sulu\Component\HttpCache\HandlerFlushInterface');
        $this->handler2 = $this->prophesize('Sulu\Component\HttpCache\HandlerUpdateResponseInterface')
            ->willImplement('Sulu\Component\HttpCache\HandlerInvalidateStructureInterface')
            ->willImplement('Sulu\Component\HttpCache\HandlerFlushInterface');

        $this->handler = new AggregateHandler([
            $this->handler1->reveal(),
            $this->handler2->reveal(),
        ]);
    }

    public function testInvalidateStructure()
    {
        $this->handler1->invalidateStructure($this->structure->reveal())
            ->shouldBeCalled();
        $this->handler2->invalidateStructure($this->structure->reveal())
            ->shouldBeCalled();

        $this->handler->invalidateStructure($this->structure->reveal());
    }

    public function testUpdateResponse()
    {
        $this->handler1->updateResponse($this->response->reveal(), $this->structure->reveal())
            ->shouldBeCalled();
        $this->handler2->updateResponse($this->response->reveal(), $this->structure->reveal())
            ->shouldBeCalled();

        $this->handler->updateResponse($this->response->reveal(), $this->structure->reveal());
    }
}
