<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

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
    private $datasourceDatagridKey;

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

    public function hasDatasource(): bool
    {
        return null !== $this->datasourceResourceKey && false !== $this->datasourceResourceKey;
    }

    public function getDatasourceResourceKey(): ?string
    {
        return $this->datasourceResourceKey;
    }

    public function setDatasourceResourceKey(string $datasourceResourceKey)
    {
        $this->datasourceResourceKey = $datasourceResourceKey;
    }

    public function setDatasourceDatagridKey(string $datasourceDatagridKey)
    {
        $this->datasourceDatagridKey = $datasourceDatagridKey;
    }

    public function getDatasourceDatagridKey(): string
    {
        return $this->datasourceDatagridKey;
    }

    public function getDatasourceAdapter(): ?string
    {
        return $this->datasourceAdapter;
    }

    public function setDatasourceAdapter(string $datasourceAdapter)
    {
        $this->datasourceAdapter = $datasourceAdapter;
    }

    public function hasAudienceTargeting(): bool
    {
        return $this->audienceTargeting;
    }

    public function setAudienceTargeting(bool $audienceTargeting)
    {
        $this->audienceTargeting = $audienceTargeting;
    }

    public function hasTags(): bool
    {
        return $this->tags;
    }

    public function setTags(bool $tags)
    {
        $this->tags = $tags;
    }

    public function hasCategories(): bool
    {
        return $this->categories;
    }

    public function setCategories(bool $categories)
    {
        $this->categories = $categories;
    }

    public function getSorting(): ?array
    {
        return $this->sorting;
    }

    public function hasSorting(): bool
    {
        return count($this->sorting) > 0;
    }

    public function setSorting(array $sorting)
    {
        $this->sorting = $sorting;
    }

    public function hasLimit(): bool
    {
        return $this->limit;
    }

    public function setLimit(bool $limit)
    {
        $this->limit = $limit;
    }

    public function hasPresentAs(): bool
    {
        return $this->presentAs;
    }

    public function setPresentAs(bool $presentAs)
    {
        $this->presentAs = $presentAs;
    }

    public function hasPagination(): bool
    {
        return $this->paginated;
    }

    public function setPaginated(bool $paginated)
    {
        $this->paginated = $paginated;
    }
}
