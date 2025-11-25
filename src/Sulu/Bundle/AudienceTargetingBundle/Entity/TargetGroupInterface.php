<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for target group entity.
 */
interface TargetGroupInterface
{
    public function getId(): int;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getPriority(): int;

    public function setPriority(int $priority): static;

    public function isAllWebspaces(): bool;

    public function setAllWebspaces(bool $allWebspaces): static;

    public function isActive(): bool;

    public function setActive(bool $active): static;

    /**
     * @return Collection<int, TargetGroupWebspaceInterface>
     */
    public function getWebspaces(): Collection;

    public function addWebspace(TargetGroupWebspaceInterface $webspace): static;

    public function removeWebspace(TargetGroupWebspaceInterface $webspace): static;

    public function clearWebspaces(): static;

    /**
     * @return Collection<int, TargetGroupRuleInterface>
     */
    public function getRules(): Collection;

    public function addRule(TargetGroupRuleInterface $rule): static;

    public function removeRule(TargetGroupRuleInterface $rule): static;

    public function clearRules(): static;
}
