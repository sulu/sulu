<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Domain\Model;

interface PreviewLinkInterface
{
    public const RESOURCE_KEY = 'preview_links';

    /**
     * @param mixed[] $options
     */
    public static function create(string $token, string $resourceKey, string $resourceId, string $locale, array $options): self;

    public function getId(): int;

    public function getToken(): string;

    public function getResourceKey(): string;

    public function getResourceId(): string;

    public function getLocale(): string;

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    public function getVisitCount(): int;

    public function increaseVisitCount(): self;

    public function getLastVisit(): ?\DateTimeImmutable;
}
