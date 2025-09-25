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

interface SeoInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getSeoData(): array;

    /**
     * @param array<string, mixed> $seoData
     */
    public function setSeoData(array $seoData): void;

    public function getSeoNoIndex(): bool;

    public function setSeoNoIndex(bool $seoNoIndex): void;

    public function getSeoNoFollow(): bool;

    public function setSeoNoFollow(bool $seoNoFollow): void;

    public function getSeoHideInSitemap(): bool;

    public function setSeoHideInSitemap(bool $seoHideInSitemap): void;
}
