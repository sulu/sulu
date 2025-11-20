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
        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof SeoInterface) {
            return [];
        }

        return [
            'seoData',
        ];
    }
}
