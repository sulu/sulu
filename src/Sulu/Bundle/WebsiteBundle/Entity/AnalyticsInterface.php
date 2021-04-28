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
    const RESOURCE_KEY = 'analytics';

    const LIST_KEY = 'analytics';

    public function getId(): ?int;

    public function setTitle(string $title): self;

    public function getTitle(): string;

    public function setAllDomains(bool $allDomains): self;

    public function isAllDomains(): bool;

    /**
     * @param mixed $content
     */
    public function setContent($content): self;

    /**
     * @return mixed
     */
    public function getContent();

    public function setType(string $type): self;

    public function getType(): string;

    public function setWebspaceKey(string $webspaceKey): self;

    public function getWebspaceKey(): string;

    public function addDomain(Domain $domain): self;

    public function removeDomain(Domain $domain): self;

    public function clearDomains(): self;

    /**
     * @return Collection<int, string>|null
     */
    public function getDomains(): ?Collection;
}
