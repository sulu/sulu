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

class Form
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $title;

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

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

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
