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

class RatchetAppManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $loop = $this->prophesize('React\EventLoop\LoopInterface');

        $manager = new RatchetAppManager(9876, 'sulu.io', '192.168.0.1', $loop->reveal());

        $this->assertEquals(9876, $manager->getPort());
        $this->assertEquals('sulu.io', $manager->getHttpHost());
        $this->assertEquals('192.168.0.1', $manager->getIpAddress());
        $this->assertEquals($loop->reveal(), $manager->getLoop());
    }

    public function testAdd()
    {
        $app = $this->prophesize('Sulu\Component\Websocket\WebsocketAppInterface');
        $app->getName()->willReturn('test');

        $manager = new RatchetAppManager(9876);

        $manager->add('/content', $app->reveal());

        $this->assertEquals(
            array(
                'test' => array(
                    'route' => '/content',
                    'app' => $app->reveal(),
                    'name' => 'test',
                    'allowedOrigins' => array('*'),
                    'httpHost' => 'localhost',
                ),
            ),
            $manager->getApps()
        );
    }

    public function testAddAllowedOrigins()
    {
        $app = $this->prophesize('Sulu\Component\Websocket\WebsocketAppInterface');
        $app->getName()->willReturn('test');

        $manager = new RatchetAppManager(9876);

        $manager->add('/content', $app->reveal(), array('test'));

        $this->assertEquals(
            array(
                'test' => array(
                    'route' => '/content',
                    'app' => $app->reveal(),
                    'name' => 'test',
                    'allowedOrigins' => array('test'),
                    'httpHost' => 'localhost',
                ),
            ),
            $manager->getApps()
        );
    }

    public function testAddHttpHost()
    {
        $sessionHandler = $this->prophesize('SessionHandlerInterface');
        $app = $this->prophesize('Sulu\Component\Websocket\WebsocketAppInterface');
        $app->getName()->willReturn('test');

        $manager = new RatchetAppManager(9876, $sessionHandler->reveal());

        $manager->add('/content', $app->reveal(), array('test'), 'sulu.io');

        $this->assertEquals(
            array(
                'test' => array(
                    'route' => '/content',
                    'app' => $app->reveal(),
                    'name' => 'test',
                    'allowedOrigins' => array('test'),
                    'httpHost' => 'sulu.io',
                ),
            ),
            $manager->getApps()
        );
    }
}
