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

class ReferencingResourcesFoundException extends \Exception implements ReferencingResourcesFoundExceptionInterface
{
    /**
     * @param array{id: int|string, resourceKey: string} $resource
     * @param array<array{id: int|string, resourceKey: string, title: string|null}> $referencingResources
     */
    public function __construct(
        private array $resource,
        private array $referencingResources,
        private int $referencingResourcesCount
    ) {
        parent::__construct(
            \sprintf(
                'Found %d referencing resources.',
                $this->referencingResourcesCount
            ),
            static::EXCEPTION_CODE_REFERENCING_RESOURCES_FOUND
        );
    }

    public function getResource(): array
    {
        return $this->resource;
    }

    public function getReferencingResources(): array
    {
        return $this->referencingResources;
    }

    public function getReferencingResourcesCount(): int
    {
        return $this->referencingResourcesCount;
    }
}
