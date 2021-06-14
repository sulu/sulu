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

class DeletionImpossibleChildrenException extends \Exception
{
    public const EXCEPTION_CODE = 12345; // TODO change

    /**
     * @var array<int, array<array{id: int, resourceKey: string}>>
     */
    private $childResources;

    /**
     * @var int
     */
    private $totalChildren;

    /**
     * @param array<int, array<array{id: int, resourceKey: string}>> $childResources
     */
    public function __construct(array $childResources, int $totalChildren)
    {
        $this->childResources = $childResources;
        $this->totalChildren = $totalChildren;

        parent::__construct(
            \sprintf('Resource cannot be deleted, because it has %d children', $this->totalChildren),
            static::EXCEPTION_CODE
        );
    }

    /**
     * @return array<int, array<array{id: int, resourceKey: string}>>
     */
    public function getChildResources(): array
    {
        return $this->childResources;
    }

    public function getTotalChildren(): int
    {
        return $this->totalChildren;
    }
}
