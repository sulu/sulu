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
        $seo = $this->seoData['seo'] ?? null;
        if (!\is_array($seo)) {
            return null;
        }

        $value = $seo['title'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setSeoTitle(?string $seoTitle): void
    {
        if (!isset($this->seoData['seo']) || !\is_array($this->seoData['seo'])) {
            $this->seoData['seo'] = [];
        }
        $this->seoData['seo']['title'] = $seoTitle;
    }

    public function getSeoDescription(): ?string
    {
        $seo = $this->seoData['seo'] ?? null;
        if (!\is_array($seo)) {
            return null;
        }

        $value = $seo['description'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setSeoDescription(?string $seoDescription): void
    {
        if (!isset($this->seoData['seo']) || !\is_array($this->seoData['seo'])) {
            $this->seoData['seo'] = [];
        }
        $this->seoData['seo']['description'] = $seoDescription;
    }

    public function getSeoKeywords(): ?string
    {
        $seo = $this->seoData['seo'] ?? null;
        if (!\is_array($seo)) {
            return null;
        }

        $value = $seo['keywords'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setSeoKeywords(?string $seoKeywords): void
    {
        if (!isset($this->seoData['seo']) || !\is_array($this->seoData['seo'])) {
            $this->seoData['seo'] = [];
        }
        $this->seoData['seo']['keywords'] = $seoKeywords;
    }

    public function getSeoCanonicalUrl(): ?string
    {
        $seo = $this->seoData['seo'] ?? null;
        if (!\is_array($seo)) {
            return null;
        }

        $value = $seo['canonicalUrl'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setSeoCanonicalUrl(?string $seoCanonicalUrl): void
    {
        if (!isset($this->seoData['seo']) || !\is_array($this->seoData['seo'])) {
            $this->seoData['seo'] = [];
        }
        $this->seoData['seo']['canonicalUrl'] = $seoCanonicalUrl;
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
