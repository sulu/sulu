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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;

class Analytics implements AnalyticsInterface
{
    private int $id;

    private string $title;

    private bool $allDomains;

    #[Exclude]
    private mixed $content;

    private string $type;

    private string $webspaceKey;

    /** @var Collection<int, Domain> */
    #[Exclude]
    private Collection $domains;

    public function __construct()
    {
        $this->domains = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setAllDomains(bool $allDomains): static
    {
        $this->allDomains = $allDomains;

        return $this;
    }

    public function isAllDomains(): bool
    {
        return $this->allDomains;
    }

    public function setContent(mixed $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setWebspaceKey(string $webspaceKey): static
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function addDomain(Domain $domain): static
    {
        if ($this->domains->contains($domain)) {
            return $this;
        }

        $this->domains[] = $domain;

        return $this;
    }

    public function removeDomain(Domain $domain): static
    {
        $this->domains->removeElement($domain);

        return $this;
    }

    public function clearDomains(): static
    {
        foreach ($this->domains as $domain) {
            $this->domains->removeElement($domain);
        }

        return $this;
    }

    /**
     * @return Collection<int, string>|null
     */
    #[VirtualProperty]
    public function getDomains(): ?Collection
    {
        if (0 === \count($this->domains)) {
            return null;
        }

        return $this->domains->map(function(Domain $domain) {
            return $domain->getUrl();
        });
    }
}
