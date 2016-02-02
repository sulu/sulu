<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\Tests\Unit\Controller;

use Prophecy\Argument;
use Sulu\Bundle\WebsocketBundle\Controller\FallbackController;
use Symfony\Component\HttpFoundation\ParameterBag;

class FallbackControllerTest extends \PHPUnit_Framework_TestCase
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

        $request->get('message')->willReturn(['test' => 1]);
        $request->reveal()->cookies = new ParameterBag(['PHPSESSID' => '123-123-123']);

        $appManager->getApp('test')->willReturn($app->reveal());

        $app->onMessage(Argument::type('Ratchet\ConnectionInterface'), ['test' => 1])->will(
            function ($args) {
                $return = ['test' => $args[1]['test'] + 1];
                $args[0]->send(json_encode($return));
            }
        );

        $controller = new FallbackController($appManager->reveal());
        $response = $controller->send('test', $request->reveal());

        $this->assertEquals(['test' => 2], json_decode($response->getContent(), true));
    }
}
