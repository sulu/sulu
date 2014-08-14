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
        $templateKey = $msg['templateKey'];
        $languageCode = $msg['languageCode'];
        $webspaceKey = $msg['webspaceKey'];

        // if preview is started
        if (!$this->preview->started($user, $content, $templateKey, $languageCode)) {
            // TODO workspace, language
            $this->preview->start($user, $content, $webspaceKey, $templateKey, $languageCode);
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
        if ($type == 'form' && isset($params['changes'])) {

            $templateKey = $msg['templateKey'];
            $languageCode = $msg['languageCode'];
            $webspaceKey = $msg['webspaceKey'];

            foreach ($params['changes'] as $property => $data) {
                // update property
                $this->preview->update(
                    $user,
                    $content,
                    $webspaceKey,
                    $templateKey,
                    $languageCode,
                    $property,
                    $data
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
                $changes = $this->preview->getChanges(
                    $user,
                    $content,
                    $templateKey,
                    $languageCode
                );

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
        $this->logger->debug("Connection {$from->resourceId} has called close");
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->logger->debug("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error("An error has occurred: {$e->getMessage()}");
    }
}
