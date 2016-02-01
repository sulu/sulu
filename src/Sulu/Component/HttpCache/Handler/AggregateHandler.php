<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Sulu\Component\HttpCache\HandlerInterface;
use Sulu\Component\HttpCache\HandlerInvalidatePathInterface;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * This cache handler aggregates and delegates to other
 * handlers. This is the default sulu cache handler.
 */
class AggregateHandler implements
    HandlerFlushInterface,
    HandlerUpdateResponseInterface,
    HandlerInvalidateStructureInterface,
    HandlerInvalidatePathInterface
{
    /**
     * @var HandlerInterface[]
     */
    private $handlers;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param HandlerInterface[] $handlers
     */
    public function __construct($handlers = [], LoggerInterface $logger = null)
    {
        $this->handlers = $handlers;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateStructure(StructureInterface $structure)
    {
        foreach ($this->handlers as $handler) {
            if (!$handler instanceof HandlerInvalidateStructureInterface) {
                continue;
            }

            $this->logger->debug(sprintf(
                '[CACHE] INVALIDATING [%s]: %s (%s)',
                get_class($handler),
                get_class($structure),
                $structure->getUuid()
            ));
            $handler->invalidateStructure($structure);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function invalidatePath($path, array $headers = [])
    {
        foreach ($this->handlers as $handler) {
            if (!$handler instanceof HandlerInvalidatePathInterface) {
                continue;
            }

            $this->logger->debug(sprintf(
                '[CACHE] INVALIDATING [%s]: %s',
                get_class($handler),
                $path
            ));
            $handler->invalidatePath($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateResponse(Response $response, StructureInterface $structure)
    {
        foreach ($this->handlers as $handler) {
            if (!$handler instanceof HandlerUpdateResponseInterface) {
                continue;
            }

            $this->logger->debug(sprintf(
                '[CACHE] UPDATING RESPONSE [%s]: %s (%s)',
                get_class($handler),
                get_class($structure),
                $structure->getUuid()
            ));
            $handler->updateResponse($response, $structure);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        foreach ($this->handlers as $handler) {
            if (!$handler instanceof HandlerFlushInterface) {
                continue;
            }

            try {
                $res = $handler->flush();
                if ($res) {
                    $this->logger->debug(sprintf(
                        '[CACHE] FLUSH OK [%s]',
                        get_class($handler)
                    ));
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    '[CACHE] FLUSH ERROR [%s] %s',
                    get_class($e),
                    $e->getMessage()
                ));
            }
        }
    }
}
