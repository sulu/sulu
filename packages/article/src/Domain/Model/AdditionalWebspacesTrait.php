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

namespace Sulu\Article\Domain\Model;

trait AdditionalWebspacesTrait
{
    /**
     * @var bool
     */
    private $customizeWebspaceSettings = false;

    /**
     * @var string[]|null
     */
    private $additionalWebspaces;

    public function getCustomizeWebspaceSettings(): bool
    {
        return $this->customizeWebspaceSettings;
    }

    public function setCustomizeWebspaceSettings(bool $customizeWebspaceSettings): void
    {
        $this->customizeWebspaceSettings = $customizeWebspaceSettings;
    }

    public function getAdditionalWebspaces(): ?array
    {
        return $this->additionalWebspaces;
    }

    public function setAdditionalWebspaces(?array $additionalWebspaces): void
    {
        $this->additionalWebspaces = $additionalWebspaces;
    }

    public function addAdditionalWebspace(string $webspace): void
    {
        if (null === $this->additionalWebspaces) {
            $this->additionalWebspaces = [];
        }

        if (!\in_array($webspace, $this->additionalWebspaces, true)) {
            $this->additionalWebspaces[] = $webspace;
        }
    }

    public function removeAdditionalWebspace(string $webspace): void
    {
        if (null === $this->additionalWebspaces) {
            return;
        }

        $key = \array_search($webspace, $this->additionalWebspaces, true);
        if (false !== $key) {
            unset($this->additionalWebspaces[$key]);
            $this->additionalWebspaces = \array_values($this->additionalWebspaces);
        }
    }
}