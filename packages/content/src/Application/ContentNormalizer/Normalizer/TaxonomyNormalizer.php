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

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;

class TaxonomyNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof TaxonomyInterface || !$object instanceof DimensionContentInterface) {
            return $normalizedData;
        }

        $normalizedData['excerptTags'] = $normalizedData['excerptTagIds'];
        unset($normalizedData['excerptTagIds']);
        unset($normalizedData['excerptTagNames']);
        $normalizedData['excerptCategories'] = $normalizedData['excerptCategoryIds'];
        unset($normalizedData['excerptCategoryIds']);
        $normalizedData['excerptAudienceTargetGroups'] = $normalizedData['excerptAudienceTargetGroupIds'] ?? [];
        unset($normalizedData['excerptAudienceTargetGroupIds']);

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof TaxonomyInterface) {
            return [];
        }

        return [
            'excerptTags',
            'excerptCategories',
            'excerptAudienceTargetGroups',
        ];
    }
}
