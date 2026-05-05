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

use Sulu\Component\Hash\HasherInterface;
use Sulu\Content\Domain\Model\AuditableInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentHashNormalizer implements NormalizerInterface
{
    public function __construct(
        private HasherInterface $hasher,
    ) {
    }

    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof DimensionContentInterface || !$object instanceof AuditableInterface) {
            return $normalizedData;
        }

        $normalizedData['_hash'] = $this->hasher->hash($object);

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        return [];
    }
}
