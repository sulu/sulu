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

namespace Sulu\Article\Application\Webspace;

use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;

class WebspaceResolver
{
    private WebspaceManagerInterface $webspaceManager;

    private WebspaceSettingsConfigurationResolver $webspaceSettingsConfigurationResolver;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        WebspaceSettingsConfigurationResolver $webspaceSettingsConfigurationResolver
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->webspaceSettingsConfigurationResolver = $webspaceSettingsConfigurationResolver;
    }

    public function resolveMainWebspace(WebspaceInterface $dimensionContent, string $locale): ?string
    {
        if (!$this->hasMoreThanOneWebspace()) {
            $webspaces = $this->webspaceManager->getWebspaceCollection()->getWebspaces();
            $firstWebspace = \reset($webspaces);

            return $firstWebspace ? $firstWebspace->getKey() : null;
        }

        $hasCustomizedWebspaceSettings = $this->hasCustomizedWebspaceSettings($dimensionContent);

        if ($hasCustomizedWebspaceSettings) {
            return $dimensionContent->getMainWebspace();
        }

        return $this->webspaceSettingsConfigurationResolver->getDefaultMainWebspaceForLocale($locale);
    }

    /**
     * @return string[]|null
     */
    public function resolveAdditionalWebspaces(AdditionalWebspacesInterface $dimensionContent, string $locale): ?array
    {
        if (!$this->hasMoreThanOneWebspace()) {
            return [];
        }

        $hasCustomizedWebspaceSettings = $this->hasCustomizedWebspaceSettings($dimensionContent);

        if ($hasCustomizedWebspaceSettings) {
            return $dimensionContent->getAdditionalWebspaces() ?? [];
        }

        return $this->webspaceSettingsConfigurationResolver->getDefaultAdditionalWebspacesForLocale($locale);
    }

    public function hasCustomizedWebspaceSettings(WebspaceInterface $dimensionContent): bool
    {
        if (!$dimensionContent instanceof AdditionalWebspacesInterface) {
            return false;
        }

        return $dimensionContent->getCustomizeWebspaceSettings();
    }

    /**
     * Check if system has more than one webspace.
     */
    private function hasMoreThanOneWebspace(): bool
    {
        return \count($this->webspaceManager->getWebspaceCollection()->getWebspaces()) > 1;
    }
}
