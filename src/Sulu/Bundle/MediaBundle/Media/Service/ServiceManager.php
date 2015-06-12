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
    protected $services;

    /**
     * creates default media register structure
     */
    public function __construct() {
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
        foreach ($this->services as $service) {
            /** @var ServiceInterface $service */
            foreach ($this->mediaRegister as $action => $mediaList) {
                foreach ($mediaList as $media) {
                    switch ($action) {
                        case self::MEDIA_STATUS_ADD:
                            $service->add($media);
                            break;
                        case self::MEDIA_STATUS_UPDATE:
                            $service->update($media);
                            break;
                        case self::MEDIA_STATUS_DELETE:
                            $service->delete($media);
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
