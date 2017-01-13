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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Container for a webspace definition.
 */
class Webspace implements ArrayableInterface
{
    /**
     * The name of the webspace.
     *
     * @var string
     */
    private $name;

    /**
     * The key of the webspace.
     *
     * @var string
     */
    private $key;

    /**
     * The localizations defined for this webspace.
     *
     * @var Localization[]
     */
    private $localizations = [];

    /**
     * The default localization defined for this webspace.
     *
     * @var Localization
     */
    private $defaultLocalization;

    /**
     * The x-default localization defined for this webspace.
     *
     * @var Localization
     */
    private $xDefaultLocalization;

    /**
     * The segments defined for this webspace.
     *
     * @var Segment[]
     */
    private $segments;

    /**
     * The default segment defined for this webspace.
     *
     * @var Segment
     */
    private $defaultSegment;

    /**
     * The theme of the webspace.
     *
     * @var string
     */
    private $theme;

    /**
     * The portals defined for this webspace.
     *
     * @var Portal[]
     */
    private $portals = [];

    /**
     * The security system for this webspace.
     *
     * @var Security
     */
    private $security;

    /**
     * Navigation for this webspace.
     *
     * @var Navigation
     */
    private $navigation;

    /**
     * A list of twig templates.
     *
     * @var array
     */
    private $templates = [];

    /**
     * Template which is selected by default if no other template is chosen.
     *
     * @var string[]
     */
    private $defaultTemplates = [];

    /**
     * The url generation strategy for this portal.
     *
     * @var string
     */
    private $resourceLocatorStrategy;

    /**
     * Sets the key of the webspace.
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of the webspace.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Adds a localization to the webspace.
     *
     * @param Localization $localization
     */
    public function addLocalization(Localization $localization)
    {
        $this->localizations[] = $localization;

        if ($localization->isDefault()) {
            $this->setDefaultLocalization($localization);
        }

        if ($localization->isXDefault()) {
            $this->xDefaultLocalization = $localization;
        }
    }

