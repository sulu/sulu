<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Websocket\Exception\MissingParameterException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles messages for preview.
 *
 * @example {cmd: start, locale: de, webspaceKey: sulu_io, user: 1, content: 123-123-123}
 *
 * The example starts the preview and init the session in the cache.
 */
class PreviewMessageHandler implements MessageHandlerInterface
{
    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * {@inheritdoc}
     */
    protected $name = 'sulu_content.preview';

    public function __construct(
        PreviewInterface $preview,
        RequestAnalyzer $requestAnalyzer,
        Registry $registry,
        ContentMapperInterface $contentMapper,
        LoggerInterface $logger
    ) {
        $this->preview = $preview;
        $this->logger = $logger;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->registry = $registry;
        $this->contentMapper = $contentMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ConnectionInterface $conn, array $message, MessageHandlerContext $context)
    {
        // reconnect mysql
        $this->reconnect();

        try {
            return $this->execute($conn, $context, $message);
        } catch (PreviewException $ex) {
            throw new MessageHandlerException($ex);
        }
    }

    /**
     * Executes command.
     *
     * @param ConnectionInterface   $conn
     * @param MessageHandlerContext $context
     * @param array                 $msg
     *
     * @return mixed|null
     *
     * @throws PreviewNotStartedException
     * @throws MissingParameterException
     */
    private function execute(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        if (!array_key_exists('command', $msg)) {
            throw new MissingParameterException('command');
        }
        $command = $msg['command'];
        $result = null;

        switch ($command) {
            case 'start':
                $result = $this->start($conn, $context, $msg);
                break;
            case 'stop':
                $result = $this->stop($conn, $context);
                break;
            case 'update':
                $result = $this->update($conn, $context, $msg);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Command "%s" not known', $command));
                break;
        }

        return $result;
    }

    /**
     * Reconnect to mysql.
     */
    private function reconnect()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->registry->getManager();
        /** @var Connection $connection */
        $connection = $entityManager->getConnection();

        try {
            $connection->executeQuery('SELECT 1;');
        } catch (DBALException $exc) {
            $this->logger->warning('Mysql reconnect');
            $connection->close();
            $connection->connect();
        }
    }

    /**
     * Start preview session.
     *
     * @param ConnectionInterface   $conn
     * @param MessageHandlerContext $context
     * @param array                 $msg
     *
     * @return array
     *
     * @throws MissingParameterException
     */
    private function start(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        // locale
        if (!array_key_exists('locale', $msg)) {
            throw new MissingParameterException('locale');
        }
        $locale = $msg['locale'];
        $context->set('locale', $locale);

        // webspace key
        if (!array_key_exists('webspaceKey', $msg)) {
            throw new MissingParameterException('webspaceKey');
        }
        $webspaceKey = $msg['webspaceKey'];
        $context->set('webspaceKey', $webspaceKey);

        // user id
        if (!array_key_exists('user', $msg)) {
            throw new MissingParameterException('user');
        }
        $user = $msg['user'];
        $context->set('user', $user);

        // content uuid
        if (!array_key_exists('content', $msg)) {
            throw new MissingParameterException('content');
        }
        $contentUuid = $msg['content'];
        $context->set('content', $contentUuid);

        // init message vars
        $template = array_key_exists('template', $msg) ? $msg['template'] : null;
        $data = array_key_exists('data', $msg) ? $msg['data'] : null;

        // start preview
        $this->preview->start($user, $contentUuid, $webspaceKey, $locale, $data, $template);

        return [
            'command' => 'start',
            'content' => $contentUuid,
            'msg' => 'OK',
        ];
    }

    /**
     * Stop preview session.
     *
     * @param ConnectionInterface   $from
     * @param MessageHandlerContext $context
     *
     * @return array
     *
     * @throws PreviewNotStartedException
     */
    private function stop(ConnectionInterface $from, MessageHandlerContext $context)
    {
        // check context parameters
        if (!$context->has('user')) {
            throw new PreviewNotStartedException();
        }

        // get user id
        $user = $context->get('user');

        // get session vars
        $contentUuid = $context->get('content');
        $locale = $context->get('locale');
        $webspaceKey = $context->get('webspaceKey');

        // stop preview
        $this->preview->stop($user, $contentUuid, $webspaceKey, $locale);

        $context->clear();

        return [
            'command' => 'stop',
            'content' => $contentUuid,
            'msg' => 'OK',
        ];
    }

    /**
     * Updates properties of current session content.
     *
     * @param ConnectionInterface   $from
     * @param MessageHandlerContext $context
     * @param array                 $msg
     *
     * @return array
     *
     * @throws PreviewNotStartedException
     * @throws MissingParameterException
     */
    private function update(ConnectionInterface $from, MessageHandlerContext $context, $msg)
    {
        // check context parameters
        if (
            !$context->has('content') &&
            !$context->has('locale') &&
            !$context->has('webspaceKey') &&
            !$context->has('user')
        ) {
            throw new PreviewNotStartedException();
        }

        // get user id
        $user = $context->get('user');

        // get session vars
        $contentUuid = $context->get('content');
        $locale = $context->get('locale');
        $webspaceKey = $context->get('webspaceKey');

        // init msg vars
        if (!array_key_exists('data', $msg) && is_array($msg['data'])) {
            throw new MissingParameterException('data');
        }
        $changes = $msg['data'];

        $request = new Request(['webspace' => $webspaceKey, 'locale' => $locale]);
        $this->requestAnalyzer->analyze($request);

        foreach ($changes as $property => $data) {
            // update property
            $this->preview->updateProperty(
                $user,
                $contentUuid,
                $webspaceKey,
                $locale,
                $property,
                $data
            );
        }

        return [
            'command' => 'update',
            'content' => $contentUuid,
            'data' => $this->preview->getChanges(
                $user,
                $contentUuid,
                $webspaceKey,
                $locale
            ),
        ];
    }

    /**
     * Connection lost.
     *
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     */
    public function onClose(ConnectionInterface $conn, MessageHandlerContext $context)
    {
        // get session vars
        $user = $context->get('user');
        $locale = $context->get('locale');
        $contentUuid = $context->get('content');
        $webspaceKey = $context->get('webspaceKey');

        $this->preview->stop($user, $contentUuid, $webspaceKey, $locale);
    }
}
