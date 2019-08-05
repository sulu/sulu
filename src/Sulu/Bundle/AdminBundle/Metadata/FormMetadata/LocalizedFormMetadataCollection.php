<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

/**
 * Collection to store the localized FormMetadata objects in each locale.
 */
class LocalizedFormMetadataCollection
{
    /**
     * @var FormMetadata[]
     */
    private $items = [];

    public function add($locale, $formMetadata)
    {
        $this->items[$locale] = $formMetadata;
    }

    public function get($locale): FormMetadata
    {
        if (!isset($this->items[$locale])) {
            throw new \InvalidArgumentException(sprintf('Locale "%s" does not exist in collection.', $locale));
        }

        return $this->items[$locale];
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function merge(self $otherFormCollection): LocalizedFormMetadataCollection
    {
        $mergedFormCollection = new self();
        foreach ($this->items as $locale => $item) {
            if (isset($otherFormCollection->getItems()[$locale])) {
                $mergedFormCollection->add($locale, $item->merge($otherFormCollection->get($locale)));
            }
        }

        return $mergedFormCollection;
    }
}
