<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Websocket\MessageDispatcher;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Sulu\Component\Websocket\AbstractWebsocketApp;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;
use Sulu\Component\Websocket\Exception\MissingParameterException;

/**
 * Class MessageDispatcherApp
 */
class MessageDispatcherApp extends AbstractWebsocketApp implements MessageComponentInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name;

    /**
     * @var MessageDispatcherInterface
     */
    protected $messageDispatcher;

    public function __construct($appName, MessageDispatcherInterface $messageDispatcher)
    {
        parent::__construct();

        $this->name = $appName;
        $this->messageDispatcher = $messageDispatcher;
    }

    /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $context = $this->getContext($from);
        $msg = json_decode($msg, true);

        try {
            $this->dispatch($context, $msg);
        } catch (\Exception $e) {
            // send fail message
            $from->send(
                json_encode(
                    array(
                        'type' => 'error',
                        'exception' => get_class($e),
                        'code' => $e->getCode(),
                        'msg' => $e->getMessage(),
                        'parentMsg' => $msg
                    )
                )
            );
        }
    }

    /**
     * Dispatches message to handler with dispatcher service
     * @param ConnectionContextInterface $context
     * @param array $msg
     * @throws MissingParameterException
     */
    private function dispatch(ConnectionContextInterface $context, array $msg)
    {
        if(!array_key_exists('event', $msg)){
            throw new MissingParameterException('event');
        }
        if(!array_key_exists('message', $msg)){
            throw new MissingParameterException('message');
        }

        $this->messageDispatcher->dispatch($msg['event'], $msg['message'], $context);
    }
}
