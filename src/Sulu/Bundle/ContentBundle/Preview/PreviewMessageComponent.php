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
use Ratchet\MessageComponentInterface;
use Sulu\Component\Websocket\AbstractWebsocketApp;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContextInterface;
use Sulu\Component\Webspace\Analyzer\AdminRequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class PreviewMessageComponent extends AbstractWebsocketApp implements MessageComponentInterface
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
        parent::__construct();

        $this->preview = $preview;
        $this->logger = $logger;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msgString)
    {
        // get context for message
        $context = $this->getContext($from);

        // reconnect mysql
        $this->reconnect();

        // decode message
        $msg = json_decode($msgString, true);

        try {
            $this->execute($from, $context, $msg);
        } catch (\Exception $e) {
            // send fail message
            $from->send(
                json_encode(
                    array(
                        'command' => 'fail',
                        'code' => $e->getCode(),
                        'msg' => $e->getMessage(),
                        'parentMsg' => $msg
                    )
                )
            );
        }
    }

    /**
     * Executes command
     * @param ConnectionInterface $from
     * @param ConnectionContextInterface $context
     * @param array $msg
     * @throws ContextParametersNotFoundException
     * @throws MissingParameterException
     */
    private function execute(ConnectionInterface $from, ConnectionContextInterface $context, $msg)
    {
        if (!array_key_exists('command', $msg)) {
            throw new MissingParameterException('command');
        }
        $command = $msg['command'];

        switch ($command) {
            case 'start':
                $this->start($from, $context, $msg);
                break;
            case 'stop':
                $this->stop($from, $context);
                break;
            case 'update':
                $this->update($from, $context, $msg);
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
     * @param ConnectionInterface $from
     * @param PreviewConnectionContext $context
     * @param array $msg
     * @throws MissingParameterException
     */
    private function start(ConnectionInterface $from, PreviewConnectionContext $context, $msg)
    {
        // init session
        // content uuid
        if (!array_key_exists('content', $msg)) {
            throw new MissingParameterException('content');
        }
        $contentUuid = $msg['content'];
        $context->setContentUuid($contentUuid);

        // locale
        if (!array_key_exists('locale', $msg)) {
            throw new MissingParameterException('locale');
        }
        $locale = $msg['locale'];
        $context->setLocale($locale);

        // webspace key
        if (!array_key_exists('webspaceKey', $msg)) {
            throw new MissingParameterException('webspaceKey');
        }
        $webspaceKey = $msg['webspaceKey'];
        $context->setWebspaceKey($webspaceKey);

        // get user id
        $user = $context->getAdminUser()->getId();

        // init message vars
        $template = array_key_exists('template', $msg) ? $msg['template'] : null;
        $data = array_key_exists('data', $msg) ? $msg['data'] : null;

        // start preview
        $this->preview->start($user, $contentUuid, $webspaceKey, $locale, $data, $template);

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
     * Stop preview session
     * @param ConnectionInterface $from
     * @param PreviewConnectionContext $context
     */
    private function stop(ConnectionInterface $from, PreviewConnectionContext $context)
    {
        // get user id
        $user = $context->getAdminUser()->getId();

        // get session vars
        $contentUuid = $context->getContentUuid();
        $locale = $context->getLocale();
        $webspaceKey = $context->getWebspaceKey();

        // stop preview
        $this->preview->stop($user, $contentUuid, $webspaceKey, $locale);

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
     * @param PreviewConnectionContext $context
     * @param array $msg
     * @throws ContextParametersNotFoundException
     * @throws MissingParameterException
     */
    private function update(ConnectionInterface $from, PreviewConnectionContext $context, $msg)
    {
        // check context parameters
        if (!$context->hasContextParameters()) {
            throw new ContextParametersNotFoundException();
        }

        // get user id
        $user = $context->getAdminUser()->getId();

        // get session vars
        $contentUuid = $context->getContentUuid();
        $locale = $context->getLocale();
        $webspaceKey = $context->getWebspaceKey();

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

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        parent::onClose($conn);

        $this->logger->error("An error has occurred: {$e->getMessage()}");
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext(ConnectionInterface $conn)
    {
        return new PreviewConnectionContext($conn);
    }
}