    /**
     * Returns the localizations of this webspace.
     *
     * @param Localization[] $localizations
     */
    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;
    }

    /**
     * Returns the localizations of this webspace.
     *
     * @return Localization[]
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * Returns a list of all localizations and sublocalizations.
     *
     * @return Localization[]
     */
    public function getAllLocalizations()
    {
        $localizations = [];
        foreach ($this->getLocalizations() as $child) {
            $localizations[] = $child;
            $localizations = array_merge($localizations, $child->getAllLocalizations());
        }

        return $localizations;
    }

    /**
     * Returns the localization object for a given localization string.
     *
     * @param string $localization
     *
     * @return Localization|null
     */
    public function getLocalization($localization)
    {
        $localizations = $this->getLocalizations();
        if (!empty($localizations)) {
            foreach ($localizations as $webspaceLocalization) {
                $result = $webspaceLocalization->findLocalization($localization);

                if ($result) {
                    return $result;
                }
            }
        }

        return;
    }

    /**
     * Sets the default localization for this webspace.
     *
     * @param Localization $defaultLocalization
     */
    public function setDefaultLocalization($defaultLocalization)
    {
        $this->defaultLocalization = $defaultLocalization;

        if (!$this->getXDefaultLocalization()) {
            $this->xDefaultLocalization = $defaultLocalization;
        }
    }

    /**
     * Returns the default localization for this webspace.
     *
     * @return Localization
     */
    public function getDefaultLocalization()
    {
        if (!$this->defaultLocalization) {
            return $this->localizations[0];
        }

        return $this->defaultLocalization;
    }

    /**
     * Returns the x-default localization for this webspace.
     *
     * @return Localization
     */
    public function getXDefaultLocalization()
    {
        if (!$this->xDefaultLocalization) {
            return $this->localizations[0];
        }

        return $this->xDefaultLocalization;
    }

    /**
     * Sets the name of the webspace.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the webspace.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a portal to the webspace.
     *
     * @param Portal $portal
     */
    public function addPortal(Portal $portal)
    {
        $this->portals[] = $portal;
    }

    /**
     * Sets the portals of this webspace.
     *
     * @param \Sulu\Component\Webspace\Portal[] $portals
     */
    public function setPortals($portals)
    {
        $this->portals = $portals;
    }

    /**
     * Returns the portals of this webspace.
     *
     * @return \Sulu\Component\Webspace\Portal[]
     */
    public function getPortals()
    {
        return $this->portals;
    }

    /**
     * Adds a segment to the webspace.
     *
     * @param Segment $segment
     */
    public function addSegment(Segment $segment)
    {
        $this->segments[] = $segment;

        if ($segment->isDefault()) {
            $this->setDefaultSegment($segment);
        }
    }

    /**
     * Sets the segments of this webspace.
     *
     * @param \Sulu\Component\Webspace\Segment[] $segments
     */
    public function setSegments($segments)
    {
        $this->segments = $segments;
    }

    /**
     * Returns the segments of this webspace.
     *
     * @return \Sulu\Component\Webspace\Segment[]
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * Sets the default segment of this webspace.
     *
     * @param Segment $defaultSegment
     */
    public function setDefaultSegment($defaultSegment)
    {
        $this->defaultSegment = $defaultSegment;
    }

    /**
     * Returns the default segment for this webspace.
     *
     * @return Segment
     */
    public function getDefaultSegment()
    {
        return $this->defaultSegment;
    }

    /**
     * Sets the theme for this portal.
     *
     * @param string|null $theme this parameter is options
     */
    public function setTheme($theme = null)
    {
        $this->theme = $theme;
    }

    /**
     * Returns the theme for this portal.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Sets the security system.
     *
     * @param Security $security
     */
    public function setSecurity($security)
    {
        $this->security = $security;
    }

    /**
     * Returns the security system.
     *
     * @return Security
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * @return Navigation
     */
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * @param Navigation $navigation
     */
    public function setNavigation($navigation)
    {
        $this->navigation = $navigation;
    }

    /**
     * Returns false if domain not exists in webspace.
     *
     * @param string $domain
     * @param string $environment
     * @param string $locale
     *
     * @return bool
     *
     * @throws Exception\EnvironmentNotFoundException
     */
    public function hasDomain($domain, $environment, $locale = null)
    {
        $localizationParts = explode('_', $locale);
        $language = $localizationParts[0];
        $country = isset($localizationParts[1]) ? $localizationParts[1] : null;

        foreach ($this->getPortals() as $portal) {
            foreach ($portal->getEnvironment($environment)->getUrls() as $url) {
                $host = parse_url('//' . $url->getUrl())['host'];
                if (($locale === null || $url->isValidLocale($language, $country))
                    && ($host === $domain || $host === '{host}')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add a new template for given type.
     *
     * @param string $type
     * @param string $template
     */
    public function addTemplate($type, $template)
    {
        $this->templates[$type] = $template;
    }

    /**
     * Returns a template for the given type.
     *
     * @param string $type
     *
     * @return string|null
     */
    public function getTemplate($type)
    {
        if (array_key_exists($type, $this->templates)) {
            return $this->templates[$type];
        }

        return;
    }

    /**
     * Returns an array of templates.
     *
     * @return string[]
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Add a new default template for given type.
     *
     * @param string $type
     * @param string $template
     */
    public function addDefaultTemplate($type, $template)
    {
        $this->defaultTemplates[$type] = $template;
    }

    /**
     * Returns a error template for given code.
     *
     * @param string $type
     *
     * @return string|null
     */
    public function getDefaultTemplate($type)
    {
        if (array_key_exists($type, $this->defaultTemplates)) {
            return $this->defaultTemplates[$type];
        }

        return;
    }

    /**
     * Returns a array of default template.
     *
     * @return string
     */
    public function getDefaultTemplates()
    {
        return $this->defaultTemplates;
    }

    /**
     * Set resource-locator strategy.
     *
     * @param string $resourceLocatorStrategy
     */
    public function setResourceLocatorStrategy($resourceLocatorStrategy)
    {
        $this->resourceLocatorStrategy = $resourceLocatorStrategy;
    }

    /**
     * Returns resource-locator strategy.
     *
     * @return string
     */
    public function getResourceLocatorStrategy()
    {
        return $this->resourceLocatorStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        $res = [];
        $res['key'] = $this->getKey();
        $res['name'] = $this->getName();
        $res['localizations'] = [];
        $res['templates'] = $this->getTemplates();
        $res['defaultTemplates'] = $this->getDefaultTemplates();
        $res['resourceLocator']['strategy'] = $this->getResourceLocatorStrategy();

        foreach ($this->getLocalizations() as $localization) {
            $res['localizations'][] = $localization->toArray();
        }

        $thisSecurity = $this->getSecurity();
        if ($thisSecurity != null) {
            $res['security']['system'] = $thisSecurity->getSystem();
        }

        $res['segments'] = [];
        $segments = $this->getSegments();

        if (!empty($segments)) {
            foreach ($segments as $segment) {
                $res['segments'][] = $segment->toArray();
            }
        }

        $res['theme'] = !$this->theme ? null : $this->theme;

        $res['portals'] = [];
        foreach ($this->getPortals() as $portal) {
            $res['portals'][] = $portal->toArray();
        }

        $res['navigation'] = [];
        $res['navigation']['contexts'] = [];
        if ($navigation = $this->getNavigation()) {
            foreach ($this->getNavigation()->getContexts() as $context) {
                $res['navigation']['contexts'][] = [
                    'key' => $context->getKey(),
                    'metadata' => $context->getMetadata(),
                ];
            }
        }

        return $res;
    }
}
