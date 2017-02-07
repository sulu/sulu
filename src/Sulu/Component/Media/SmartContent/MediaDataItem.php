<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Represents media item in media data provider.
 *
 * @ExclusionPolicy("all")
 */
class MediaDataItem implements ItemInterface
{
    /**
     * @var Media
     */
    private $entity;

    public function __construct(Media $entity)
    {
        $this->entity = $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getTitle()
    {
        return $this->entity->getTitle();
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getImage()
    {
        if (!array_key_exists('50x50', ($thumbnails = $this->entity->getThumbnails()))) {
            return;
        }

        return $thumbnails['50x50'];
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->entity;
    }
}
