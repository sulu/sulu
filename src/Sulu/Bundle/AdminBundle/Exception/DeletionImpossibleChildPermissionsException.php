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

class DeletionImpossibleChildPermissionsException extends \Exception
{
    public const EXCEPTION_CODE = 12346; // TODO change

    /**
     * @var array<array{id: int, resourceKey: string}>
     */
    private $unauthorizedChildResources;

    /**
     * @var int
     */
    private $totalUnauthorizedChildren;

    /**
     * @param array<array{id: int, resourceKey: string}> $unauthorizedChildResources
     */
    public function __construct(array $unauthorizedChildResources, int $totalUnauthorizedChildren)
    {
        $this->unauthorizedChildResources = $unauthorizedChildResources;
        $this->totalUnauthorizedChildren = $totalUnauthorizedChildren;

        parent::__construct(
            \sprintf(
                'Resource cannot be deleted, because the user doesn\'t have permissions for %d children',
                $this->totalUnauthorizedChildren
            ),
            static::EXCEPTION_CODE
        );
    }

    /**
     * @return array<array{id: int, resourceKey: string}>
     */
    public function getUnauthorizedChildResources(): array
    {
        return $this->unauthorizedChildResources;
    }

    public function getTotalUnauthorizedChildren(): int
    {
        return $this->totalUnauthorizedChildren;
    }
}
