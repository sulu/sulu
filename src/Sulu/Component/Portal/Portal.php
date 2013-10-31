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

    /**
     * Adds the given language to the portal
     * @param Language $language
     */
    public function addLanguage(Language $language) {
        $this->languages[] = $language;
    }

    /**
     * Sets the languages to this portal
     * @param \Sulu\Component\Portal\Language $languages The language to add
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }

    /**
     * Returns the languages of this portal
     * @return \Sulu\Component\Portal\Language[] The languages of this portal
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * Sets the theme for this portal
     * @param \Sulu\Component\Portal\Theme $theme
     */
    public function setTheme(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * Returns the theme for this portal
     * @return \Sulu\Component\Portal\Theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Adds an environment to this portal
     * @param $environment The environment to add
     */
    public function addEnvironment($environment)
    {
        $this->environments[] = $environment;
    }

    /**
     * Sets the environments for this portal
     * @param \Sulu\Component\Portal\Environment[] $environments
     */
    public function setEnvironments($environments)
    {
        $this->environments = $environments;
    }

    /**
     * Returns the environment for this portal
     * @return \Sulu\Component\Portal\Environment[]
     */
    public function getEnvironments()
    {
        return $this->environments;
    }
}
