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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Api\Media;

/**
 * Class ServiceManager
 * The Service Manager notify all registered services
 */
class ServiceManager implements ServiceManagerInterface
{
    /**
     * @var array
     */
    protected $mediaRegister = array();

    /**
     * @var ServiceInterface[]
     */
    protected $services = array();

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * creates default media register structure
     * @param LoggerInterface $logger
     */
    public function __construct(
        $logger = null
    ) {
        $this->logger = $logger ? : new NullLogger();
        $this->mediaRegister[self::MEDIA_STATUS_ADD] = array();
        $this->mediaRegister[self::MEDIA_STATUS_UPDATE] = array();
        $this->mediaRegister[self::MEDIA_STATUS_DELETE] = array();
    }

    /**
     * {@inheritdoc}
     */
    public function addMedia(
        Media $media,
        $status = self::MEDIA_STATUS_ADD
    ) {
        $this->mediaRegister[$status][$media->getId()] = $media;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function notify()
    {
        $this->logger->warning('================== NO ======================' . count($this->services));

        foreach ($this->services as $service) {
            /** @var ServiceInterface $service */
            foreach ($this->mediaRegister as $action => $mediaList) {
                if (count($mediaList)) {
                    $mediaList = array_values($mediaList);
                    switch ($action) {
                        case self::MEDIA_STATUS_ADD:
                            $service->add($mediaList);
                            break;
                        case self::MEDIA_STATUS_UPDATE:
                            $service->update($mediaList);
                            break;
                        case self::MEDIA_STATUS_DELETE:
                            $service->delete($mediaList);
                            break;
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(ServiceInterface $command, $alias)
    {
        $this->services[$alias] = $command;
    }
}
