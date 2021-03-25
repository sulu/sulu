<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Tests\Unit\Infrastructure\Doctrine\Subscriber;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ReflectionClass;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecordInterface;
use Sulu\Bundle\EventLogBundle\Infrastructure\Doctrine\Subscriber\EventRecordMetadataSubscriber;

class EventRecordMetadataSubscriberTest extends TestCase
{
    public function testLoadClassMetadataPersistPayloadEnabled()
    {
        $subscriber = $this->createEventRecordMetadataSubscriber(true);

        $reflectionClass = $this->prophesize(ReflectionClass::class);
        $reflectionClass->implementsInterface(EventRecordInterface::class)->willReturn(true);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getReflectionClass()->willReturn($reflectionClass->reveal());
        $classMetadata->hasField('eventPayload')->willReturn(false);

        $loadClassMetadataEvent = $this->prophesize(LoadClassMetadataEventArgs::class);
        $loadClassMetadataEvent->getClassMetadata()->willReturn($classMetadata->reveal());

        $classMetadata->mapField([
            'fieldName' => 'eventPayload',
            'columnName' => 'eventPayload',
            'type' => 'json',
            'nullable' => true,
        ])->shouldBeCalled();

        $subscriber->loadClassMetadata($loadClassMetadataEvent->reveal());
    }

    public function testLoadClassMetadataPersistPayloadEnabledUnrelatedClass()
    {
        $subscriber = $this->createEventRecordMetadataSubscriber(true);

        $reflectionClass = $this->prophesize(ReflectionClass::class);
        $reflectionClass->implementsInterface(EventRecordInterface::class)->willReturn(false);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getReflectionClass()->willReturn($reflectionClass->reveal());
        $classMetadata->hasField('eventPayload')->willReturn(false);

        $loadClassMetadataEvent = $this->prophesize(LoadClassMetadataEventArgs::class);
        $loadClassMetadataEvent->getClassMetadata()->willReturn($classMetadata->reveal());

        $classMetadata->mapField(Argument::cetera())->shouldNotBeCalled();

        $subscriber->loadClassMetadata($loadClassMetadataEvent->reveal());
    }

    public function testLoadClassMetadataPersistPayloadDisabled()
    {
        $subscriber = $this->createEventRecordMetadataSubscriber(false);

        $reflectionClass = $this->prophesize(ReflectionClass::class);
        $reflectionClass->implementsInterface(EventRecordInterface::class)->willReturn(true);

        $classMetadata = $this->prophesize(ClassMetadataInfo::class);
        $classMetadata->getReflectionClass()->willReturn($reflectionClass->reveal());
        $classMetadata->hasField('eventPayload')->willReturn(false);

        $loadClassMetadataEvent = $this->prophesize(LoadClassMetadataEventArgs::class);
        $loadClassMetadataEvent->getClassMetadata()->willReturn($classMetadata->reveal());

        $classMetadata->mapField(Argument::cetera())->shouldNotBeCalled();

        $subscriber->loadClassMetadata($loadClassMetadataEvent->reveal());
    }

    private function createEventRecordMetadataSubscriber(
        bool $shouldPersistPayload
    ): EventRecordMetadataSubscriber {
        return new EventRecordMetadataSubscriber(
            $shouldPersistPayload
        );
    }
}
