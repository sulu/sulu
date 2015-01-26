<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\Tests\Unit\Controller;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Bundle\WebsocketBundle\Controller\FallbackController;

class FallbackControllerTest extends ProphecyTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testSend()
    {
        $appManager = $this->prophesize('Sulu\Component\Websocket\AppManagerInterface');
        $app = $this->prophesize('Sulu\Component\Websocket\WebsocketAppInterface');
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');

        $request->get('message')->willReturn(array('test' => 1));

        $appManager->getApp('test')->willReturn($app->reveal());

        $app->onMessage(Argument::type('Ratchet\ConnectionInterface'), array('test' => 1))->willReturn(
            array('test' => 2)
        );

        $controller = new FallbackController($appManager->reveal());
        $response = $controller->send('test', $request->reveal());

        $this->assertEquals(array(), json_decode($response->getContent(), true));
    }
}
