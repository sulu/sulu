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

namespace Sulu\Content\Application\ContentAggregator;

use Doctrine\ORM\PersistentCollection;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentAggregator implements ContentAggregatorInterface
{
    public function __construct(
        private ContentMergerInterface $contentMerger,
        private bool $debug = false,
    ) {
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param array<string, mixed> $dimensionAttributes
     *
     * @return T
     */
    public function aggregate(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface
    {
        $dimensionContents = $contentRichEntity->getDimensionContents();

        if ($this->debug && $dimensionContents instanceof PersistentCollection && !$dimensionContents->isInitialized()) {
            throw new \RuntimeException(\sprintf(
                'Dimension contents must be eager-loaded before calling aggregate(). '
                . 'Entity "%s" has uninitialized dimension contents. '
                . 'Fix: Add ->leftJoin(\'entity.dimensionContents\', \'dimensionContent\') to your query. '
                . 'This error only appears in debug mode - production will fall back to lazy loading '
                . 'but with worse performance.',
                $contentRichEntity::class
            ));
        }

        $dimensionContentClass = $contentRichEntity->createDimensionContent()::class;

        $dimensionContentCollection = new DimensionContentCollection(
            $dimensionContents,
            $dimensionAttributes,
            $dimensionContentClass
        );

        if (0 === \count($dimensionContentCollection)) {
            throw new ContentNotFoundException($contentRichEntity, $dimensionAttributes);
        }

        return $this->contentMerger->merge($dimensionContentCollection);
    }
}
