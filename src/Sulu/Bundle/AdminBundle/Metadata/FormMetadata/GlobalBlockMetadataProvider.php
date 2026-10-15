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

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;

/**
 * A block type that references a global block carries no fields of its own: its metadata is the one of the global block.
 *
 * @internal
 */
class GlobalBlockMetadataProvider
{
    /**
     * @var array<string, array<FormMetadata>>
     */
    private array $globalBlocks = [];

    public function __construct(
        private readonly MetadataProviderRegistry $metadataProviderRegistry,
    ) {
    }

    /**
     * Returns the global block the block type references, or the block type itself when it references none.
     */
    public function getMetadata(FormMetadata $blockType, string $locale): FormMetadata
    {
        $tag = $blockType->getTagsByName('sulu.global_block')[0] ?? null;
        $globalBlockType = $tag?->getAttribute('global_block');

        if (!\is_string($globalBlockType)) {
            return $blockType;
        }

        return $this->getGlobalBlocks($locale)[$globalBlockType] ?? $blockType;
    }

    /**
     * @return array<FormMetadata>
     */
    private function getGlobalBlocks(string $locale): array
    {
        if (!isset($this->globalBlocks[$locale])) {
            $typedFormMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
                ->getMetadata('block', $locale, []);

            \assert($typedFormMetadata instanceof TypedFormMetadata, 'Block form metadata not found for block resolving.');

            $this->globalBlocks[$locale] = $typedFormMetadata->getForms();
        }

        return $this->globalBlocks[$locale];
    }
}
