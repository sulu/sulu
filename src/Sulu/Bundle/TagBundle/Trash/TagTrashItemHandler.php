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
use Sulu\Bundle\TrashBundle\Domain\Factory\TrashItemFactoryInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;

final class TagTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface
{
    /**
     * @var TrashItemFactoryInterface
     */
    private $trashItemFactory;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    public function __construct(TrashItemFactoryInterface $trashItemFactory, TagManagerInterface $tagManager)
    {
        $this->trashItemFactory = $trashItemFactory;
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

        return $this->trashItemFactory->create(
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
}
