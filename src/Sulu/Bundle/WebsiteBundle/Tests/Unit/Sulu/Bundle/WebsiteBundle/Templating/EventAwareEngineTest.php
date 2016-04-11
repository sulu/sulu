<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Templating;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Templating\EngineEvents;
use Sulu\Bundle\WebsiteBundle\Templating\EventAwareEngine;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class EventAwareEngineTest extends \PHPUnit_Framework_TestCase
{
    public function testRender()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventableEngine = new EventAwareEngine($engine->reveal(), $eventDispatcher->reveal());

        $engine->render('test', ['test' => 1])->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(EngineEvents::INITIALIZE, null)->shouldBeCalledTimes(1);

        $this->assertTrue($eventableEngine->render('test', ['test' => 1]));
    }

    public function testRenderTwice()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventableEngine = new EventAwareEngine($engine->reveal(), $eventDispatcher->reveal());

        $engine->render('test', ['test' => 1])->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(EngineEvents::INITIALIZE, null)->shouldBeCalledTimes(1);

        $this->assertTrue($eventableEngine->render('test', ['test' => 1]));
        $this->assertTrue($eventableEngine->render('test', ['test' => 1]));
    }

    public function testRenderResponse()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventableEngine = new EventAwareEngine($engine->reveal(), $eventDispatcher->reveal());

        $engine->renderResponse('test', ['test' => 1], Argument::type(Response::class))
            ->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(EngineEvents::INITIALIZE, null)->shouldBeCalledTimes(1)->shouldBeCalled();

        $this->assertTrue(
            $eventableEngine->renderResponse('test', ['test' => 1], $this->prophesize(Response::class)->reveal())
        );
    }

    public function testRenderResponseTwice()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventableEngine = new EventAwareEngine($engine->reveal(), $eventDispatcher->reveal());

        $engine->renderResponse('test', ['test' => 1], Argument::type(Response::class))
            ->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(EngineEvents::INITIALIZE, null)->shouldBeCalledTimes(1);

        $this->assertTrue(
            $eventableEngine->renderResponse('test', ['test' => 1], $this->prophesize(Response::class)->reveal())
        );
        $this->assertTrue(
            $eventableEngine->renderResponse('test', ['test' => 1], $this->prophesize(Response::class)->reveal())
        );
    }

    public function testExists()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventableEngine = new EventAwareEngine($engine->reveal(), $eventDispatcher->reveal());

        $engine->exists('test')->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(EngineEvents::INITIALIZE, null)->shouldBeCalledTimes(1);

        $this->assertTrue($eventableEngine->exists('test'));
    }

    public function testExistsTwice()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventableEngine = new EventAwareEngine($engine->reveal(), $eventDispatcher->reveal());

        $engine->exists('test')->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(EngineEvents::INITIALIZE, null)->shouldBeCalledTimes(1);

        $this->assertTrue($eventableEngine->exists('test'));
        $this->assertTrue($eventableEngine->exists('test'));
    }

    public function testSupports()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventableEngine = new EventAwareEngine($engine->reveal(), $eventDispatcher->reveal());

        $engine->supports('test')->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(EngineEvents::INITIALIZE, null)->shouldBeCalledTimes(1);

        $this->assertTrue($eventableEngine->supports('test'));
    }

    public function testSupportsTwice()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventableEngine = new EventAwareEngine($engine->reveal(), $eventDispatcher->reveal());

        $engine->supports('test')->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(EngineEvents::INITIALIZE, null)->shouldBeCalledTimes(1);

        $this->assertTrue($eventableEngine->supports('test'));
        $this->assertTrue($eventableEngine->supports('test'));
    }
}
