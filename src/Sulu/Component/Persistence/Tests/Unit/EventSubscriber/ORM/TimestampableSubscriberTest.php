<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Tests\Unit\EventSubscriber\ORM;

use Prophecy\Argument;
use Sulu\Component\Persistence\EventSubscriber\ORM\TimestampableSubscriber;

class TimestampableSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->loadClassMetadataEvent = $this->prophesize('Doctrine\ORM\Event\LoadClassMetadataEventArgs');
        $this->lifecycleEvent = $this->prophesize('Doctrine\ORM\Event\LifecycleEventArgs');
        $this->timestampableObject = $this->prophesize('\stdClass')
            ->willImplement('Sulu\Component\Persistence\Model\TimestampableInterface');
        $this->classMetadata = $this->prophesize('Doctrine\ORM\Mapping\ClassMetadata');
        $this->refl = $this->prophesize('\ReflectionClass');
        $this->entityManager = $this->prophesize('Doctrine\ORM\EntityManager');

        $this->subscriber = new TimestampableSubscriber();
    }

    public function testLoadClassMetadata()
    {
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getReflectionClass()->willReturn($this->refl->reveal());
        $this->refl->implementsInterface('Sulu\Component\Persistence\Model\TimestampableInterface')->willReturn(true);

        $this->classMetadata->mapField(Argument::any())->shouldBeCalled();
        $this->classMetadata->hasField('created')->willReturn(false);
        $this->classMetadata->hasField('changed')->willReturn(false);

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }

    public function provideOnPreUpdate()
    {
        return [
            [null],
            [new \DateTime('2015-01-01')],
        ];
    }

    /**
     * @dataProvider provideOnPreUpdate
     */
    public function testOnPreUpdate($created)
    {
        $entity = $this->timestampableObject->reveal();
        $this->lifecycleEvent->getObject()->willReturn($this->timestampableObject->reveal());
        $this->lifecycleEvent->getObjectManager()->willReturn($this->entityManager->reveal());
        $this->entityManager->getClassMetadata(get_class($entity))->willReturn($this->classMetadata);

        $this->classMetadata->getFieldValue($entity, 'created')->willReturn($created);

        if (null === $created) {
            $this->classMetadata->setFieldValue(
                $entity,
                'created',
                Argument::type('\DateTime')
            )->shouldBeCalled();
        } else {
            $this->classMetadata->setFieldValue(Argument::any())->shouldNotBeCalled();
        }

        $this->classMetadata->setFieldValue(
            $entity,
            'changed',
            Argument::type('\DateTime')
        )->shouldBeCalled();

        $this->subscriber->preUpdate($this->lifecycleEvent->reveal());
    }
}
