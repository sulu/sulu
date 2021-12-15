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

class RemoveDependantResourcesFoundException extends \Exception implements RemoveDependantResourcesFoundExceptionInterface
{
    /**
     * @var array{id: int|string, resourceKey: string}
     */
    protected $resource;

    /**
     * @var array<int, array<array{id: int|string, resourceKey: string}>>
     */
    protected $dependantResourceBatches;

    /**
     * @var int
     */
    protected $dependantResourcesCount;

    /**
     * @param array{id: int|string, resourceKey: string} $resource
     * @param array<int, array<array{id: int|string, resourceKey: string}>> $dependantResourceBatches
     */
    public function __construct(array $resource, array $dependantResourceBatches, int $dependantResourcesCount)
    {
        $this->resource = $resource;
        $this->dependantResourceBatches = $dependantResourceBatches;
        $this->dependantResourcesCount = $dependantResourcesCount;

        parent::__construct(
            \sprintf('Resource has %d dependant resources.', $this->dependantResourcesCount),
            static::EXCEPTION_CODE_DEPENDANT_RESOURCES_FOUND
        );
    }

    public function getTitleTranslationKey(): string
    {
        return 'sulu_admin.delete_element_dependant_warning_title';
    }

    public function getTitleTranslationParameters(): array
    {
        return [
            '%count%' => $this->dependantResourcesCount,
        ];
    }

    public function getDetailTranslationKey(): string
    {
        return 'sulu_admin.delete_element_dependant_warning_detail';
    }

    public function getDetailTranslationParameters(): array
    {
        return [
            '%count%' => $this->dependantResourcesCount,
        ];
    }

    public function getResource(): array
    {
        return $this->resource;
    }

    public function getDependantResourceBatches(): array
    {
        return $this->dependantResourceBatches;
    }

    public function getDependantResourcesCount(): int
    {
        return $this->dependantResourcesCount;
    }
}
