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

use Sulu\Content\Domain\Model\AuditableInterface;

class AuditableNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof AuditableInterface) {
            return $normalizedData;
        }

        $normalizedData['changer'] = $object->getChanger()?->getId();
        $normalizedData['creator'] = $object->getCreator()?->getId();

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof AuditableInterface) {
            return [];
        }

        return ['changer', 'creator'];
    }
}
