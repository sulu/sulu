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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Sulu cache manager
 */
class SymfonyHttpCacheManager implements HttpCacheManagerInterface
{

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param $logger
     */
    public function __construct(WebspaceManagerInterface $webspaceManager, $logger = null)
    {
        $this->webspaceManager = $webspaceManager;
        $this->logger = $logger ? : new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function expire(StructureInterface $structure)
    {
        $urls = $this->webspaceManager->findUrlsByResourceLocator(
            $structure->getPropertyValue('url'),
            'dev', // TODO get enviorment
            $structure->getLanguageCode(),
            $structure->getWebspaceKey()
        );

        if (count($urls) > 0) {
            foreach ($urls as $url) {
                $this->purge($url);
                if (($tmpUrl = rtrim($url, '/')) !== $url) {
                    $this->purge($tmpUrl);
                }
            }
        }
    }

    private function purge($url)
    {
        $this->logger->debug('PURGE: ' . $url);


    }
}
