<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace;

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
     * The url generation strategy for this portal
     * @var string
     */
    private $resourceLocatorStrategy;

    /**
     * An array of localizations
     * @var Localization[]
     */
    private $localizations;

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
     * @param string $resourceLocatorStrategy
     */
    public function setResourceLocatorStrategy($resourceLocatorStrategy)
    {
        $this->resourceLocatorStrategy = $resourceLocatorStrategy;
    }

    /**
     * @return string
     */
    public function getResourceLocatorStrategy()
    {
        return $this->resourceLocatorStrategy;
    }

    /**
     * Adds the given language to the portal
     * @param Localization $localization
     */
    public function addLocalization(Localization $localization) {
        $this->localizations[] = $localization;
    }

    /**
     * Sets the localizations to this portal
     * @param \Sulu\Component\Workspace\Localization[] $localizations
     */
    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;
    }

    /**
     * Returns the languages of this portal
     * @return \Sulu\Component\Workspace\Localization[] The languages of this portal
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * Sets the theme for this portal
     * @param \Sulu\Component\Workspace\Theme $theme
     */
    public function setTheme(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * Returns the theme for this portal
     * @return \Sulu\Component\Workspace\Theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Adds an environment to this portal
     * @param $environment Environment The environment to add
     */
    public function addEnvironment($environment)
    {
        $this->environments[] = $environment;
    }

    /**
     * Sets the environments for this portal
     * @param \Sulu\Component\Workspace\Environment[] $environments
     */
    public function setEnvironments($environments)
    {
        $this->environments = $environments;
    }

    /**
     * Returns the environment for this portal
     * @return \Sulu\Component\Workspace\Environment[]
     */
    public function getEnvironments()
    {
        return $this->environments;
    }
}
