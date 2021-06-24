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

namespace Sulu\Bundle\TrashBundle\Application\TrashManager;

use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Exception\RestoreTrashItemHandlerNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Exception\StoreTrashItemHandlerNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;

class TrashManager implements TrashManagerInterface
{
    /**
     * @var iterable<StoreTrashItemHandlerInterface>
     */
    private $storeTrashItemHandlers;

    /**
     * @var iterable<RestoreTrashItemHandlerInterface>
     */
    private $restoreTrashItemHandlers;

    /**
     * @param iterable<StoreTrashItemHandlerInterface> $storeTrashItemHandlers
     * @param iterable<RestoreTrashItemHandlerInterface> $restoreTrashItemHandlers
     */
    public function __construct(iterable $storeTrashItemHandlers, iterable $restoreTrashItemHandlers)
    {
        $this->storeTrashItemHandlers = $storeTrashItemHandlers;
        $this->restoreTrashItemHandlers = $restoreTrashItemHandlers;
    }

    public function store(string $resourceKey, object $object): TrashItemInterface
    {
        foreach ($this->storeTrashItemHandlers as $storeTrashItemHandler) {
            if (!$storeTrashItemHandler->supports($resourceKey)) {
                continue;
            }

            return $storeTrashItemHandler->store($object);
        }

        throw new StoreTrashItemHandlerNotFoundException($resourceKey);
    }

    public function restore(TrashItemInterface $trashItem): object
    {
        $resourceKey = $trashItem->getResourceKey();

        foreach ($this->restoreTrashItemHandlers as $restoreTrashItemHandler) {
            if (!$restoreTrashItemHandler->supports($resourceKey)) {
                continue;
            }

            return $restoreTrashItemHandler->restore($trashItem);
        }

        throw new RestoreTrashItemHandlerNotFoundException($resourceKey);
    }
}
