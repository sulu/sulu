<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

/**
 * Blocks represents a choice of sub structures.
 *
 * @deprecated use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata instead
 *
 * TODO: Components are basically Snippets, but Snippets are loaded as Structures
 */
class BlockMetadata extends PropertyMetadata
{
    /**
     * @var ItemMetadata[]
     */
    public $components = [];

    /**
     * @var string
     */
    public $defaultComponentName;

    /**
     * Return the default component name.
     *
     * @return string
     */
    public function getDefaultComponentName()
    {
        return $this->defaultComponentName;
    }

    /**
     * Return the components.
     *
     * @return ItemMetadata[]
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * @param string $name
     *
     * @return ItemMetadata
     */
    public function getComponentByName($name)
    {
        foreach ($this->components as $component) {
            if ($component->getName() == $name) {
                return $component;
            }
        }
    }

    /**
     * Add a new component.
     *
     * @param ItemMetadata $component
     */
    public function addComponent(ItemMetadata $component)
    {
        $this->components[] = $component;
    }
}
