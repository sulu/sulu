<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\Normalizer;

use Sulu\Content\Application\ContentNormalizer\Normalizer\NormalizerInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;

class PageNormalizer implements NormalizerInterface
{
    public function getIgnoredAttributes(object $object): array
    {
        return [];
    }

    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof PageDimensionContentInterface) {
            return $normalizedData;
        }

        /** @var PageInterface $page */
        $page = $object->getResource();

        $normalizedData['webspace'] = $page->getWebspaceKey();

        return $normalizedData;
    }
}
