<?php

declare(strict_types=1);

namespace Sulu\Component\Content\Types\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class GlobalBlocksTypedFormMetadataVisitor implements TypedFormMetadataVisitorInterface
{
    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
    ) {
    }

    private ?TypedFormMetadata $globalBlocksMetadata = null;

    private function getGlobalBlockMetadata(string $name, string $locale): ?FormMetadata
    {
        if (null === $this->globalBlocksMetadata) {
            $this->globalBlocksMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
                ->getMetadata('block', $locale, []);
        }

        return $this->globalBlocksMetadata->getForms()[$name] ?? null;
    }

    public function visitTypedFormMetadata(
        TypedFormMetadata $formMetadata,
        string $key,
        string $locale,
        array $metadataOptions = [],
    ): void {
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
            foreach ($item->getTypes() as $type) {
                $globalBlockTag = $type->getTagsByName('sulu.global_block')[0] ?? null;
                if (!$globalBlockTag) {
                    continue;
                }

                $globalBlockType = $globalBlockTag->getAttribute('global_block');
                $blockMetadata = $this->getGlobalBlockMetadata($globalBlockType, $locale);
                $type->setTitle($blockMetadata->getTitle());

                $rootSchema->addDefinition($blockMetadata->getName(), $blockMetadata->getSchema());

                $this->enhanceGlobalBlockTypes($type->getItems(), $locale, $rootSchema);
            }
        }
    }
}
