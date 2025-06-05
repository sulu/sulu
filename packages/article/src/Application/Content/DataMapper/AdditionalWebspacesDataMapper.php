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

namespace Sulu\Article\Application\Content\DataMapper;

use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Content\Application\ContentDataMapper\DataMapper\DataMapperInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Webmozart\Assert\Assert;

class AdditionalWebspacesDataMapper implements DataMapperInterface
{
    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): void {
        if (!$localizedDimensionContent instanceof AdditionalWebspacesInterface) {
            return;
        }

        $this->setAdditionalWebspacesData($localizedDimensionContent, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setAdditionalWebspacesData(AdditionalWebspacesInterface $dimensionContent, array $data): void
    {
        $customizeWebspaceSettings = false;

        if (\array_key_exists('customizeWebspaceSettings', $data)) {
            Assert::nullOrBoolean($data['customizeWebspaceSettings']);
            $customizeWebspaceSettings = (bool) $data['customizeWebspaceSettings'];
            $dimensionContent->setCustomizeWebspaceSettings($customizeWebspaceSettings);
        }

        // If customize is not activated, set both mainWebspace and additionalWebspaces to null
        if (!$customizeWebspaceSettings) {
            $dimensionContent->setMainWebspace(null);
            $dimensionContent->setAdditionalWebspaces(null);
            return;
        }

        // Only process additionalWebspaces if customize is activated
        if (\array_key_exists('additionalWebspaces', $data)) {
            Assert::nullOrIsArray($data['additionalWebspaces']);
            $additionalWebspaces = $data['additionalWebspaces'] ?? [];

            // Ensure all values are strings
            $additionalWebspaces = \array_filter($additionalWebspaces, static function ($webspace): bool {
                return \is_string($webspace) && '' !== $webspace;
            });

            $dimensionContent->setAdditionalWebspaces(\array_values($additionalWebspaces));
        }
    }
}
