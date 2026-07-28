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

namespace Sulu\Content\Application\ContentHash;

use Sulu\Component\Hash\HasherInterface;
use Sulu\Component\Rest\Exception\InvalidHashException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentHashChecker
{
    public function __construct(
        private HasherInterface $hasher,
    ) {
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param mixed[] $data
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     * @param int|string $identifier
     */
    public function checkHash(array $data, ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes, $identifier): void
    {
        $expectedHash = $data['_hash'] ?? null;
        // No hash in the request means the check is skipped (e.g. when ?force=true strips _hash from data).
        if (!\is_string($expectedHash)) {
            return;
        }

        /** @var class-string<T>&literal-string $dimensionContentClass */
        $dimensionContentClass = $contentRichEntity->createDimensionContent()::class;
        $dimensionAttributes = $dimensionContentClass::getEffectiveDimensionAttributes($dimensionAttributes);
        $dimensionContent = (new DimensionContentCollection(
            $contentRichEntity->getDimensionContents(),
            $dimensionAttributes,
            $dimensionContentClass,
        ))->getDimensionContent($dimensionAttributes);

        if (!$dimensionContent instanceof DimensionContentInterface) {
            // No persisted content for this locale yet, so there is nothing to lock against.
            return;
        }

        if ($expectedHash !== $this->hasher->hash($dimensionContent)) {
            throw new InvalidHashException($contentRichEntity::class, $identifier);
        }
    }
}
