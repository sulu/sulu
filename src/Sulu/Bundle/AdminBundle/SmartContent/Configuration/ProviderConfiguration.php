<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\SmartContent\Configuration;

use Sulu\Component\Content\Compat\PropertyParameter;

/**
 * Provides configuration for smart-content.
 */
class ProviderConfiguration implements ProviderConfigurationInterface
{
    /**
     * @var string
     */
    private $datasourceResourceKey;

    /**
     * @var string
     */
    private $datasourceListKey;

    /**
     * @var string
     */
    private $datasourceAdapter;

    /**
     * @var bool
     */
    private $audienceTargeting = false;

    /**
     * @var bool
     */
    private $tags = false;

    /**
     * @var PropertyParameter[]
     */
    private $types = [];

    /**
     * @var bool
     */
    private $categories = false;

    /**
     * @var PropertyParameter[]
     */
    private $sorting = [];

    /**
     * @var bool
     */
    private $limit = false;

    /**
     * @var bool
     */
    private $presentAs = false;

    /**
     * @var bool
     */
    private $paginated = false;

    /**
     * @var string|null
     */
    private $view;

    /**
     * @var array<string, string>|null
     */
    private $resultToView;

    public function hasDatasource(): bool
    {
        return null !== $this->datasourceResourceKey && '' !== $this->datasourceResourceKey;
    }

    public function getDatasourceResourceKey(): ?string
    {
        return $this->datasourceResourceKey;
    }

    public function setDatasourceResourceKey(string $datasourceResourceKey): void
    {
        $this->datasourceResourceKey = $datasourceResourceKey;
    }

    public function setDatasourceListKey(string $datasourceListKey): void
    {
        $this->datasourceListKey = $datasourceListKey;
    }

    public function getDatasourceListKey(): string
    {
        return $this->datasourceListKey;
    }

    public function getDatasourceAdapter(): ?string
    {
        return $this->datasourceAdapter;
    }

    public function setDatasourceAdapter(string $datasourceAdapter): void
    {
        $this->datasourceAdapter = $datasourceAdapter;
    }

    public function hasAudienceTargeting(): bool
    {
        return $this->audienceTargeting;
    }

    public function setAudienceTargeting(bool $audienceTargeting): void
    {
        $this->audienceTargeting = $audienceTargeting;
    }

    public function hasTags(): bool
    {
        return $this->tags;
    }

    public function setTags(bool $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @return null|PropertyParameter[]
     */
    public function getTypes(): ?array
    {
        return $this->types;
    }

    public function hasTypes(): bool
    {
        return \count($this->types) > 0;
    }

    /**
     * @param PropertyParameter[] $types
     */
    public function setTypes(array $types): void
    {
        $this->types = $types;
    }

    public function hasCategories(): bool
    {
        return $this->categories;
    }

    public function setCategories(bool $categories): void
    {
        $this->categories = $categories;
    }

    public function getSorting(): ?array
    {
        return $this->sorting;
    }

    public function hasSorting(): bool
    {
        return \count($this->sorting) > 0;
    }

    /**
     * @param PropertyParameter[] $sorting
     */
    public function setSorting(array $sorting): void
    {
        $this->sorting = $sorting;
    }

    public function hasLimit(): bool
    {
        return $this->limit;
    }

    public function setLimit(bool $limit): void
    {
        $this->limit = $limit;
    }

    public function hasPresentAs(): bool
    {
        return $this->presentAs;
    }

    public function setPresentAs(bool $presentAs): void
    {
        $this->presentAs = $presentAs;
    }

    public function hasPagination(): bool
    {
        return $this->paginated;
    }

    public function setPaginated(bool $paginated): void
    {
        $this->paginated = $paginated;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function setView(?string $view): void
    {
        $this->view = $view;
    }

    public function getResultToView(): ?array
    {
        return $this->resultToView;
    }

    /**
     * @param array<string, string>|null $resultToView
     */
    public function setResultToView(?array $resultToView): void
    {
        $this->resultToView = $resultToView;
    }
}
