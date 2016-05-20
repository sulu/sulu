<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Sulu\Component\Util\ArrayableInterface;

class Theme implements ArrayableInterface
{
    /**
     * The key of the theme.
     *
     * @var string
     */
    private $key;

    /**
     * Theme constructor.
     *
     * @param string $key
     */
    public function __construct($key = null)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of the theme.
     *
     * @return string The key of the theme
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        return [
            'key' => $this->getKey(),
        ];
    }
}
