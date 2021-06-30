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

namespace Sulu\Bundle\TestBundle\Testing\Trash;

use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;

class TestTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    public function __construct(TrashItemRepositoryInterface $trashItemRepository)
    {
        $this->trashItemRepository = $trashItemRepository;
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData): object
    {
        return new \stdClass();
    }

    public function store(object $resource): TrashItemInterface
    {
        return $this->trashItemRepository->create(
            static::getResourceKey(),
            '1',
            [],
            'Resource title',
            null,
            null,
            null
        );
    }

    public static function getResourceKey(): string
    {
        return 'test_resource';
    }
}
