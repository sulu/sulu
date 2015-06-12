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
interface ServiceManagerInterface
{
    const MEDIA_STATUS_ADD = 'add';
    const MEDIA_STATUS_UPDATE = 'update';
    const MEDIA_STATUS_DELETE = 'delete';

    /**
     * @param Media $media
     * @param string $status
     * @return boolean
     */
    public function addMedia(Media $media, $status = self::MEDIA_STATUS_ADD);

    /**
     * notifies all services
     */
    public function notify();

    /**
     * @param ServiceInterface $command
     * @param $alias
     */
    public function add(ServiceInterface $command, $alias);
}
