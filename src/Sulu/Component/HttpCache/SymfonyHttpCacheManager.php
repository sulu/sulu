<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache;

use Psr\Log\NullLogger;
use Sulu\Component\Content\StructureInterface;

/**
 * Sulu cache manager
 */
class SymfonyHttpCacheManager implements HttpCacheManagerInterface
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
    public function expire(StructureInterface $structure)
    {
        $this->logger->debug($structure->getPropertyValue('url'));
    }
}
