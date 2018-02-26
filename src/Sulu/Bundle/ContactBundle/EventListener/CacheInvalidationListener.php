<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Contact\Model\ContactInterface;
use Sulu\Component\HttpCache\HandlerInvalidateReferenceInterface;

/**
 * Invalidate references when account/contact are persisted.
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
        if ($object instanceof ContactInterface) {
            $this->invalidationHandler->invalidateReference('contact', $object->getId());
            $this->invalidateTags($object->getTags());
            $this->invalidateCategories($object->getCategories());
        } elseif ($object instanceof AccountInterface) {
            $this->invalidationHandler->invalidateReference('account', $object->getId());
            $this->invalidateTags($object->getTags());
            $this->invalidateCategories($object->getCategories());
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
