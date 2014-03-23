<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

use PHPCR\NodeInterface;
use Psr\Log\NullLogger;

/**
 * Sulu cache manager
 */
class CacheManager implements CacheManagerInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param $logger
     */
    public function __construct($logger)
    {
        $this->logger = $logger ? : new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function expire(NodeInterface $node)
    {
        $this->logger->debug($node->getPath());
    }
}
