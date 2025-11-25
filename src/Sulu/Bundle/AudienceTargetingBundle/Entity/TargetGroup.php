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

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * Entity class for target group.
 */
class TargetGroup implements TargetGroupInterface
{
    private int $id;

    private string $title;

    private ?string $description = null;

    private int $priority;

    private bool $allWebspaces = false;

    private bool $active = false;

    /**
     * @var Collection<int, TargetGroupWebspaceInterface>
     */
    private Collection $webspaces;

    /**
     * @var Collection<int, TargetGroupRuleInterface>
     */
    private Collection $rules;

    /**
     * Initialization of collections.
     */
    public function __construct()
    {
        $this->webspaces = new ArrayCollection();
        $this->rules = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function isAllWebspaces(): bool
    {
        return $this->allWebspaces;
    }

    public function setAllWebspaces(bool $allWebspaces): static
    {
        $this->allWebspaces = $allWebspaces;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getWebspaces(): Collection
    {
        return $this->webspaces;
    }

    /**
     * @return string[]
     */
    #[VirtualProperty]
    #[SerializedName('webspaceKeys')]
    public function getWebspaceKeys(): array
    {
        // @phpstan-ignore isset.initializedProperty (property may be uninitialized when loaded from trash)
        if (!isset($this->webspaces)) {
            return [];
        }

        return \array_values(
            \array_map(function(TargetGroupWebspaceInterface $targetGroupWebspace) {
                return $targetGroupWebspace->getWebspaceKey();
            }, $this->webspaces->toArray()),
        );
    }

    public function addWebspace(TargetGroupWebspaceInterface $webspace): static
    {
        $this->webspaces[] = $webspace;

        return $this;
    }

    public function removeWebspace(TargetGroupWebspaceInterface $webspace): static
    {
        $this->webspaces->removeElement($webspace);

        return $this;
    }

    public function clearWebspaces(): static
    {
        foreach ($this->webspaces as $webspace) {
            $this->removeWebspace($webspace);
        }

        return $this;
    }

    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function addRule(TargetGroupRuleInterface $rule): static
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function removeRule(TargetGroupRuleInterface $rule): static
    {
        $this->rules->removeElement($rule);

        return $this;
    }

    public function clearRules(): static
    {
        foreach ($this->rules as $rule) {
            $this->removeRule($rule);
        }

        return $this;
    }
}
