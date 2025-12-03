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

namespace Sulu\Article\Infrastructure\Sulu\Content\DataMapper;

use Sulu\Article\Application\Webspace\WebspaceSettingsConfigurationResolver;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentDataMapper\DataMapper\DataMapperInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Webmozart\Assert\Assert;

class AdditionalWebspacesDataMapper implements DataMapperInterface
{
    public function __construct(
        private readonly WebspaceSettingsConfigurationResolver $webspaceSettingsConfigurationResolver,
        private readonly WebspaceManagerInterface $webspaceManager,
    ) {
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): void {
        if (!$localizedDimensionContent instanceof ArticleDimensionContentInterface) {
            return;
        }

        $this->setAdditionalWebspacesData($localizedDimensionContent, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setAdditionalWebspacesData(ArticleDimensionContentInterface $dimensionContent, array $data): void
    {
        $customizeWebspaceSettings = false;

        if (\array_key_exists('customizeWebspaceSettings', $data)) {
            Assert::nullOrBoolean($data['customizeWebspaceSettings']);
            $customizeWebspaceSettings = (bool) $data['customizeWebspaceSettings'];
            $dimensionContent->setCustomizeWebspaceSettings($customizeWebspaceSettings);
        }

        // If customize is not activated, set main and additional webspaces to default values from the configuration.
        if (!$customizeWebspaceSettings) {
            $locale = (string) $dimensionContent->getLocale();
            $defaultMainWebspace = $this->webspaceSettingsConfigurationResolver->getDefaultMainWebspaceForLocale($locale);
            $additionalWebspaces = $this->webspaceSettingsConfigurationResolver->getDefaultAdditionalWebspacesForLocale($locale);
            $dimensionContent->setMainWebspace($defaultMainWebspace);
            $dimensionContent->setAdditionalWebspaces(\array_values($additionalWebspaces));
            $dimensionContent->setCustomizeWebspaceSettings($customizeWebspaceSettings);

            return;
        }

        // Only process additionalWebspaces if customize is activated
        if (\array_key_exists('additionalWebspaces', $data)) {
            Assert::nullOrIsArray($data['additionalWebspaces']);
            $additionalWebspaces = $data['additionalWebspaces'] ?? [];

            // Ensure all values are strings
            $additionalWebspaces = \array_filter($additionalWebspaces, static function($webspace): bool {
                return \is_string($webspace) && '' !== $webspace;
            });

            // Validate all webspaces support the current locale
            if (\count($additionalWebspaces) > 0) {
                $locale = (string) $dimensionContent->getLocale();
                foreach ($additionalWebspaces as $webspaceKey) {
                    $this->validateWebspaceSupportsLocale($webspaceKey, $locale);
                }
            }

            $dimensionContent->setAdditionalWebspaces(\array_values($additionalWebspaces));
        }
    }

    private function validateWebspaceSupportsLocale(string $webspaceKey, ?string $locale): void
    {
        if (!$locale) {
            return;
        }

        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        if (!$webspace) {
            throw new \InvalidArgumentException(\sprintf('Webspace "%s" not found', $webspaceKey));
        }

        if (!$webspace->getLocalization($locale)) {
            throw new \InvalidArgumentException(
                \sprintf('Webspace "%s" does not support locale "%s"', $webspaceKey, $locale)
            );
        }
    }
}
