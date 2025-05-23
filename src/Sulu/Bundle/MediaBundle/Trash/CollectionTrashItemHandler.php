<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionRestoredEvent;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Webmozart\Assert\Assert;

final class CollectionTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private CollectionRepositoryInterface $collectionRepository,
        private EntityManagerInterface $entityManager,
        private DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    /**
     * @param CollectionInterface $resource
     */
    public function store(object $resource, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($resource, CollectionInterface::class);

        $data = [
            'id' => $resource->getId(),
            'key' => $resource->getKey(),
            'typeId' => $resource->getType()->getId(),
            'defaultMetaLocale' => $resource->getDefaultMeta()->getLocale(),
            'parentId' => null,
            'meta' => [],
            'created' => $resource->getCreated()->format('c'),
            'changed' => $resource->getChanged()->format('c'),
            'creatorId' => null,
            'changerId' => null,
        ];

        $creator = $resource->getCreator();
        if ($creator) {
            $data['creatorId'] = $creator->getId();
        }

        $changer = $resource->getChanger();
        if ($changer) {
            $data['changerId'] = $changer->getId();
        }

        $parent = $resource->getParent();
        if ($parent) {
            $data['parentId'] = $parent->getId();
        }

        $collectionTitles = [];
        foreach ($resource->getMeta() as $meta) {
            $locale = $meta->getLocale();

            $data['meta'][$locale] = \array_filter([
                'title' => $meta->getTitle(),
                'description' => $meta->getDescription(),
            ]);

            $collectionTitles[$locale] = $meta->getTitle();
        }

        return $this->trashItemRepository->create(
            CollectionInterface::RESOURCE_KEY,
            (string) $resource->getId(),
            $collectionTitles,
            \array_filter($data),
            null,
            $options,
            MediaAdmin::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        $collection = new Collection();

        $collection->setType($this->getReference(CollectionType::class, $data['typeId']));
        $collection->setKey($data['key'] ?? null);

        if ($parent = $this->findEntity(Collection::class, $data['parentId'] ?? null)) {
            $collection->setParent($parent);
        }

        if ($collection instanceof Collection) {
            if (\is_string($data['changed'] ?? null)) {
                $collection->setChanged(new \DateTimeImmutable($data['changed']));
            }
            if (\is_string($data['created'] ?? null)) {
                $collection->setCreated(new \DateTimeImmutable($data['created']));
            }
            $collection->setCreator($this->findEntity(UserInterface::class, $data['creatorId'] ?? null));
            $collection->setChanger($this->findEntity(UserInterface::class, $data['changerId'] ?? null));
        }

        foreach (($data['meta'] ?? []) as $locale => $metaData) {
            $collectionMeta = new CollectionMeta();
            $collectionMeta->setLocale($locale);
            $collectionMeta->setTitle($metaData['title'] ?? null);
            $collectionMeta->setDescription($metaData['description'] ?? null);
            $collectionMeta->setCollection($collection);
            $collection->addMeta($collectionMeta);

            if ($data['defaultMetaLocale'] === $locale) {
                $collection->setDefaultMeta($collectionMeta);
            }
        }

        $this->domainEventCollector->collect(
            new CollectionRestoredEvent($collection, $data)
        );

        if (null === $this->collectionRepository->findCollectionById($id)) {
            $this->doctrineRestoreHelper->persistAndFlushWithId($collection, $id);
        } else {
            $this->entityManager->persist($collection);
            $this->entityManager->flush();
        }

        return $collection;
    }

    public static function getResourceKey(): string
    {
        return CollectionInterface::RESOURCE_KEY;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param mixed|null $id
     *
     * @return T|null
     */
    private function findEntity(string $className, $id)
    {
        if ($id) {
            return $this->entityManager->find($className, $id);
        }

        return null;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param string|int $id
     *
     * @return T
     */
    private function getReference(string $className, $id)
    {
        /** @var T $object */
        $object = $this->entityManager->getReference($className, $id);

        return $object;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration('restore_collection', MediaAdmin::MEDIA_OVERVIEW_VIEW, ['id' => 'id']);
    }
}
