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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Sulu\Component\Websocket\Exception\MissingParameterException;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerContext;
use Sulu\Component\Websocket\MessageDispatcher\MessageHandlerInterface;
use Sulu\Component\Webspace\Analyzer\AdminRequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

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
     * {@inheritdoc}
     */
    protected $name = 'sulu_content.preview';

    public function __construct(
        PreviewInterface $preview,
        AdminRequestAnalyzer $requestAnalyzer,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->preview = $preview;
        $this->logger = $logger;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ConnectionInterface $conn, array $message, MessageHandlerContext $context)
    {
        // reconnect mysql
        $this->reconnect();

        try {
            $this->execute($conn, $context, $message);
        } catch (\Exception $e) {
            // send fail message
            $conn->send(
                json_encode(
                    array(
                        'command' => 'fail',
                        'code' => $e->getCode(),
                        'msg' => $e->getMessage(),
                        'parentMsg' => $message
                    )
                )
            );
        }
    }

    /**
     * Executes command
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     * @param array $msg
     * @throws ContextParametersNotFoundException
     * @throws MissingParameterException
     */
    private function execute(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        if (!array_key_exists('command', $msg)) {
            throw new MissingParameterException('command');
        }
        $command = $msg['command'];

        switch ($command) {
            case 'start':
                $this->start($conn, $context, $msg);
                break;
            case 'stop':
                $this->stop($conn, $context);
                break;
            case 'update':
                $this->update($conn, $context, $msg);
                break;
        }
    }

    /**
     * Reconnect to mysql
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
     * Start preview session
     * @param ConnectionInterface $conn
     * @param MessageHandlerContext $context
     * @param array $msg
     * @throws MissingParameterException
     */
    private function start(ConnectionInterface $conn, MessageHandlerContext $context, $msg)
    {
        // init session
        // content uuid
        if (!array_key_exists('content', $msg)) {
            throw new MissingParameterException('content');
        }
        $contentUuid = $msg['content'];
        $context->set('content', $contentUuid);

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

        // get user id
        $user = $context->getFirewallUser()->getId();

        // init message vars
        $template = array_key_exists('template', $msg) ? $msg['template'] : null;
        $data = array_key_exists('data', $msg) ? $msg['data'] : null;

        // start preview
        $this->preview->start($user, $contentUuid, $webspaceKey, $locale, $data, $template);

        // send ok message
        $conn->send(
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $contentUuid,
                    'msg' => 'OK'
                )
            )
        );
    }

    /**
     * Stop preview session
     * @param ConnectionInterface $from
     * @param MessageHandlerContext $context
     */
    private function stop(ConnectionInterface $from, MessageHandlerContext $context)
    {
        // get user id
        $user = $context->getFirewallUser()->getId();

        // get session vars
        $contentUuid = $context->get('content');
        $locale = $context->get('locale');
        $webspaceKey = $context->get('webspaceKey');

        // stop preview
        $this->preview->stop($user, $contentUuid, $webspaceKey, $locale);

        $context->clear();

        // send ok message
        $from->send(
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $contentUuid,
                    'msg' => 'OK'
                )
            )
        );
    }

    /**
     * Updates properties of current session content
     * @param ConnectionInterface $from
     * @param MessageHandlerContext $context
     * @param array $msg
     * @throws ContextParametersNotFoundException
     * @throws MissingParameterException
     */
    private function update(ConnectionInterface $from, MessageHandlerContext $context, $msg)
    {
        // check context parameters
        if (
            !$context->has('content') &&
            !$context->has('locale') &&
            !$context->has('webspaceKey')
        ) {
            throw new ContextParametersNotFoundException();
        }

        // get user id
        $user = $context->getFirewallUser()->getId();

        // get session vars
        $contentUuid = $context->get('content');
        $locale = $context->get('locale');
        $webspaceKey = $context->get('webspaceKey');

        // init msg vars
        if (!array_key_exists('data', $msg) && is_array($msg['data'])) {
            throw new MissingParameterException('data');
        }
        $changes = $msg['data'];

        $this->requestAnalyzer->setWebspaceKey($webspaceKey);
        $this->requestAnalyzer->setLocalizationCode($locale);

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

        // send ok message
        $from->send(
            json_encode(
                array(
                    'command' => 'update',
                    'content' => $contentUuid,
                    'data' => $this->preview->getChanges(
                        $user,
                        $contentUuid,
                        $webspaceKey,
                        $locale
                    )
                )
            )
        );
    }
}
