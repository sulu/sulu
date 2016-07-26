<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * Container for property-metadata.
 */
class PropertyMetadata extends BasePropertyMetadata
{
    /**
     * @var BasePropertyMetadata[]
     */
    protected $metadata = [];

    /**
     * @return BasePropertyMetadata[]
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param string $name
     * @param BasePropertyMetadata $metadata
     */
    public function addMetadata($name, $metadata)
    {
        $this->metadata[$name] = $metadata;
    }

    /**
     * @param string $name
     *
     * @return BasePropertyMetadata
     */
    public function get($name)
    {
        return $this->metadata[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->class,
                $this->name,
                $this->metadata,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list($this->class,
            $this->name,
            $this->metadata) = unserialize($str);

        $this->reflection = new \ReflectionProperty($this->class, $this->name);
        $this->reflection->setAccessible(true);
    }
}
