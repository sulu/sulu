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

    public function getTargetWebspace(string $currentWebspaceKey): string
    {
        if ($this->getMainWebspace() === $currentWebspaceKey
            || (
                $this->getAdditionalWebspaces()
                && \in_array($currentWebspaceKey, $this->getAdditionalWebspaces(), true)
            )
        ) {
            return $currentWebspaceKey;
        }

        return $this->getMainWebspace() ?? '';
    }
}
