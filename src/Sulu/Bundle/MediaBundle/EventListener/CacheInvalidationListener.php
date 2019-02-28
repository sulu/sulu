<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\HttpCache\HandlerInvalidateReferenceInterface;

/**
 * Invalidate references when media is persisted.
 */
class CacheInvalidationListener
{
    /**
     * @var HandlerInvalidateReferenceInterface
     */
    private $invalidationHandler;

    public function __construct(HandlerInvalidateReferenceInterface $invalidationHandler)
    {
        $this->invalidationHandler = $invalidationHandler;
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $this->invalidateEntity($eventArgs->getObject());
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $this->invalidateEntity($eventArgs->getObject());
    }

    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $this->invalidateEntity($eventArgs->getObject());
    }

    private function invalidateEntity($object)
    {
        if ($object instanceof MediaInterface) {
            $this->invalidationHandler->invalidateReference('media', $object->getId());
        } elseif ($object instanceof File) {
            $this->invalidateEntity($object->getMedia());
        } elseif ($object instanceof FileVersion) {
            $this->invalidateEntity($object->getFile());
            $this->invalidateTags($object->getTags());
            $this->invalidateCategories($object->getCategories());
        } elseif ($object instanceof FileVersionMeta) {
            $this->invalidateEntity($object->getFileVersion());
        }
    }

    /**
     * @param TagInterface[] $tags
     */
    private function invalidateTags($tags)
    {
        foreach ($tags as $tag) {
            $this->invalidationHandler->invalidateReference('tag', $tag->getId());
        }
    }

    /**
     * @param CategoryInterface[] $categories
     */
    private function invalidateCategories($categories)
    {
        foreach ($categories as $category) {
            $this->invalidationHandler->invalidateReference('category', $category->getId());
        }
    }
}
