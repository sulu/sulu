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
     * @var ComponentConfigurationInterface
     */
    private $datasource;

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

    /**
     * @var string
     */
    private $deepLink;

    /**
     * {@inheritdoc}
     */
    public function hasDatasource()
    {
        return $this->datasource !== null && $this->datasource !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @param ComponentConfigurationInterface $datasource
     */
    public function setDatasource($datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTags()
    {
        return $this->tags;
    }

    /**
     * @param bool $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCategories()
    {
        return $this->categories;
    }

    /**
     * @param bool $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSorting()
    {
        return count($this->sorting) > 0;
    }

    /**
     * @param PropertyParameter[] $sorting
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }

    /**
     * {@inheritdoc}
     */
    public function hasLimit()
    {
        return $this->limit;
    }

    /**
     * @param bool $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPresentAs()
    {
        return $this->presentAs;
    }

    /**
     * @param bool $presentAs
     */
    public function setPresentAs($presentAs)
    {
        $this->presentAs = $presentAs;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPagination()
    {
        return $this->paginated;
    }

    /**
     * @param bool $paginated
     */
    public function setPaginated($paginated)
    {
        $this->paginated = $paginated;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeepLink()
    {
        return $this->deepLink;
    }

    /**
     * @param string $deepLink
     */
    public function setDeepLink($deepLink)
    {
        $this->deepLink = $deepLink;
    }
}
