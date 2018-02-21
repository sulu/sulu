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
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
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
        $object = $eventArgs->getObject();
        if ($object instanceof ContactInterface) {
            $this->invalidationHandler->invalidateReference('contact', $object->getId());
        } elseif ($object instanceof AccountInterface) {
            $this->invalidationHandler->invalidateReference('account', $object->getId());
        }
    }
}
