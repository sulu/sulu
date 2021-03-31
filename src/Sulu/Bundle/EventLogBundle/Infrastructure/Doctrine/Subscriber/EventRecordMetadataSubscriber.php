<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Infrastructure\Doctrine\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecordInterface;

/**
 * @internal
 */
class EventRecordMetadataSubscriber implements EventSubscriber
{
    const EVENT_PAYLOAD_FIELD = 'eventPayload';

    /**
     * @var bool
     */
    private $shouldPersistPayload;

    public function __construct(bool $shouldPersistPayload)
    {
        $this->shouldPersistPayload = $shouldPersistPayload;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();

        if ($this->shouldPersistPayload
            && $reflection->implementsInterface(EventRecordInterface::class)
            && !$metadata->hasField(self::EVENT_PAYLOAD_FIELD)
        ) {
            $metadata->mapField([
                'fieldName' => self::EVENT_PAYLOAD_FIELD,
                'columnName' => self::EVENT_PAYLOAD_FIELD,
                'type' => 'json',
                'nullable' => true,
            ]);
        }
    }
}
