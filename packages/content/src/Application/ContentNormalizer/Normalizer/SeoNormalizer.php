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

namespace Sulu\Content\Application\ContentNormalizer\Normalizer;

use Sulu\Content\Domain\Model\SeoInterface;

class SeoNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof SeoInterface) {
            return $normalizedData;
        }

        $seoData = $object->getSeoData();
        if ([] !== $seoData) {
            $normalizedData['seo'] = $seoData;
        }

        // Add boolean fields (they have dedicated columns, not in nested structure)
        $normalizedData['seoHideInSitemap'] = $object->getSeoHideInSitemap();
        $normalizedData['seoNoFollow'] = $object->getSeoNoFollow();
        $normalizedData['seoNoIndex'] = $object->getSeoNoIndex();

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof SeoInterface) {
            return [];
        }

        // Ignore raw seoData and individual getter fields (stored in nested structure)
        return [
            'seoData',
            'seoTitle',
            'seoDescription',
            'seoKeywords',
            'seoCanonicalUrl',
        ];
    }
}
