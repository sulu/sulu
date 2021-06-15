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

namespace Sulu\Component\Rest\Exception;

class InsufficientChildPermissionsException extends \Exception implements InsufficientChildPermissionsExceptionInterface
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
                'Insufficient permissions for %d children of this resource',
                $this->totalUnauthorizedChildResources
            ),
            static::EXCEPTION_CODE_INSUFFICIENT_CHILD_PERMISSIONS
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
