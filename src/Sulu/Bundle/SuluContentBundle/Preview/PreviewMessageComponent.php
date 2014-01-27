<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class PreviewMessageComponent implements MessageComponentInterface
{
    /**
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * @var array
     */
    protected $content;

    /**
     * @var SecurityContextInterface
     */
    private $context;

    /**
     * @var PreviewInterface
     */
    private $preview;

    public function __construct(SecurityContextInterface $context, PreviewInterface $preview)
    {
        $this->clients = new \SplObjectStorage;
        $this->content = array();

        $this->context = $context;
        $this->preview = $preview;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "Connection {$conn->resourceId} has connected\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Connection {$from->resourceId} has send a message: {$msg}\n";
        $msg = json_decode($msg);
        $user = $this->context->getToken()->getUser()->getId();

        if (
            isset($msg->command) &&
            isset($msg->params) &&
            isset($msg->content) &&
            isset($msg->type) &&
            in_array(
                strtolower($msg->type),
                array('form', 'preview')
            )
        ) {
            switch ($msg->command) {
                case 'start':
                    $this->start($from, $msg, $user);
                    break;
                case 'update':
                    $this->update($from, $msg, $user);
                    break;
            }
        }
    }

    private function start(ConnectionInterface $from, $msg, $user)
    {
        $content = $msg->content;
        $type = strtolower($msg->type);

        // if preview is started
        if (!$this->preview->started($user, $content)) {
            // TODO workspace, language
            $this->preview->start($user, $content, '', '');
        }

        // generate unique cache id
        $id = $user . '-' . $content;
        if (!array_key_exists($id, $this->content)) {
            $this->content[$id] = array(
                'content' => $content,
                'user' => $user
            );
        }

        // save client for type
        $this->content[$id][$type] = $from;

        // inform other part
        $otherType = ($type == 'form' ? 'preview' : 'form');
        $other = false;
        if (isset($this->content[$id][$otherType])) {
            $other = true;
            $this->content[$id][$otherType]->send(
                json_encode(
                    array(
                        'command' => 'start',
                        'content' => $content,
                        'type' => $type,
                        'params' => array('msg' => 'OK', 'other' => true)
                    )
                )
            );
        }

        // send ok message
        $from->send(
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $content,
                    'type' => $type,
                    'params' => array('msg' => 'OK', 'other' => $other)
                )
            )
        );
    }

    private function update(ConnectionInterface $from, $msg, $user)
    {
        $content = $msg->content;
        $type = strtolower($msg->type);
        $params = $msg->params;
        $id = $user . '-' . $content;

        if (
            $type == 'form' &&
            isset($params->property) &&
            isset($params->data)
        ) {
            // update property
            $this->preview->update($user, $content, $params->property, $params->data);
            // get changes
            $changes = $this->preview->getChanges($user, $content);

            // send ok message
            $from->send(
                json_encode(
                    array(
                        'command' => 'update',
                        'content' => $content,
                        'type' => 'form',
                        'params' => array('msg' => 'OK')
                    )
                )
            );

            // if there are some changes
            if (
                sizeof($changes) > 0 &&
                isset($this->content[$id]) &&
                isset($this->content[$id]['preview'])
            ) {
                // get preview client
                /** @var ConnectionInterface $previewClient */
                $previewClient = $this->content[$id]['preview'];
                // send changes command
                $previewClient->send(
                    json_encode(
                        array(
                            'command' => 'changes',
                            'content' => $content,
                            'type' => 'preview',
                            'params' => $changes
                        )
                    )
                );
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
