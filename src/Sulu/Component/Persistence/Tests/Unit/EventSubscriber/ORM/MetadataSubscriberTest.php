<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Tests\Unit\EventSubscriber\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\ReflectionService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Component\Persistence\EventSubscriber\ORM\MetadataSubscriber;

class MetadataSubscriberTest extends TestCase
{
    /**
     * @var LoadClassMetadataEventArgs|ObjectProphecy
     */
    protected $loadClassMetadataEvent;

    /**
     * @var ClassMetadata|ObjectProphecy
     */
    protected $classMetadata;

    /**
     * @var \ReflectionClass|ObjectProphecy
     */
    protected $reflection;

    /**
     * @var EntityManager|ObjectProphecy
     */
    protected $entityManager;

    /**
     * @var ClassMetadataFactory|ObjectProphecy
     */
    protected $classMetadataFactory;

    /**
     * @var ReflectionService|ObjectProphecy
     */
    protected $reflectionService;

    /**
     * @var Configuration|ObjectProphecy
     */
    protected $configuration;

    /**
     * @var MetadataSubscriber
     */
    protected $subscriber;

    /**
     * @var \stdClass|ObjectProphecy
     */
    protected $object;

    /**
     * @var \stdClass|ObjectProphecy
     */
    protected $parentObject;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadClassMetadataEvent = $this->prophesize(LoadClassMetadataEventArgs::class);

        $this->parentObject = $this->prophesize(\stdClass::class);
        $this->object = $this->prophesize(\stdClass::class)->willExtend(\get_class($this->parentObject->reveal()));

        $objects = [
            'sulu' => [
                'contact' => [
                    'model' => \stdClass::class,
                    'repository' => ContactRepository::class,
                ],
                'member' => [
                    'model' => \Closure::class,
                ],
                'user' => [
                    'model' => \get_class($this->object->reveal()),
                ],
            ],
        ];

        $this->classMetadata = $this->prophesize(ClassMetadata::class);
        $this->reflection = $this->prophesize(\ReflectionClass::class);
        $this->entityManager = $this->prophesize(EntityManager::class);
        $this->classMetadataFactory = $this->prophesize(ClassMetadataFactory::class);
        $this->reflectionService = $this->prophesize(ReflectionService::class);
        $this->configuration = $this->prophesize(Configuration::class);

        $this->subscriber = new MetadataSubscriber($objects);
    }

    public function testLoadClassMetadataWithCustomRepository()
    {
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getName()->willReturn(\stdClass::class);

        $this->classMetadata
            ->setCustomRepositoryClass(ContactRepository::class)
            ->shouldBeCalled();
        $this->loadClassMetadataEvent->getEntityManager()->willReturn($this->entityManager->reveal());
        $this->entityManager->getConfiguration()->willReturn($this->configuration->reveal());
        $this->entityManager->getMetadataFactory()->willReturn($this->classMetadataFactory->reveal());
        $this->classMetadataFactory->getReflectionService()->willReturn($this->reflectionService->reveal());

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }

    public function testLoadClassMetadataWithoutCustomRepository()
    {
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getName()->willReturn(\Closure::class);

        $this->classMetadata
            ->setCustomRepositoryClass(ContactRepository::class)
            ->shouldNotBeCalled();
        $this->loadClassMetadataEvent->getEntityManager()->willReturn($this->entityManager->reveal());
        $this->entityManager->getConfiguration()->willReturn($this->configuration->reveal());
        $this->entityManager->getMetadataFactory()->willReturn($this->classMetadataFactory->reveal());
        $this->classMetadataFactory->getReflectionService()->willReturn($this->reflectionService->reveal());

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }

    public function testLoadClassMetadataWithoutParent()
    {
        $this->object = $this->prophesize(\stdClass::class);
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getName()->willReturn(\get_class($this->object->reveal()));

        $this->classMetadata
            ->setCustomRepositoryClass(ContactRepository::class)
            ->shouldNotBeCalled();

        $this->loadClassMetadataEvent->getEntityManager()->willReturn($this->entityManager->reveal());
        $this->entityManager->getConfiguration()->willReturn($this->configuration->reveal());
        $this->entityManager->getMetadataFactory()->willReturn($this->classMetadataFactory->reveal());
        $this->configuration->getNamingStrategy()->willReturn(null);
        $this->classMetadataFactory->getReflectionService()->willReturn($this->reflectionService->reveal());

        /** @var MappingDriver|ObjectProphecy $mappingDriver */
        $mappingDriver = $this->prophesize(MappingDriver::class);
        $this->configuration->getMetadataDriverImpl()->willReturn($mappingDriver->reveal());
        $mappingDriver->getAllClassNames()->willReturn([\get_class($this->parentObject->reveal())]);
        $mappingDriver->loadMetadataForClass(
            \get_class($this->parentObject->reveal()),
            Argument::type(ClassMetadata::class)
        )->shouldNotBeCalled();

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }
}
