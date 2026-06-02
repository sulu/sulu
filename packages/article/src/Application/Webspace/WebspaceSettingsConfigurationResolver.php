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

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class WebspaceSettingsConfigurationResolver
{
    /**
     * @param array<string, string> $defaultMainWebspace
     * @param array<string, string[]> $defaultAdditionalWebspaces
     */
    public function __construct(
        private array $defaultMainWebspace,
        private array $defaultAdditionalWebspaces,
        private readonly WebspaceManagerInterface $webspaceManager,
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

        $webspaces = $this->webspaceManager->getWebspaceCollection()->getWebspaces();
        if (1 === \count($webspaces)) {
            return \reset($webspaces)->getKey();
        }

        throw new \RuntimeException(\sprintf(
            'No default main webspace configured for locale "%s". When more than one webspace exists, the '
            . '"sulu_article.default_main_webspace" option must be set to one of your webspace keys '
            . '(optionally per locale).',
            $searchedLocale,
        ));
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
