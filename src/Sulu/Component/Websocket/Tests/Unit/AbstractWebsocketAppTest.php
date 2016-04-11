<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\Tests\Unit;

use Doctrine\Common\Cache\ArrayCache;

class AbstractWebsocketAppTest extends \PHPUnit_Framework_TestCase
{
    public function testOpen()
    {
        $app = $this->getMockForAbstractClass('Sulu\Component\Websocket\AbstractWebsocketApp', [new ArrayCache()]);

        $reflectionClass = new \ReflectionClass('Sulu\Component\Websocket\AbstractWebsocketApp');
        $reflectionProperty = $reflectionClass->getProperty('clients');
        $reflectionProperty->setAccessible(true);

        $connection = $this->prophesize('Ratchet\ConnectionInterface');
        $connectionInstance = $connection->reveal();
        $connectionInstance->resourceId = uniqid();

        $app->OnOpen($connectionInstance);

        /** @var \SplObjectStorage $clients */
        $clients = $reflectionProperty->getValue($app);
        $this->assertTrue($clients->contains($connectionInstance));
    }

    public function testClose()
    {
        $app = $this->getMockForAbstractClass('Sulu\Component\Websocket\AbstractWebsocketApp', [new ArrayCache()]);
        $reflectionClass = new \ReflectionClass('Sulu\Component\Websocket\AbstractWebsocketApp');
        $reflectionProperty = $reflectionClass->getProperty('clients');
        $reflectionProperty->setAccessible(true);

        $connection = $this->prophesize('Ratchet\ConnectionInterface');
        $connectionInstance = $connection->reveal();
        $connectionInstance->resourceId = uniqid();

        $clients = new \SplObjectStorage();
        $clients->attach($connectionInstance);

        $app->OnClose($connectionInstance);

        /** @var \SplObjectStorage $clients */
        $clients = $reflectionProperty->getValue($app);
        $this->assertFalse($clients->contains($connectionInstance));
    }
}
