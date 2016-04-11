<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Sulu\Component\Persistence\Model\TimestampableInterface;

/**
 * Manage the timestamp fields on models implementing the
 * TimestampableInterface.
 */
class TimestampableSubscriber implements EventSubscriber
{
    const CREATED_FIELD = 'created';
    const CHANGED_FIELD = 'changed';

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
            Events::preUpdate,
            Events::prePersist,
        ];
    }

    /**
     * Load the class data, mapping the created and changed fields
     * to datetime fields.
     *
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();

        if ($reflection !== null && $reflection->implementsInterface('Sulu\Component\Persistence\Model\TimestampableInterface')) {
            if (!$metadata->hasField(self::CREATED_FIELD)) {
                $metadata->mapField([
                    'fieldName' => self::CREATED_FIELD,
                    'type' => 'datetime',
                    'notnull' => true,
                ]);
            }

            if (!$metadata->hasField(self::CHANGED_FIELD)) {
                $metadata->mapField([
                    'fieldName' => self::CHANGED_FIELD,
                    'type' => 'datetime',
                    'notnull' => true,
                ]);
            }
        }
    }

    /**
     * Set the timestamps before update.
     *
     * @param LifecycleEventArgs $event
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->handleTimestamp($event);
    }

    /**
     * Set the timestamps before creation.
     *
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $this->handleTimestamp($event);
    }

    /**
     * Set the timestamps. If created is NULL then set it. Always
     * set the changed field.
     *
     * @param LifecycleEventArgs $event
     */
    private function handleTimestamp(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $meta = $event->getObjectManager()->getClassMetadata(get_class($entity));

        $created = $meta->getFieldValue($entity, self::CREATED_FIELD);
        if (null === $created) {
            $meta->setFieldValue($entity, self::CREATED_FIELD, new \DateTime());
        }

        $meta->setFieldValue($entity, self::CHANGED_FIELD, new \DateTime());
    }
}
