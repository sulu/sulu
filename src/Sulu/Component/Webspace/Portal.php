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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Exception\EnvironmentNotFoundException;
use Sulu\Component\Webspace\Exception\PortalLocalizationNotFoundException;

/**
 * Container for a portal configuration.
 *
 * @phpstan-import-type LocalizationArray from Localization
 * @phpstan-import-type EnvironmentArray from Environment
 */
class Portal
{
    /**
     * The name of the portal.
     *
     * @var string
     */
    private $name;

    /**
     * The key of the portal.
     *
     * @var string
     */
    private $key;

    /**
     * An array of localizations.
     *
     * @var Localization[]
     */
    private $localizations;

    /**
     * The default localization for this portal.
     *
     * @var Localization
     */
    private $defaultLocalization;

    /**
     * @var Environment[]
     */
    private $environments;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * Sets the name of the portal.
     *
     * @param string $name The name of the portal
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the portal.
     *
     * @return string The name of the portal
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Adds the given language to the portal.
     *
     * @return void
     */
    public function addLocalization(Localization $localization)
    {
        $this->localizations[] = $localization;

        if ($localization->isDefault()) {
            $this->setDefaultLocalization($localization);
        }
    }

    /**
     * Sets the localizations to this portal.
     *
     * @param Localization[] $localizations
     *
     * @return void
     */
    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;
    }

    /**
     * Returns the languages of this portal.
     *
     * @return Localization[] The languages of this portal
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * @return Localization
     */
    public function getLocalization($locale)
    {
        foreach ($this->getLocalizations() as $localization) {
            if ($locale === $localization->getLocale()) {
                return $localization;
            }
        }

        throw new PortalLocalizationNotFoundException($this, $locale);
    }

    /**
     * @param Localization $defaultLocalization
     *
     * @return void
     */
    public function setDefaultLocalization($defaultLocalization)
    {
        $this->defaultLocalization = $defaultLocalization;
    }

    /**
     * @return Localization
     */
    public function getDefaultLocalization()
    {
        return $this->defaultLocalization;
    }

    /**
     * Adds an environment to this portal.
     *
     * @param Environment $environment Environment The environment to add
     *
     * @return void
     */
    public function addEnvironment($environment)
    {
        $this->environments[$environment->getType()] = $environment;
    }

    /**
     * Sets the environments for this portal.
     *
     * @param Environment[] $environments
     *
     * @return void
     */
    public function setEnvironments(array $environments)
    {
        $this->environments = [];

        foreach ($environments as $environment) {
            $this->addEnvironment($environment);
        }
    }

    /**
     * Returns the environment for this portal.
     *
     * @return Environment[]
     */
    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * Returns the environment with the given type, and throws an exception if the environment does not exist.
     *
     * @param string $type
     *
     * @return Environment
     *
     * @throws EnvironmentNotFoundException
     */
    public function getEnvironment($type)
    {
        if (!isset($this->environments[$type])) {
            throw new EnvironmentNotFoundException($this, $type);
        }

        return $this->environments[$type];
    }

    /**
     * @return void
     */
    public function setWebspace(Webspace $webspace)
    {
        $this->webspace = $webspace;
    }

    /**
     * @return Webspace
     */
    public function getWebspace()
    {
        return $this->webspace;
    }

    /**
     * @return array{name: string, key: string, localizations: array<int, LocalizationArray>, environments?: array<int,
     * EnvironmentArray>}
     */
    public function toArray($depth = null)
    {
        $res = [];
        $res['name'] = $this->getName();
        $res['key'] = $this->getKey();

        $res['localizations'] = [];

        foreach ($this->getLocalizations() as $localization) {
            $res['localizations'][] = $localization->toArray();
        }

        foreach ($this->getEnvironments() as $environment) {
            $res['environments'][] = $environment->toArray();
        }

        return $res;
    }
}
