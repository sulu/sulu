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

namespace Sulu\Bundle\TrashBundle\Tests\Application\Trash;

use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Bundle\TrashBundle\Tests\Application\Entity\TestResource;

class TestTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface, RestoreConfigurationProviderInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    public function __construct(TrashItemRepositoryInterface $trashItemRepository)
    {
        $this->trashItemRepository = $trashItemRepository;
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        return new TestResource();
    }

    public function store(object $resource, array $options = []): TrashItemInterface
    {
        return $this->trashItemRepository->create(
            static::getResourceKey(),
            '1',
            'Resource title',
            [],
            null,
            $options,
            null,
            null,
            null
        );
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(null, null, null, ['restoreSerializationGroup']);
    }

    public static function getResourceKey(): string
    {
        return 'test_resource';
    }
}
