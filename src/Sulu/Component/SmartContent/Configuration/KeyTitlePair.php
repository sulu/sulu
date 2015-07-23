<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

/**
 * Provides a key value pair.
 */
class KeyTitlePair implements KeyTitlePairInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $titles;

    public function __construct($key, array $titles)
    {
        $this->key = $key;
        $this->titles = $titles;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($locale)
    {
        if (!array_key_exists($locale, $this->titles)) {
            return array_values($this->titles)[0];
        }

        return $this->titles[$locale];
    }

    /**
     * @param array $titles
     */
    public function setTitles($titles)
    {
        $this->titles = $titles;
    }
}
