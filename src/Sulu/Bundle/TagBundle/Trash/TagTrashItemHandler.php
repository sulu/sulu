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
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Webmozart\Assert\Assert;

final class TagTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var TagRepositoryInterface
     */
    private $tagRepository;

    /**
     * @var DoctrineRestoreHelperInterface
     */
    private $doctrineRestoreHelper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        TagRepositoryInterface $tagRepository,
        DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        EntityManagerInterface $entityManager,
        DomainEventCollectorInterface $domainEventCollector
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->tagRepository = $tagRepository;
        $this->doctrineRestoreHelper = $doctrineRestoreHelper;
        $this->entityManager = $entityManager;
        $this->domainEventCollector = $domainEventCollector;
    }

    /**
     * @param TagInterface $tag
     */
    public function store(object $tag): TrashItemInterface
    {
        Assert::isInstanceOf($tag, TagInterface::class);

        return $this->trashItemRepository->create(
            TagInterface::RESOURCE_KEY,
            (string) $tag->getId(),
            [
                'name' => $tag->getName(),
                'created' => $tag->getCreated()->format('c'),
                'creatorId' => $tag->getCreator() ? $tag->getCreator()->getId() : null,
            ],
            $tag->getName(),
            TagAdmin::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        /** @var TagInterface|null $existingTag */
        $existingTag = $this->tagRepository->findTagByName($data['name']);
        if (null !== $existingTag) {
            throw new TagAlreadyExistsException($existingTag->getName());
        }

        $tag = $this->tagRepository->createNew();
        $tag->setName($data['name']);

        if ($tag instanceof Tag) {
            $tag->setCreated(new \DateTime($data['created']));
            $tag->setCreator($this->entityManager->find(UserInterface::class, $data['creatorId']));
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
}
