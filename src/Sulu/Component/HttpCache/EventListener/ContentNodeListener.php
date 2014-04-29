<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\EventListener;

use Sulu\Component\HttpCache\HttpCacheManagerInterface;
use Sulu\Component\Content\Event\ContentNodeEvent;

/**
 * This listener
 * @package Sulu\Component\Cache\EventListener
 */
class ContentNodeListener
{
    /**
     * @var \Sulu\Component\HttpCache\HttpCacheManagerInterface
     */
    protected $cacheManager;

    /**
     * string Current environment
     */
    protected $environment;

    /**
     * @param HttpCacheManagerInterface $cacheManager
     * @param $environment
     */
    public function __construct(HttpCacheManagerInterface $cacheManager, $environment)
    {
        $this->cacheManager = $cacheManager;
        $this->environment = $environment;
    }

    /**
     * @param ContentNodeEvent $event
     */
    public function onContentNodeSave(ContentNodeEvent $event)
    {
        $this->cacheManager->expire($event->getStructure(), $this->environment);
    }
}
