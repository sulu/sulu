<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Sulu\Component\Content\Metadata;

/**
 * Represents a navigation context defined in webspace xml
 */
class NavigationContext
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $key;

    public function __construct($key, $data)
    {
        $this->key = $key;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $locale
     * @return null|string
     */
    public function getTitle($locale)
    {
        if (isset($this->data[$locale]['title'])) {
            return $this->data[$locale]['title'];
        }

        return ucfirst($this->key);
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->data;
    }
}
