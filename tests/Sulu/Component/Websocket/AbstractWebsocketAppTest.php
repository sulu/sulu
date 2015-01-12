<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket;

use Prophecy\PhpUnit\ProphecyTestCase;

class AbstractWebsocketAppTest extends ProphecyTestCase
{
    public function testOpen()
    {
        $app = $this->prophesize('Sulu\Component\Websocket\AbstractWebsocketApp');
        $reflectionClass = new \ReflectionClass(get_class($app));
        $reflectionProperty = $reflectionClass->getProperty('clients');
        $reflectionProperty->setAccessible(true);

        $connection = $this->prophesize('Ratchet\ConnectionInterface');
        $app->reveal()->OnOpen($connection->reveal());

        /** @var \SplObjectStorage $clients */
        $clients = $reflectionProperty->getValue($app);
        $this->assertTrue($clients->contains($connection->reveal()));
    }

    public function testClose()
    {
        $app = $this->prophesize('Sulu\Component\Websocket\AbstractWebsocketApp');
        $reflectionClass = new \ReflectionClass(get_class($app));
        $reflectionProperty = $reflectionClass->getProperty('clients');
        $reflectionProperty->setAccessible(true);

        $connection = $this->prophesize('Ratchet\ConnectionInterface');
        $clients = new \SplObjectStorage();
        $clients->attach($connection->reveal());

        $app->reveal()->OnClose($connection->reveal());

        /** @var \SplObjectStorage $clients */
        $clients = $reflectionProperty->getValue($app);
        $this->assertFalse($clients->contains($connection->reveal()));
    }
}
