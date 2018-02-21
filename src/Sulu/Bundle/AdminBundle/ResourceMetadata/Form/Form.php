<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata\Form;

use JMS\Serializer\Annotation as Serializer;

class Form
{
    /**
     * @var Item[]
     *
     * @Serializer\Inline()
     */
    protected $items;

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(Item $item): void
    {
        $this->items[$item->getName()] = $item;
    }
}
