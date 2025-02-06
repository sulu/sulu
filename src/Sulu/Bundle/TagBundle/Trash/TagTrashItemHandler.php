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

namespace Sulu\Bundle\TagBundle\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Domain\Event\TagRestoredEvent;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Webmozart\Assert\Assert;

final class TagTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private TagRepositoryInterface $tagRepository,
        private DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    /**
     * @param TagInterface $tag
     */
    public function store(object $tag, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($tag, TagInterface::class);

        $creator = $tag->getCreator();

        return $this->trashItemRepository->create(
            TagInterface::RESOURCE_KEY,
            (string) $tag->getId(),
            $tag->getName(),
            [
                'name' => $tag->getName(),
                'created' => $tag->getCreated()->format('c'),
                'creatorId' => $creator ? $creator->getId() : null,
            ],
            null,
            $options,
            TagAdmin::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        $existingTag = $this->tagRepository->findTagByName($data['name']);
        if (null !== $existingTag) {
            throw new TagAlreadyExistsException($existingTag->getName());
        }

        $tag = $this->tagRepository->createNew();
        $tag->setName($data['name']);

        if ($tag instanceof Tag) {
            $tag->setCreated(new \DateTimeImmutable($data['created']));
            $tag->setCreator($this->findEntity(UserInterface::class, $data['creatorId']));
        }

        $this->domainEventCollector->collect(
            new TagRestoredEvent($tag, $data)
        );

        $existingTag = $this->tagRepository->findTagById($id);
        if (null === $existingTag) {
            $this->doctrineRestoreHelper->persistAndFlushWithId($tag, $id);
        } else {
            $this->entityManager->persist($tag);
            $this->entityManager->flush();
        }

        return $tag;
    }

    public static function getResourceKey(): string
    {
        return TagInterface::RESOURCE_KEY;
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

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(null, TagAdmin::EDIT_FORM_VIEW, ['id' => 'id']);
    }
}
