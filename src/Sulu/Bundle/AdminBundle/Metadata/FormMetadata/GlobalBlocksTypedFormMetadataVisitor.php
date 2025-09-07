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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
class GlobalBlocksTypedFormMetadataVisitor implements TypedFormMetadataVisitorInterface, FormMetadataVisitorInterface
{
    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
    ) {
    }

    private ?TypedFormMetadata $globalBlocksMetadata = null;

    public function visitTypedFormMetadata(
        TypedFormMetadata $formMetadata,
        string $key,
        string $locale,
        array $metadataOptions = [],
    ): void {
        if ($metadataOptions['ignore_global_blocks'] ?? false) {
            return;
        }

        foreach ($formMetadata->getForms() as $form) {
            $this->enhanceGlobalBlockTypes($form->getItems(), $locale, $form->getSchema());
        }
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function enhanceGlobalBlockTypes(array $items, string $locale, SchemaMetadata $rootSchema): void
    {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $this->enhanceGlobalBlockTypes($item->getItems(), $locale, $rootSchema);

                continue;
            }

            if (!$item instanceof FieldMetadata) {
                continue;
            }

            foreach ($item->getTypes() as $type) {
                $globalBlockTag = $type->getTagsByName('sulu.global_block')[0] ?? null;
                if (!$globalBlockTag) {
                    $this->enhanceGlobalBlockTypes($type->getItems(), $locale, $rootSchema);

                    continue;
                }

                $globalBlockType = $globalBlockTag->getAttribute('global_block');
                \assert(\is_string($globalBlockType), 'The "global_block" attribute of the "sulu.global_block" tag must be a string' . \get_debug_type($globalBlockType) . ' given.');
                $blockMetadata = $this->getGlobalBlockMetadata($globalBlockType, $locale);
                if (!$blockMetadata) {
                    continue;
                }

                $type->setTitles($blockMetadata->getTitles());

                $rootSchema->addDefinition($blockMetadata->getKey(), $blockMetadata->getSchema());

                $this->enhanceGlobalBlockTypes($blockMetadata->getItems(), $locale, $rootSchema);
            }
        }
    }

    private function getGlobalBlockMetadata(string $name, string $locale): ?FormMetadata
    {
        if (null === $this->globalBlocksMetadata) {
            /** @var TypedFormMetadata $globalBlocksMetadata */
            $globalBlocksMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
                ->getMetadata('block', $locale, ['ignore_global_blocks' => true]);

            $this->globalBlocksMetadata = $globalBlocksMetadata;
        }

        return $this->globalBlocksMetadata->getForms()[$name] ?? null;
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ($metadataOptions['ignore_global_blocks'] ?? false) {
            return;
        }

        $this->enhanceGlobalBlockTypes($formMetadata->getItems(), $locale, $formMetadata->getSchema());
    }
}
