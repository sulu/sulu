<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GenericSerializationVisitor;
use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Hash\HasherInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Rest\ApiWrapper;

/**
 * Adds the hash of an object to its serialization, if it is auditable.
 */
class HashSerializeEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var HasherInterface
     */
    private $hasher;

    public function __construct(HasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'],
        ];
    }

    /**
     * Adds the hash of the given object to its serialization.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();

        // FIXME This can be removed, as soon as we've got rid of all ApiEntities.
        if ($object instanceof ApiWrapper) {
            $object = $object->getEntity();
        }

        if (!$object instanceof AuditableInterface && !$object instanceof LocalizedAuditableBehavior) {
            return;
        }

        if (!$event->getVisitor() instanceof GenericSerializationVisitor) {
            return;
        }

        $event->getVisitor()->addData('_hash', $this->hasher->hash($object));
    }
}
