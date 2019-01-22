<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\Form;

use JMS\Serializer\Annotation\SerializedName;
use Sulu\Bundle\AdminBundle\Metadata\Schema\Schema;

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
     * @var Schema
     */
    private $schema;

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
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

    /**
     * @param Item[] $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }

    public function addItem(Item $item): void
    {
        $this->items[$item->getName()] = $item;
    }

    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function merge(self $otherForm)
    {
        $mergedForm = new self();
        if ($this->name) {
            $mergedForm->setName($this->name);
        }
        if ($this->title) {
            $mergedForm->setTitle($this->title);
        }

        $mergedForm->setItems(array_merge($this->getItems(), $otherForm->getItems()));
        $mergedForm->setSchema($this->getSchema()->merge($otherForm->getSchema()));

        return $mergedForm;
    }
}
