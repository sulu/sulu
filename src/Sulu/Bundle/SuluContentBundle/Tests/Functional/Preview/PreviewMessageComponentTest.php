<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Preview;


use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Sulu\Bundle\ContentBundle\Preview\PreviewMessageComponent;
use Sulu\Component\Testing\WebsocketClient;
use Symfony\Component\Security\Core\SecurityContextInterface;

class PreviewMessageComponentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsocketClient
     */
    private $client;

    protected function setUp()
    {
        $component = $this->prepareComponent();

        $port = 12345;
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $component
                )
            ),
            $port
        );
        $server->run();

        $this->client = new WebsocketClient();
        $this->client->connect('localhost', $port, '');
    }

    private function prepareComponent()
    {
        $securityContext = $this->prepareSecurityContext();
        $component = new PreviewMessageComponent($securityContext);

        return $component;
    }

    /**
     * @return SecurityContextInterface
     */
    private function prepareSecurityContext()
    {
        $context = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        return $context;
    }
}
