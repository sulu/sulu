<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface AnalyticsInterface
{
    public const RESOURCE_KEY = 'analytics';

    public const LIST_KEY = 'analytics';

    public function getId(): int;

    public function setTitle(string $title): static;

    public function getTitle(): string;

    public function setAllDomains(bool $allDomains): static;

    public function isAllDomains(): bool;

    public function setContent(mixed $content): static;

    public function getContent(): mixed;

    public function setType(string $type): static;

    public function getType(): string;

    public function setWebspaceKey(string $webspaceKey): static;

    public function getWebspaceKey(): string;

    public function addDomain(Domain $domain): static;

    public function removeDomain(Domain $domain): static;

    public function clearDomains(): static;

    /**
     * @return Collection<int, string>|null
     */
    public function getDomains(): ?Collection;
}
