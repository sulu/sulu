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

namespace Sulu\Bundle\AdminBundle\Exception;

class DeletionImpossibleChildPermissionsException extends \Exception implements DeletionImpossibleChildPermissionsExceptionInterface
{
    /**
     * @var array<array{id: int|string, resourceKey: string}>
     */
    private $unauthorizedChildResources;

    /**
     * @var int
     */
    private $totalUnauthorizedChildResources;

    /**
     * @param array<array{id: int|string, resourceKey: string}> $unauthorizedChildResources
     */
    public function __construct(array $unauthorizedChildResources, int $totalUnauthorizedChildResources)
    {
        $this->unauthorizedChildResources = $unauthorizedChildResources;
        $this->totalUnauthorizedChildResources = $totalUnauthorizedChildResources;

        parent::__construct(
            \sprintf(
                'Resource cannot be deleted, because the user doesn\'t have permissions for %d children',
                $this->totalUnauthorizedChildResources
            ),
            static::EXCEPTION_CODE
        );
    }

    public function getUnauthorizedChildResources(): array
    {
        return $this->unauthorizedChildResources;
    }

    public function getTotalUnauthorizedChildResources(): int
    {
        return $this->totalUnauthorizedChildResources;
    }
}
