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

namespace Sulu\Article\Application\Content\Normalizer;

use Sulu\Article\Application\Webspace\WebspaceResolver;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Content\Application\ContentNormalizer\Normalizer\NormalizerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class AdditionalWebspacesNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly WebspaceResolver $webspaceResolver,
    ) {
    }

    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof AdditionalWebspacesInterface || $object->getCustomizeWebspaceSettings()) {
            return $normalizedData;
        }

        $locale = $object instanceof DimensionContentInterface ? $object->getLocale() : null;

        if ($locale) {
            $normalizedData['mainWebspace'] = $this->webspaceResolver->resolveMainWebspace($object, $locale);
            $normalizedData['additionalWebspaces'] = $this->webspaceResolver->resolveAdditionalWebspaces($object, $locale);
        } else {
            // Fallback if no locale is available
            $normalizedData['mainWebspace'] = null;
            $normalizedData['additionalWebspaces'] = [];
        }

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof AdditionalWebspacesInterface) {
            return [];
        }

        // Don't ignore any attributes - we want to enhance the existing data
        return [];
    }
}
