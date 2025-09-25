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

namespace Sulu\Content\Domain\Model;

trait SeoTrait
{
    /**
     * @var array<string, mixed>
     */
    private $seoData = [];

    /**
     * @var bool
     */
    private $seoNoIndex = false;

    /**
     * @var bool
     */
    private $seoNoFollow = false;

    /**
     * @var bool
     */
    private $seoHideInSitemap = false;

    public function getSeoData(): array
    {
        return $this->seoData;
    }

    /**
     * @param array<string, mixed> $seoData
     */
    public function setSeoData(array $seoData): void
    {
        $this->seoData = $seoData;
    }

    public function getSeoNoIndex(): bool
    {
        return $this->seoNoIndex;
    }

    public function setSeoNoIndex(bool $seoNoIndex): void
    {
        $this->seoNoIndex = $seoNoIndex;
    }

    public function getSeoNoFollow(): bool
    {
        return $this->seoNoFollow;
    }

    public function setSeoNoFollow(bool $seoNoFollow): void
    {
        $this->seoNoFollow = $seoNoFollow;
    }

    public function getSeoHideInSitemap(): bool
    {
        return $this->seoHideInSitemap;
    }

    public function setSeoHideInSitemap(bool $seoHideInSitemap): void
    {
        $this->seoHideInSitemap = $seoHideInSitemap;
    }
}
