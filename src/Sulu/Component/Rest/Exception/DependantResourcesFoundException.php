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

class DependantResourcesFoundException extends \Exception implements DependantResourcesFoundExceptionInterface
{
    /**
     * @var array<int, array<array{id: int|string, resourceKey: string}>>
     */
    private $dependantResources;

    /**
     * @var int
     */
    private $totalDependantResources;

    /**
     * @param array<int, array<array{id: int|string, resourceKey: string}>> $dependantResources
     */
    public function __construct(array $dependantResources, int $totalDependantResources)
    {
        $this->dependantResources = $dependantResources;
        $this->totalDependantResources = $totalDependantResources;

        parent::__construct(
            \sprintf('Resource has %d dependant children', $this->totalDependantResources),
            static::EXCEPTION_CODE_DEPENDANT_RESOURCES_FOUND
        );
    }

    public function getDependantResources(): array
    {
        return $this->dependantResources;
    }

    public function getTotalDependantResources(): int
    {
        return $this->totalDependantResources;
    }
}
