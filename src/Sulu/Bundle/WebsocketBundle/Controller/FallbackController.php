<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\Controller;

use Sulu\Bundle\WebsocketBundle\Connection\DummyConnection;
use Sulu\Component\Websocket\AppManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides fallback interface for websocket apps
 */
class FallbackController
{
    /**
     * @var AppManagerInterface
     */
    private $appManager;

    function __construct(AppManagerInterface $appManager)
    {
        $this->appManager = $appManager;
    }

    /**
     * Redirect message to app and returns value as json response
     * @param string $appName
     * @param Request $request
     * @return JsonResponse
     */
    public function send($appName, Request $request)
    {
        $sessionName = ini_get('session.name');

        $message = $request->get('message');
        $id = $request->cookies->get($sessionName);

        $app = $this->appManager->getApp($appName);
        $connection = new DummyConnection($id);

        $app->onMessage($connection, $message);

        return new Response($connection->getData(), 200, array('Content-Type' => 'application/json'));
    }
}
