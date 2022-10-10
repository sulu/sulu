<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Trash;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Trash\CollectionTrashItemHandler;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItem;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class CollectionTrashItemHandlerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<TrashItemRepositoryInterface>
     */
    private $trashItemRepository;

    /**
     * @var ObjectProphecy<CollectionRepositoryInterface>
     */
    private $collectionRepository;

    /**
     * @var ObjectProphecy<DoctrineRestoreHelperInterface>
     */
    private $doctrineRestoreHelper;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var CollectionTrashItemHandler
     */
    private $collectionTrashItemHandler;

    public function setUp(): void
    {
        $this->trashItemRepository = $this->prophesize(TrashItemRepositoryInterface::class);
        $this->collectionRepository = $this->prophesize(CollectionRepositoryInterface::class);
        $this->doctrineRestoreHelper = $this->prophesize(DoctrineRestoreHelperInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        // we don't want expect calls on model classes instead return a real trashItem which data should be checked
        $this->trashItemRepository->create(Argument::cetera())
            ->will(function($args) {
                $trashItem = new TrashItem();
                $trashItem->setResourceKey($args[0]);
                $trashItem->setResourceId($args[1]);
                $trashItem->setRestoreData($args[3]);
                $trashItem->setRestoreType($args[4]);
                $trashItem->setRestoreOptions($args[5]);
                $trashItem->setResourceSecurityContext($args[6]);
                $trashItem->setResourceSecurityObjectType($args[7]);
                $trashItem->setResourceSecurityObjectId($args[8]);

                foreach ($args[2] as $locale => $title) {
                    $trashItem->setResourceTitle($title, $locale);
                }

                return $trashItem;
            });

        $this->doctrineRestoreHelper->persistAndFlushWithId(Argument::cetera())
            ->will(static function($args): void {
                /** @var CollectionInterface $collection */
                $collection = $args[0];

                static::setPrivateProperty($collection, 'id', $args[1]);
            });

        $this->entityManager->getReference(Argument::cetera())
            ->will(static function($args) {
                /** @var class-string $className */
                $className = $args[0];

                $object = new $className();
                static::setPrivateProperty($object, 'id', $args[1]);

                return $object;
            });

        $this->collectionTrashItemHandler = new CollectionTrashItemHandler(
            $this->trashItemRepository->reveal(),
            $this->collectionRepository->reveal(),
            $this->entityManager->reveal(),
            $this->doctrineRestoreHelper->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testStoreMinimal(): void
    {
        $collection = $this->getMinimalCollection();

        $trashItem = $this->collectionTrashItemHandler->store($collection);

        $this->assertSame('1', $trashItem->getResourceId());
        $this->assertSame('Minimal Collection', $trashItem->getResourceTitle());
        $this->assertSame('collections', $trashItem->getResourceKey());
        $this->assertSame('sulu.media.collections', $trashItem->getResourceSecurityContext());
        $this->assertNull($trashItem->getResourceSecurityObjectId());
        $this->assertNull($trashItem->getResourceSecurityObjectType());
        $this->assertSame($this->getMinimalCollectionData(), $trashItem->getRestoreData());
    }

    public function testStoreComplex(): void
    {
        $collection = $this->getComplexCollection();

        $trashItem = $this->collectionTrashItemHandler->store($collection);

        $this->assertSame('1', $trashItem->getResourceId());
        $this->assertSame('Complex Collection', $trashItem->getResourceTitle());
        $this->assertSame('collections', $trashItem->getResourceKey());
        $this->assertSame('sulu.media.collections', $trashItem->getResourceSecurityContext());
        $this->assertNull($trashItem->getResourceSecurityObjectId());
        $this->assertNull($trashItem->getResourceSecurityObjectType());
        $this->assertSame($this->getComplexCollectionData(), $trashItem->getRestoreData());
    }

    public function testRestoreMinimal(): void
    {
        $collectionData = $this->getMinimalCollectionData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($collectionData);

        $this->collectionRepository->findCollectionById(1)
            ->willReturn(null)
            ->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::that(static function(DomainEvent $event) {
            static::assertSame('restored', $event->getEventType());

            return true;
        }))
            ->shouldBeCalledOnce();

        $collection = $this->collectionTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame(1, $collection->getId());
        $this->assertSame('Minimal Collection', $collection->getDefaultMeta()->getTitle());
        $this->assertSame('2020-11-05T12:15:00+01:00', $collection->getCreated()->format('c'));
        $this->assertSame('2020-12-10T14:15:00+01:00', $collection->getChanged()->format('c'));
    }

    public function testRestoreSameIdExists(): void
    {
        $collectionData = $this->getMinimalCollectionData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($collectionData);

        $existCollection = new Collection();
        static::setPrivateProperty($existCollection, 'id', 1);
        $this->collectionRepository->findCollectionById(Argument::cetera())
            ->willReturn($existCollection)
            ->shouldBeCalled();
        $this->entityManager->persist(Argument::cetera())
            ->shouldBeCalled()
            ->will(static function($args): void {
                /** @var CollectionInterface $collection */
                $collection = $args[0];

                static::setPrivateProperty($collection, 'id', 2);
            });
        $this->domainEventCollector->collect(Argument::that(static function(DomainEvent $event) {
            static::assertSame('restored', $event->getEventType());

            return true;
        }))
            ->shouldBeCalledOnce();
        $this->entityManager->flush()
            ->shouldBeCalled();

        $collection = $this->collectionTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame(2, $collection->getId());
        $this->assertSame('Minimal Collection', $collection->getDefaultMeta()->getTitle());
        $this->assertSame('2020-11-05T12:15:00+01:00', $collection->getCreated()->format('c'));
        $this->assertSame('2020-12-10T14:15:00+01:00', $collection->getChanged()->format('c'));
    }

    public function testRestoreComplex(): void
    {
        $collectionData = $this->getComplexCollectionData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($collectionData);

        $this->collectionRepository->findCollectionById(1)
            ->willReturn(null)
            ->shouldBeCalled();
        $this->entityManager->find(Argument::cetera())
            ->shouldBeCalled()
            ->will(static function($args) {
                /** @var class-string $className */
                $className = $args[0];
                $className = static::resolveInterfaceToClass($className);

                $object = new $className();
                static::setPrivateProperty($object, 'id', $args[1]);

                return $object;
            });
        $this->doctrineRestoreHelper->persistAndFlushWithId(Argument::cetera())
            ->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::that(static function(DomainEvent $event) {
            static::assertSame('restored', $event->getEventType());

            return true;
        }))
            ->shouldBeCalledOnce();

        $collection = $this->collectionTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Collection::class, $collection);
        $trashItem = $this->collectionTrashItemHandler->store($collection);
        $this->assertSame($collectionData, $trashItem->getRestoreData());
    }

    private function getMinimalCollection(): Collection
    {
        $collection = new Collection();
        static::setPrivateProperty($collection, 'id', 1);
        $collection->setCreated(new \DateTime('2020-11-05T12:15:00+01:00'));
        $collection->setChanged(new \DateTime('2020-12-10T14:15:00+01:00'));

        $collectionType = new CollectionType();
        static::setPrivateProperty($collectionType, 'id', 11);
        $collection->setType($collectionType);

        $collectionMeta1 = new CollectionMeta();
        $collectionMeta1->setLocale('en');
        $collectionMeta1->setTitle('Minimal Collection');
        $collectionMeta1->setCollection($collection);
        $collection->addMeta($collectionMeta1);
        $collection->setDefaultMeta($collectionMeta1);

        return $collection;
    }

    /**
     * @return mixed[]
     */
    private function getMinimalCollectionData(): array
    {
        return [
            'id' => 1,
            'typeId' => 11,
            'defaultMetaLocale' => 'en',
            'meta' => [
                'en' => [
                    'title' => 'Minimal Collection',
                ],
            ],
            'created' => '2020-11-05T12:15:00+01:00',
            'changed' => '2020-12-10T14:15:00+01:00',
        ];
    }

    private function getComplexCollection(): Collection
    {
        $collection = new Collection();
        static::setPrivateProperty($collection, 'id', 1);
        $collection->setKey('key');
        $collection->setCreated(new \DateTime('2020-11-05T12:15:00+01:00'));
        $collection->setChanged(new \DateTime('2020-12-10T14:15:00+01:00'));

        $creator = new User();
        static::setPrivateProperty($creator, 'id', 21);
        $collection->setCreator($creator);

        $changer = new User();
        static::setPrivateProperty($changer, 'id', 22);
        $collection->setChanger($changer);

        $collectionType = new CollectionType();
        static::setPrivateProperty($collectionType, 'id', 11);
        $collection->setType($collectionType);

        $collectionMeta1 = new CollectionMeta();
        $collectionMeta1->setLocale('en');
        $collectionMeta1->setTitle('Complex Collection');
        $collectionMeta1->setDescription('Complex Description');
        $collectionMeta1->setCollection($collection);
        $collection->addMeta($collectionMeta1);
        $collection->setDefaultMeta($collectionMeta1);

        $collectionMeta2 = new CollectionMeta();
        $collectionMeta2->setLocale('de');
        $collectionMeta2->setTitle('Komplex Collection');
        $collectionMeta2->setDescription('Komplex Description');
        $collectionMeta2->setCollection($collection);
        $collection->addMeta($collectionMeta2);

        return $collection;
    }

    /**
     * @return mixed[]
     */
    private function getComplexCollectionData(): array
    {
        return [
            'id' => 1,
            'key' => 'key',
            'typeId' => 11,
            'defaultMetaLocale' => 'en',
            'meta' => [
                'en' => [
                    'title' => 'Complex Collection',
                    'description' => 'Complex Description',
                ],
                'de' => [
                    'title' => 'Komplex Collection',
                    'description' => 'Komplex Description',
                ],
            ],
            'created' => '2020-11-05T12:15:00+01:00',
            'changed' => '2020-12-10T14:15:00+01:00',
            'creatorId' => 21,
            'changerId' => 22,
        ];
    }

    /**
     * @param class-string $className
     *
     * @return class-string
     */
    private static function resolveInterfaceToClass(string $className): string
    {
        $mapping = [
            UserInterface::class => User::class,
        ];

        return $mapping[$className] ?? $className;
    }
}
