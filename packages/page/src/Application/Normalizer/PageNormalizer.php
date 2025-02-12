<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\Normalizer;

use Sulu\Content\Application\ContentNormalizer\Normalizer\NormalizerInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;

class PageNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof PageDimensionContentInterface) {
            return $normalizedData;
        }

        $normalizedData['id'] = $object->getResource()->getUuid();
        $normalizedData['webspace'] = $object->getResource()->getWebspaceKey();

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if ($object instanceof PageDimensionContentInterface) {
            return [
                'mainWebspace',
            ];
        }

        return [];
    }
}
