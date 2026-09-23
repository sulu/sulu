<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\MediaBundle\Media\MediaLanguage\MediaLanguageProvider;

/**
 * @internal This class is internal. Create a separate visitor if you want to manipulate the metadata in your project.
 */
class MediaLanguageFormMetadataVisitor implements FormMetadataVisitorInterface
{
    public function __construct(
        private MediaLanguageProvider $mediaLanguageProvider,
    ) {
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ('media_details' !== $formMetadata->getKey()) {
            return;
        }

        // the field is nested inside a section, so the top-level items are sections, not fields
        $mediaLanguagesField = $this->findField($formMetadata->getItems(), 'mediaLanguages');
        if (!$mediaLanguagesField instanceof FieldMetadata) {
            return;
        }

        $valuesOption = new OptionMetadata();
        $valuesOption->setName('values');
        $valuesOption->setType('collection');

        $options = [];
        foreach ($this->mediaLanguageProvider->getLanguageNames($locale) as $code => $name) {
            $option = new OptionMetadata();
            $option->setName($code);
            $option->setValue($code);
            $option->setTitle($name, $locale);
            $options[] = $option;
        }
        $valuesOption->setValue($options);

        $mediaLanguagesField->addOption($valuesOption);
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function findField(array $items, string $name): ?FieldMetadata
    {
        foreach ($items as $item) {
            if ($item instanceof FieldMetadata && $name === $item->getName()) {
                return $item;
            }

            if ($item instanceof SectionMetadata) {
                $field = $this->findField($item->getItems(), $name);
                if (null !== $field) {
                    return $field;
                }
            }
        }

        return null;
    }
}
