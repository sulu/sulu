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

namespace Sulu\Page\Infrastructure\Sulu\Content\Normalizer;

use Sulu\Content\Application\ContentNormalizer\Normalizer\NormalizerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;
use Sulu\Page\Domain\Model\PageInterface;

class PageExcerptNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof TaxonomyInterface || !$object instanceof DimensionContentInterface) {
            return $normalizedData;
        }

        $resource = $object->getResource();
        if ($resource instanceof PageInterface) {
            $normalizedData['excerptSegment'] = null !== $object->getExcerptSegment() ? [
                $resource->getWebspaceKey() => $object->getExcerptSegment(),
            ] : null;
        }

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        return [];
    }
}
