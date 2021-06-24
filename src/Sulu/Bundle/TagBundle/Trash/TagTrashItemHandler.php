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

use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;

class TagTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    public function __construct(TrashItemRepositoryInterface $trashItemRepository, TagManagerInterface $tagManager)
    {
        $this->trashItemRepository = $trashItemRepository;
        $this->tagManager = $tagManager;
    }

    public function supports(string $resourceKey): bool
    {
        return TagInterface::RESOURCE_KEY === $resourceKey;
    }

    public function store(object $tag): TrashItemInterface
    {
        if (!$tag instanceof TagInterface) {
            throw new \InvalidArgumentException();
        }

        $trashItem = $this->trashItemRepository->create(
            TagInterface::RESOURCE_KEY,
            [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ],
            $tag->getName(),
            TagAdmin::SECURITY_CONTEXT,
            null,
            null
        );

        $this->trashItemRepository->addAndCommit($trashItem);

        return $trashItem;
    }

    public function restore(TrashItemInterface $trashItem): object
    {
        $restoreData = $trashItem->getRestoreData();
        $id = $restoreData['id'];
        unset($restoreData['id']);

        $tag = $this->tagManager->restore(
            $id,
            $restoreData
        );

        $this->trashItemRepository->removeAndCommit($trashItem);

        return $tag;
    }
}
