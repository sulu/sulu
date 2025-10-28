<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadataVisitorInterface;

/**
 * Automatically enables block ID generation for page, article, and snippet templates.
 *
 * @internal
 */
class BlockIdGeneratorFormMetadataVisitor implements TypedFormMetadataVisitorInterface
{
    public function visitTypedFormMetadata(TypedFormMetadata $formMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        foreach ($formMetadata->getForms() as $formMetadata) {
            $this->enhanceBlockMetadata($formMetadata->getItems());
        }
    }

    /**
     * @param ItemMetadata[] $itemsMetadata
     */
    private function enhanceBlockMetadata(array $itemsMetadata): void
    {
        foreach ($itemsMetadata as $itemMetadata) {
            if ($itemMetadata instanceof SectionMetadata) {
                $this->enhanceBlockMetadata($itemMetadata->getItems());

                continue;
            }

            if (!$itemMetadata instanceof FieldMetadata) {
                continue;
            }

            foreach ($itemMetadata->getTypes() as $type) {
                $this->enhanceBlockMetadata($type->getItems());
            }

            if ('block' !== $itemMetadata->getType()
                || \array_key_exists('block_id_generator', $itemMetadata->getOptions())
            ) {
                continue;
            }

            $optionMetadata = new OptionMetadata();
            $optionMetadata->setName('block_id_generator');
            $optionMetadata->setValue(true);
            $itemMetadata->addOption($optionMetadata);
        }
    }
}
