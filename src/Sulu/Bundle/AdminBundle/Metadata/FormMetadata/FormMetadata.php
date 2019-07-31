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

use JMS\Serializer\Annotation\SerializedName;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class FormMetadata
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
     * @var ItemMetadata[]
     *
     * @SerializedName("form")
     */
    private $items;

    /**
     * @var SchemaMetadata
     */
    private $schema;

    /**
     * @var string
     */
    private $key;

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return ItemMetadata[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param ItemMetadata[] $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }

    public function addItem(ItemMetadata $item): void
    {
        $this->items[$item->getName()] = $item;
    }

    public function setSchema(SchemaMetadata $schema)
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
