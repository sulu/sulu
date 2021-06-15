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

class DeletionWithChildrenNotAllowedException extends \Exception implements DeletionWithChildrenNotAllowedExceptionInterface
{
    /**
     * @var array<int, array<array{id: int|string, resourceKey: string}>>
     */
    private $childResources;

    /**
     * @var int
     */
    private $totalChildResources;

    /**
     * @param array<int, array<array{id: int|string, resourceKey: string}>> $childResources
     */
    public function __construct(array $childResources, int $totalChildResources)
    {
        $this->childResources = $childResources;
        $this->totalChildResources = $totalChildResources;

        parent::__construct(
            \sprintf('Resource cannot be deleted, because it has %d children', $this->totalChildResources),
            static::EXCEPTION_CODE_DELETION_WITH_CHILDREN_NOT_ALLOWED
        );
    }

    public function getChildResources(): array
    {
        return $this->childResources;
    }

    public function getTotalChildResources(): int
    {
        return $this->totalChildResources;
    }
}
