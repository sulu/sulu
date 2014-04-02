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

use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class PreviewMessageComponent implements MessageComponentInterface
{
    /**
     * @var array
     */
    protected $content;

    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(PreviewInterface $preview, LoggerInterface $logger)
    {
        $this->content = array();

        $this->preview = $preview;
        $this->logger = $logger;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->logger->debug("Connection {$conn->resourceId} has connected");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->logger->debug("Connection {$from->resourceId} has send a message: {$msg}");
        $msg = json_decode($msg, true);

        if (isset($msg['command']) &&
            isset($msg['user']) &&
            isset($msg['params']) &&
            isset($msg['content']) &&
            isset($msg['type']) &&
            in_array(
                strtolower($msg['type']),
                array('form', 'preview')
            )
        ) {
            $user = $msg['user'];
            switch ($msg['command']) {
                case 'start':
                    $this->start($from, $msg, $user);
                    break;
                case 'update':
                    $this->update($from, $msg, $user);
                    break;
                case 'close':
                    $this->close($from, $msg, $user);
                    break;
            }
        }
    }

    private function start(ConnectionInterface $from, $msg, $user)
    {
        $content = $msg['content'];
        $type = strtolower($msg['type']);

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
        $content = $msg['content'];
        $type = strtolower($msg['type']);
        $params = $msg['params'];
        $id = $user . '-' . $content;

        // if params correct
        // FIXME implement error handling
        if ($type == 'form' && isset($params['changes']) && isset($params['template'])) {

            foreach ($params['changes'] as $property => $data) {
                // update property
                $this->preview->update(
                    $user,
                    $content,
                    $msg['webspaceKey'],
                    $msg['languageCode'],
                    $property,
                    $data,
                    $params['template']
                );
            }

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
            if (isset($this->content[$id]) &&
                isset($this->content[$id]['preview'])
            ) {
                // get changes
                $changes = $this->preview->getChanges($user, $content);

                if (sizeof($changes) > 0) {
                    // get preview client
                    /** @var ConnectionInterface $previewClient */
                    $previewClient = $this->content[$id]['preview'];
                    $changes = json_encode(
                        array(
                            'command' => 'changes',
                            'content' => $content,
                            'type' => 'preview',
                            'params' => array(
                                'changes' => $changes
                            )
                        )
                    );
                    $this->logger->debug("Changes send {$changes}");

                    // send changes command
                    $previewClient->send($changes);
                }
            }
        }
    }

    private function close(ConnectionInterface $from, $msg, $user)
    {
        $content = $msg['content'];
        $type = strtolower($msg['type']);
        $otherType = ($type == 'form' ? 'preview' : 'form');
        $id = $user . '-' . $content;

        // stop preview
        $this->preview->stop($user, $content);

        // close connection
        $from->close();

        // close other part
        if (isset($this->content[$id][$otherType])) {
            /** @var ConnectionInterface $other */
            $other = $this->content[$id][$otherType];
            $other->close();
        }

        // cleanUp cache
        unset($this->content[$id]);
    }

    public function onClose(ConnectionInterface $conn)
    {
        /** @var ConnectionInterface $other */
        $other = null;
        foreach ($this->content as $data) {
            if (isset($data['form']) && ($data['form'] == $conn || isset($data['preview']))) {
                $other = $data['preview'];
            } elseif (isset($data['preview']) && ($data['preview'] == $conn && isset($data['form']))) {
                $other = $data['form'];
            }
        }

        if ($other != null) {
            $other->close();
        }

        $this->logger->debug("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error("An error has occurred: {$e->getMessage()}");

        $conn->close();
    }
}
