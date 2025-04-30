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

class SectionMetadata extends ItemMetadata
{
    /**
     * @var ItemMetadata[]
     */
    protected $items = [];

    protected $type = 'section';

    /**
     * @return ItemMetadata[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(ItemMetadata $item): void
    {
        $this->items[$item->getName()] = $item;
    }

    /**
     * @internal no backwards compatibility promise is given for this method it could be removed or changed at any time
     *
     * @return FieldMetadata[]
     */
    public function getFlatFieldMetadata(): array
    {
        return $this->doFlatItems($this);
    }

    /**
     * @return FieldMetadata[]
     */
    private function doFlatItems(SectionMetadata $metadata): array
    {
        $items = [];
        foreach ($metadata->getItems() as $item) {
            if ($item instanceof SectionMetadata) {
                foreach ($this->doFlatItems($item) as $subItem) {
                    $items[] = $subItem;
                }
            } elseif ($item instanceof FieldMetadata) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
