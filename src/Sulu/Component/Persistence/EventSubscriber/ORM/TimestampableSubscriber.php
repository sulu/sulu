<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Sulu\Component\Persistence\Model\TimestampableInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Manage the timestamp fields on models implementing the
 * TimestampableInterface.
 */
class TimestampableSubscriber
{
    public const CREATED_FIELD = 'created';

    public const CHANGED_FIELD = 'changed';

    public function __construct(private ClockInterface $clock)
    {
    }

    /**
     * Load the class data, mapping the created and changed fields
     * to datetime fields.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();

        // @phpstan-ignore notIdentical.alwaysTrue
        if (null !== $reflection && $reflection->implementsInterface(TimestampableInterface::class)) {
            if (!$metadata->hasField(self::CREATED_FIELD)) {
                $metadata->mapField([
                    'fieldName' => self::CREATED_FIELD,
                    'type' => 'datetime_immutable',
                    'nullable' => false,
                ]);
            }

            if (!$metadata->hasField(self::CHANGED_FIELD)) {
                $metadata->mapField([
                    'fieldName' => self::CHANGED_FIELD,
                    'type' => 'datetime_immutable',
                    'nullable' => false,
                ]);
            }
        }
    }

    /**
     * Set the timestamps before update.
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->handleTimestamp($event);
    }

    /**
     * Set the timestamps before creation.
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $this->handleTimestamp($event);
    }

    /**
     * Set the timestamps. If created is NULL then set it. Always
     * set the changed field.
     *
     * @return void
     */
    private function handleTimestamp(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $meta = $event->getObjectManager()->getClassMetadata(\get_class($entity));

        $created = $meta->getFieldValue($entity, self::CREATED_FIELD);

        $now = new \DateTimeImmutable($this->clock->now()->format('Y-m-d H:i:s'), $this->clock->now()->getTimezone());
        if (null === $created) {
            $meta->setFieldValue($entity, self::CREATED_FIELD, $now);
        }

        $meta->setFieldValue($entity, self::CHANGED_FIELD, $now);
    }
}
