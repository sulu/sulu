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
use Webmozart\Assert\Assert;

final class TagTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface
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

    public function store(object $tag): TrashItemInterface
    {
        Assert::isInstanceOf($tag, TagInterface::class);

        return $this->trashItemRepository->create(
            TagInterface::RESOURCE_KEY,
            (string) $tag->getId(),
            [
                'name' => $tag->getName(),
            ],
            $tag->getName(),
            TagAdmin::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData): object
    {
        return $this->tagManager->restore(
            (int) $trashItem->getResourceId(),
            $trashItem->getRestoreData()
        );
    }

    public static function getResourceKey(): string
    {
        return TagInterface::RESOURCE_KEY;
    }
}
