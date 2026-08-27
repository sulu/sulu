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

namespace Sulu\Content\Infrastructure\Sulu\Preview;

use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal
 *
 * @template-covariant T of DimensionContentInterface
 *
 * @implements DimensionContentCollectionInterface<T>
 */
class PreviewDimensionContentCollection implements DimensionContentCollectionInterface
{
    /**
     * @var T
     */
    private readonly DimensionContentInterface $unlocalizedDimensionContent;
    /**
     * @var T
     */
    private readonly DimensionContentInterface $localizedDimensionContent;

    /**
     * @param T $previewDimensionContent
     */
    public function __construct(
        private DimensionContentInterface $previewDimensionContent,
        private string $previewLocale
    ) {
        $this->localizedDimensionContent = clone $previewDimensionContent;
        $this->unlocalizedDimensionContent = clone $previewDimensionContent;
    }

    public function getDimensionContentClass(): string
    {
        return \get_class($this->previewDimensionContent);
    }

    public function getDimensionContent(array $dimensionAttributes): ?DimensionContentInterface
    {
        if (($dimensionAttributes['locale'] ?? null) === null) {
            return $this->unlocalizedDimensionContent;
        } else {
            return $this->localizedDimensionContent;
        }
    }

    public function getDimensionAttributes(): array
    {
        return \array_merge(
            $this->previewDimensionContent::getDefaultDimensionAttributes(),
            ['locale' => $this->previewLocale]
        );
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            $this->previewDimensionContent,
            $this->localizedDimensionContent,
            $this->unlocalizedDimensionContent,
        ]);
    }

    public function count(): int
    {
        return 3;
    }
}
