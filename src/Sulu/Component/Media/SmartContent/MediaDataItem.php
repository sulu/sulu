<?php

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
        return $this->entity->getName();
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getImage()
    {
        return $this->entity->getThumbnails();
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->entity;
    }
}
