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

use JMS\Serializer\Annotation\SerializedName;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;

class Form
{
    /**
     * @var Item[]
     *
     * @SerializedName("form")
     */
    private $items;

    /**
     * @var array
     */
    private $schema;

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

    public function setSchema(array $schema)
    {
        $this->schema = $schema;
    }
}
