<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

/**
 * Container for a portal configuration
 * @package Sulu\Component\Portal
 */
class Portal
{
    /**
     * The name of the portal
     * @var string
     */
    private $name;

    /**
     * The unique key of the portal
     * @var string
     */
    private $key;

    /**
     * An array of languages
     * @var Language[]
     */
    private $languages;

    /**
     * The theme of the portal
     * @var Theme
     */
    private $theme;

    /**
     * @var Environment[]
     */
    private $environments;

    /**
     * Sets the unique key of the portal
     * @param string $key The unique key of the portal
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the unique key of the portal
     * @return string The unique key of the portal
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets the name of the portal
     * @param string $name The name of the portal
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the portal
     * @return string The name of the portal
     */
    public function getName()
    {
        return $this->name;
    }


}
