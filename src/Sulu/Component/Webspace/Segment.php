<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Represents the segments defined in a webspace.
 */
class Segment implements ArrayableInterface
{
    /**
     * The key of the segment.
     *
     * @var string
     */
    private $key;

    /**
     * Defines if this segment is the default one.
     *
     * @var bool
     */
    private $default;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * Sets the key of the segment.
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of the segment.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets if this segment is the default one.
     *
     * @param bool $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * Returns whether this segment is the default one.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    public function setMetadata($metadata)
    {
        $this->metadata = new Metadata($metadata);
    }

    public function getMetadata()
    {
        return $this->metadata->getData();
    }

    /**
     * @param string $locale
     *
     * @return null|string
     */
    public function getTitle($locale)
    {
        return $this->metadata->get('title', $locale, \ucfirst($this->key));
    }

    public function toArray($depth = null)
    {
        $res = [];
        $res['key'] = $this->getKey();
        $res['default'] = $this->isDefault();
        $res['metadata'] = $this->getMetadata();

        return $res;
    }
}
