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
    private array $seoData = [];

    private bool $seoNoIndex = false;

    private bool $seoNoFollow = false;

    private bool $seoHideInSitemap = false;

    public function getSeoTitle(): ?string
    {
        $value = $this->seoData['title'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setSeoTitle(?string $seoTitle): void
    {
        $this->seoData['title'] = $seoTitle;
    }

    public function getSeoDescription(): ?string
    {
        $value = $this->seoData['description'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setSeoDescription(?string $seoDescription): void
    {
        $this->seoData['description'] = $seoDescription;
    }

    public function getSeoKeywords(): ?string
    {
        $value = $this->seoData['keywords'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setSeoKeywords(?string $seoKeywords): void
    {
        $this->seoData['keywords'] = $seoKeywords;
    }

    public function getSeoCanonicalUrl(): ?string
    {
        $value = $this->seoData['canonicalUrl'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setSeoCanonicalUrl(?string $seoCanonicalUrl): void
    {
        $this->seoData['canonicalUrl'] = $seoCanonicalUrl;
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

    /**
     * @return array<string, mixed>
     */
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
}
