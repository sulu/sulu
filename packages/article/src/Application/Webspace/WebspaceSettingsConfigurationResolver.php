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

use Symfony\Component\Form\Exception\InvalidConfigurationException;

class WebspaceSettingsConfigurationResolver
{
    /**
     * @param array<string, string> $defaultMainWebspace
     * @param array<string, string[]> $defaultAdditionalWebspaces
     */
    public function __construct(
        private array $defaultMainWebspace,
        private array $defaultAdditionalWebspaces
    ) {
    }

    public function getDefaultMainWebspaceForLocale(string $searchedLocale): string
    {
        if (\array_key_exists($searchedLocale, $this->defaultMainWebspace)) {
            return $this->defaultMainWebspace[$searchedLocale];
        }

        if (\array_key_exists('default', $this->defaultMainWebspace)) {
            return $this->defaultMainWebspace['default'];
        }

        throw new InvalidConfigurationException('No configured default main webspace for locale "' . $searchedLocale . '" not found.');
    }

    /**
     * @return string[]
     */
    public function getDefaultAdditionalWebspacesForLocale(string $searchedLocale): array
    {
        if (\array_key_exists($searchedLocale, $this->defaultAdditionalWebspaces)) {
            return $this->defaultAdditionalWebspaces[$searchedLocale];
        }

        if (\array_key_exists('default', $this->defaultAdditionalWebspaces)) {
            return $this->defaultAdditionalWebspaces['default'];
        }

        return [];
    }
}
