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

use Sulu\Content\Domain\Model\ExcerptInterface;

class ExcerptNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof ExcerptInterface) {
            return $normalizedData;
        }

        $excerptData = $object->getExcerptData();
        if (isset($excerptData['excerpt'])) {
            $normalizedData['excerpt'] = $excerptData['excerpt'];
        }

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof ExcerptInterface) {
            return [];
        }

        // Ignore raw excerptData and individual getter fields (stored in nested structure)
        return [
            'excerptData',
            'excerptTitle',
            'excerptDescription',
            'excerptMore',
            'excerptImage',
            'excerptIcon',
        ];
    }
}
