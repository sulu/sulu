<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Service;

use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Event\ApiMediaEventInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class EventListener
 * Handles all media events
 */
class EventListener implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ServiceManagerInterface
     */
    protected $serviceManager;

    /**
     * @param ServiceManagerInterface $serviceManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServiceManagerInterface $serviceManager,
        $logger = null
    ) {
        $this->serviceManager = $serviceManager;
        $this->logger = $logger ? : new NullLogger();
    }

    /**
     * @param ApiMediaEventInterface $event
     */
    public function onMediaAdd(ApiMediaEventInterface $event)
    {
        $this->addMediaToManager($event->getMedia(), ServiceManagerInterface::MEDIA_STATUS_ADD);
    }

    /**
     * @param ApiMediaEventInterface $event
     */
    public function onMediaUpdate(ApiMediaEventInterface $event)
    {
        $this->addMediaToManager($event->getMedia(), ServiceManagerInterface::MEDIA_STATUS_UPDATE);
    }

    /**
     * @param ApiMediaEventInterface $event
     */
    public function onMediaDelete(ApiMediaEventInterface $event)
    {
        $this->addMediaToManager($event->getMedia(), ServiceManagerInterface::MEDIA_STATUS_DELETE);
    }

    /**
     * will notify all servcies
     */
    public function onTerminate()
    {
        $this->serviceManager->notify();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $listeners = array(
            MediaManagerInterface::EVENT_POST_MEDIA_ADD => 'onMediaAdd',
            MediaManagerInterface::EVENT_POST_MEDIA_UPDATE => 'onMediaUpdate',
            MediaManagerInterface::EVENT_POST_MEDIA_DELETE => 'onMediaDelete',
            KernelEvents::TERMINATE => 'onTerminate',
        );

        return $listeners;
    }

    /**
     * @param $media
     * @param $status
     * @return boolean
     */
    private function addMediaToManager($media, $status)
    {
        return $this->serviceManager->addMedia($media, $status);
    }
}
