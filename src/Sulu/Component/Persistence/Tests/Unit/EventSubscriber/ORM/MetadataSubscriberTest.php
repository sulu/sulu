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
use Sulu\Component\Persistence\EventSubscriber\ORM\MetadataSubscriber;

class MetadataSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ORM\Event\LoadClassMetadataEventArgs
     */
    protected $loadClassMetadataEvent;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $classMetadata;

    /**
     * @var \ReflectionClass
     */
    protected $reflection;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Doctrine\ORM\Configuration
     */
    protected $configuration;

    /**
     * @var MetadataSubscriber
     */
    protected $subscriber;

    /**
     * @var \stdClass
     */
    protected $object;

    /**
     * @var \stdClass
     */
    protected $parentObject;

    public function setUp()
    {
        parent::setUp();
        $this->loadClassMetadataEvent = $this->prophesize('Doctrine\ORM\Event\LoadClassMetadataEventArgs');

        $this->parentObject = $this->prophesize('\stdClass');
        $this->object = $this->prophesize('\stdClass')
            ->willExtend(get_class($this->parentObject->reveal()));

        $objects = [
            'sulu' => [
                'contact' => [
                    'model' => '\stdClass',
                    'repository' => '\Sulu\Bundle\ContactBundle\Entity\ContactRepository',
                ],
                'member' => [
                    'model' => '\Closure',
                ],
                'user' => [
                    'model' => get_class($this->object->reveal()),
                ],
            ],
        ];

        $this->classMetadata = $this->prophesize('Doctrine\ORM\Mapping\ClassMetadata');
        $this->reflection = $this->prophesize('\ReflectionClass');
        $this->entityManager = $this->prophesize('Doctrine\ORM\EntityManager');
        $this->configuration = $this->prophesize('Doctrine\ORM\Configuration');

        $this->subscriber = new MetadataSubscriber($objects);
    }

    public function testLoadClassMetadataWithCustomRepository()
    {
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getName()->willReturn('\stdClass');

        $this->classMetadata
            ->setCustomRepositoryClass('\Sulu\Bundle\ContactBundle\Entity\ContactRepository')
            ->shouldBeCalled();
        $this->loadClassMetadataEvent->getEntityManager()->willReturn($this->entityManager->reveal());
        $this->entityManager->getConfiguration()->willReturn($this->configuration->reveal());

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }

    public function testLoadClassMetadataWithoutCustomRepository()
    {
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getName()->willReturn('\Closure');

        $this->classMetadata
            ->setCustomRepositoryClass('Sulu\Bundle\ContactBundle\Entity\ContactRepository')
            ->shouldNotBeCalled();
        $this->loadClassMetadataEvent->getEntityManager()->willReturn($this->entityManager->reveal());
        $this->entityManager->getConfiguration()->willReturn($this->configuration->reveal());

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }

    public function testLoadClassMetadataWithoutParent()
    {
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getName()->willReturn(get_class($this->object->reveal()));

        $this->classMetadata
            ->setCustomRepositoryClass('Sulu\Bundle\ContactBundle\Entity\ContactRepository')
            ->shouldNotBeCalled();
        $this->loadClassMetadataEvent->getEntityManager()->willReturn($this->entityManager->reveal());

        $this->entityManager->getConfiguration()->willReturn($this->configuration->reveal());
        $this->configuration->getNamingStrategy()->willReturn(null);

        /** @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver $mappingDriver */
        $mappingDriver = $this->prophesize('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');
        $this->configuration->getMetadataDriverImpl()->willReturn($mappingDriver->reveal());
        $mappingDriver->getAllClassNames()->willReturn([get_class($this->parentObject->reveal())]);
        $mappingDriver->loadMetadataForClass(
            get_class($this->parentObject->reveal()),
            Argument::type('Doctrine\ORM\Mapping\ClassMetadata')
        )->shouldBeCalled();

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }
}
