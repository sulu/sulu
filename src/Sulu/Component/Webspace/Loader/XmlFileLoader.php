<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Loader\Exception\ExpectedDefaultTemplatesNotFound;
use Sulu\Component\Webspace\Loader\Exception\InvalidAmountOfDefaultErrorTemplateException;
use Sulu\Component\Webspace\Loader\Exception\InvalidCustomUrlException;
use Sulu\Component\Webspace\Loader\Exception\InvalidDefaultErrorTemplateException;
use Sulu\Component\Webspace\Loader\Exception\InvalidDefaultLocalizationException;
use Sulu\Component\Webspace\Loader\Exception\InvalidErrorTemplateException;
use Sulu\Component\Webspace\Loader\Exception\InvalidPortalDefaultLocalizationException;
use Sulu\Component\Webspace\Loader\Exception\InvalidUrlDefinitionException;
use Sulu\Component\Webspace\Loader\Exception\InvalidWebspaceDefaultLocalizationException;
use Sulu\Component\Webspace\Loader\Exception\InvalidWebspaceDefaultSegmentException;
use Sulu\Component\Webspace\Loader\Exception\PortalDefaultLocalizationNotFoundException;
use Sulu\Component\Webspace\Loader\Exception\WebspaceDefaultSegmentNotFoundException;
use Sulu\Component\Webspace\Loader\Exception\WebspaceLocalizationNotUsedException;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

class XmlFileLoader extends FileLoader
{
    const SCHEME_PATH = '/schema/webspace/webspace-1.0.xsd';

    /**
     * @var \DOMXPath
     */
    private $xpath;

    /**
     * The webspace which is created by this file loader.
     *
     * @var Webspace
     */
    private $webspace;

    /**
     * Loads a webspace from a xml file.
     *
     * @param mixed  $resource The resource
     * @param string $type     The resource type
     *
     * @return Webspace The webspace object for the given resource
     */
    public function load($resource, $type = null)
    {
        $path = $this->getLocator()->locate($resource);

        // load data in path
        return $this->parseXml($path);
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * @param $file
     *
     * @return Portal
     */
    private function parseXml($file)
    {
        // load xml file
        $xmlDoc = XmlUtils::loadFile($file, __DIR__ . static::SCHEME_PATH);
        $this->xpath = new \DOMXPath($xmlDoc);
        $this->xpath->registerNamespace('x', 'http://schemas.sulu.io/webspace/webspace');

        // set simple webspace properties
        $this->webspace = new Webspace();
        $this->webspace->setName($this->xpath->query('/x:webspace/x:name')->item(0)->nodeValue);
        $this->webspace->setKey($this->xpath->query('/x:webspace/x:key')->item(0)->nodeValue);
        $this->webspace->setTheme($this->generateTheme());
        $this->webspace->setNavigation($this->generateNavigation());

        // set security
        $this->generateSecurity();

        // set localizations on webspaces
        $this->generateWebspaceLocalizations();

        // set segments on webspaces
        $this->generateSegments();

        // set portals on webspaces
        $this->generatePortals();

        // validate the webspace, and throw exceptions if not valid
        $this->validate();

        return $this->webspace;
    }

    /**
     * Validate result.
     */
    private function validate()
    {
        $this->validateWebspaceDefaultLocalization();
        $this->validateDefaultPortalLocalization();
        $this->validateWebspaceDefaultSegment();
        $this->validateLocalizations();
    }

    /**
     * @param $portal Portal
     *
     * @return bool True when successful, otherwise false
     */
    private function loadPortalLocalizationDefaultFromWebspace($portal)
    {
        $webspaceDefaultLocalization = $this->webspace->getDefaultLocalization();

        foreach ($portal->getLocalizations() as $localization) {
            if ($webspaceDefaultLocalization
                && $webspaceDefaultLocalization->getLocalization() == $localization->getLocalization()
            ) {
                $localization->setDefault(true);
                $portal->setDefaultLocalization($localization);

                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMNode $portalNode
     * @param Portal   $portal
     */
    private function generatePortalLocalizations(\DOMNode $portalNode, Portal $portal)
    {
        if ($this->xpath->query('x:localizations', $portalNode)->length > 0) {
            // set localizations from portal, if they are set
            $localizationNodes = $this->xpath->query('x:localizations/x:localization', $portalNode);
            $this->generateLocalizationsFromNodeList($localizationNodes, $portal);
        } else {
            // if the portal has no localizations fallback to the localizations from the webspace
            $localizationNodes = $this->xpath->query('/x:webspace/x:localizations//x:localization');
            $this->generateLocalizationsFromNodeList($localizationNodes, $portal, true);
        }
    }

    /**
     * @param \DOMNodeList $localizationNodes
     * @param Portal       $portal
     * @param bool         $flat
     *
     * @internal param \DOMXpath $xpath
     */
    private function generateLocalizationsFromNodeList(\DOMNodeList $localizationNodes, Portal $portal, $flat = false)
    {
        foreach ($localizationNodes as $localizationNode) {
            $localization = $this->generateLocalizationFromNode($localizationNode, $flat);

            $portal->addLocalization($localization);
        }
    }

    /**
     * @param \DOMElement|\DOMNode $localizationNode
     * @param bool                 $flat
     * @param null                 $parent
     *
     * @internal param \DOMXPath $xpath
     *
     * @return Localization
     */
    private function generateLocalizationFromNode(\DOMElement $localizationNode, $flat = false, $parent = null)
    {
        $localization = new Localization();
        $localization->setLanguage($localizationNode->attributes->getNamedItem('language')->nodeValue);

        // set parent if given
        if ($parent) {
            $localization->setParent($parent);
        }

        // set optional nodes
        $countryNode = $localizationNode->attributes->getNamedItem('country');
        if ($countryNode) {
            $localization->setCountry($countryNode->nodeValue);
        }

        $shadowNode = $localizationNode->attributes->getNamedItem('shadow');
        if ($shadowNode) {
            $localization->setShadow($shadowNode->nodeValue);
        }

        $defaultNode = $localizationNode->attributes->getNamedItem('default');
        if ($defaultNode) {
            $localization->setDefault($defaultNode->nodeValue == 'true');
        } else {
            $localization->setDefault(false);
        }

        $xDefaultNode = $localizationNode->attributes->getNamedItem('x-default');
        if ($xDefaultNode) {
            $localization->setXDefault($xDefaultNode->nodeValue == 'true');
        } else {
            $localization->setXDefault(false);
        }

        // set child nodes
        if (!$flat) {
            foreach ($this->xpath->query('x:localization', $localizationNode) as $childNode) {
                $localization->addChild($this->generateLocalizationFromNode($childNode, $flat, $localization));
            }
        }

        return $localization;
    }

    private function generateSecurity()
    {
        $securitySystemNode = $this->xpath->query('/x:webspace/x:security/x:system');
        if ($securitySystemNode->length > 0) {
            $security = new Security();
            $security->setSystem($securitySystemNode->item(0)->nodeValue);
            $this->webspace->setSecurity($security);
        }
    }

    private function generateWebspaceLocalizations()
    {
        foreach ($this->xpath->query('/x:webspace/x:localizations/x:localization') as $localizationNode) {
            $localization = $this->generateLocalizationFromNode($localizationNode);

            $this->webspace->addLocalization($localization);
        }
    }

    private function generateSegments()
    {
        foreach ($this->xpath->query('/x:webspace/x:segments/x:segment') as $segmentNode) {
            /** @var \DOMNode $segmentNode */
            $segment = new Segment();
            $segment->setName($segmentNode->nodeValue);
            $segment->setKey($segmentNode->attributes->getNamedItem('key')->nodeValue);

            $defaultNode = $segmentNode->attributes->getNamedItem('default');
            if ($defaultNode) {
                $segment->setDefault($defaultNode->nodeValue == 'true');
            } else {
                $segment->setDefault(false);
            }

            $this->webspace->addSegment($segment);
        }
    }

    private function generatePortals()
    {
        foreach ($this->xpath->query('/x:webspace/x:portals/x:portal') as $portalNode) {
            /** @var \DOMNode $portalNode */
            $portal = new Portal();

            $portal->setName($this->xpath->query('x:name', $portalNode)->item(0)->nodeValue);
            $portal->setKey($this->xpath->query('x:key', $portalNode)->item(0)->nodeValue);
            $portal->setResourceLocatorStrategy(
                $this->xpath->query('x:resource-locator/x:strategy', $portalNode)->item(0)->nodeValue
            );

            // set localization on portal
            $this->generatePortalLocalizations($portalNode, $portal);

            $this->webspace->addPortal($portal);
            $portal->setWebspace($this->webspace);

            // set environments
            $this->generateEnvironments($portalNode, $portal);
        }
    }

    /**
     * @internal param \DOMNode $webspaceNode
     *
     * @return Theme
     */
    private function generateTheme()
    {
        $theme = new Theme();
        $theme->setKey($this->xpath->query('/x:webspace/x:theme/x:key')->item(0)->nodeValue);
        $this->generateErrorTemplates($theme);
        $this->generateDefaultTemplates($theme);

        return $theme;
    }

    private function generateErrorTemplates(Theme $theme)
    {
        $defaultErrorTemplates = 0;

        foreach ($this->xpath->query('/x:webspace/x:theme/x:error-templates/x:error-template') as $errorTemplateNode) {
            /* @var \DOMNode $errorTemplateNode */
            $template = $errorTemplateNode->nodeValue;
            if (($codeNode = $errorTemplateNode->attributes->getNamedItem('code')) !== null) {
                $code = $codeNode->nodeValue;
            } elseif (($defaultNode = $errorTemplateNode->attributes->getNamedItem('default')) !== null) {
                $default = $defaultNode->nodeValue === 'true';
                if (!$default) {
                    throw new InvalidDefaultErrorTemplateException($template, $this->webspace->getKey());
                }
                ++$defaultErrorTemplates;
                $code = 'default';
            } else {
                throw new InvalidErrorTemplateException($template, $this->webspace->getKey());
            }

            $theme->addErrorTemplate($code, $template);
        }

        // only one or none default error-template is legal
        if ($defaultErrorTemplates > 1) {
            throw new InvalidAmountOfDefaultErrorTemplateException($this->webspace->getKey());
        }

        return $theme;
    }

    private function generateDefaultTemplates(Theme $theme)
    {
        $expected = ['page', 'homepage'];
        $found = [];
        $nodes = $this->xpath->query('/x:webspace/x:theme/x:default-templates/x:default-template');

        foreach ($nodes as $node) {
            /* @var \DOMNode $node */
            $template = $node->nodeValue;
            $type = $node->attributes->getNamedItem('type')->nodeValue;

            $theme->addDefaultTemplate($type, $template);
            $found[] = $type;
        }

        foreach ($expected as $item) {
            if (!in_array($item, $found)) {
                throw new ExpectedDefaultTemplatesNotFound($this->webspace->getKey(), $expected, $found);
            }
        }

        return $theme;
    }

    private function generateNavigation()
    {
        $contexts = [];

        foreach ($this->xpath->query('/x:webspace/x:navigation/x:contexts/x:context') as $contextNode) {
            /* @var \DOMNode $contextNode */
            $contexts[] = new NavigationContext(
                $contextNode->attributes->getNamedItem('key')->nodeValue,
                $this->loadMeta('x:meta/x:*', $contextNode)
            );
        }

        return new Navigation($contexts);
    }

    private function loadMeta($path, \DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($this->xpath->query($path, $context) as $node) {
            $attribute = $node->tagName;
            $lang = $this->xpath->query('@lang', $node)->item(0)->nodeValue;

            if (!isset($result[$node->tagName])) {
                $result[$attribute] = [];
            }
            $result[$attribute][$lang] = $node->textContent;
        }

        return $result;
    }

    /**
     * @param \DOMNode $portalNode
     * @param Portal   $portal
     */
    private function generateEnvironments(\DOMNode $portalNode, Portal $portal)
    {
        foreach ($this->xpath->query('x:environments/x:environment', $portalNode) as $environmentNode) {
            /** @var \DOMNode $environmentNode */
            $environment = new Environment();
            $environment->setType($environmentNode->attributes->getNamedItem('type')->nodeValue);

            $this->generateUrls($environmentNode, $environment);
            $this->generateCustomUrls($environmentNode, $environment);

            $portal->addEnvironment($environment);
        }
    }

    /**
     * @param \DOMNode    $environmentNode
     * @param Environment $environment
     *
     * @throws Exception\InvalidUrlDefinitionException
     */
    private function generateUrls(\DOMNode $environmentNode, Environment $environment)
    {
        foreach ($this->xpath->query('x:urls/x:url', $environmentNode) as $urlNode) {
            // check if the url is valid, and throw an exception otherwise
            if (!$this->checkUrlNode($urlNode)) {
                throw new InvalidUrlDefinitionException($this->webspace, $urlNode->nodeValue);
            }

            /** @var \DOMNode $urlNode */
            $url = new Url();

            $url->setUrl(rtrim($urlNode->nodeValue, '/'));

            // set optional nodes
            $url->setLanguage($this->getOptionalNodeAttribute($urlNode, 'language'));
            $url->setCountry($this->getOptionalNodeAttribute($urlNode, 'country'));
            $url->setSegment($this->getOptionalNodeAttribute($urlNode, 'segment'));
            $url->setRedirect($this->getOptionalNodeAttribute($urlNode, 'redirect'));
            $url->setMain($this->getOptionalNodeAttribute($urlNode, 'main', false));
            $url->setAnalyticsKey($this->getOptionalNodeAttribute($urlNode, 'analytics-key'));

            $environment->addUrl($url);
        }
    }

    /**
     * @param \DOMNode $environmentNode
     * @param Environment $environment
     *
     * @throws InvalidCustomUrlException
     */
    private function generateCustomUrls(\DOMNode $environmentNode, Environment $environment)
    {
        foreach ($this->xpath->query('x:custom-urls/x:custom-url', $environmentNode) as $urlNode) {
            /** @var \DOMNode $urlNode */
            $url = new CustomUrl();

            $url->setUrl(rtrim($urlNode->nodeValue, '/'));

            if (false === strpos($url->getUrl(), '*')) {
                throw new InvalidCustomUrlException($this->webspace, $url->getUrl());
            }

            $environment->addCustomUrl($url);
        }
    }

    private function getOptionalNodeAttribute(\DOMNode $node, $name, $default = null)
    {
        $attribute = $node->attributes->getNamedItem($name);
        if ($attribute) {
            return $attribute->nodeValue;
        }

        return $default;
    }

    /**
     * Checks if the urlNode is valid for this webspace.
     *
     * @param \DOMNode $urlNode
     *
     * @return bool
     */
    private function checkUrlNode(\DOMNode $urlNode)
    {
        $hasLocalization = ($urlNode->attributes->getNamedItem('localization') != null)
            || (strpos($urlNode->nodeValue, '{localization}') !== false);

        $hasLanguage = ($urlNode->attributes->getNamedItem('language') != null)
            || (strpos($urlNode->nodeValue, '{language}') !== false)
            || $hasLocalization;

        $hasSegment = (count($this->webspace->getSegments()) == 0)
            || ($urlNode->attributes->getNamedItem('segment') != null)
            || (strpos($urlNode->nodeValue, '{segment}') !== false);

        $hasRedirect = ($urlNode->attributes->getNamedItem('redirect') != null);

        return ($hasLanguage && $hasSegment) || $hasRedirect;
    }

    /**
     * Validate default webspace localization.
     *
     * @throws Exception\InvalidWebspaceDefaultLocalizationException
     */
    private function validateWebspaceDefaultLocalization()
    {
        try {
            $this->validateDefaultLocalization($this->webspace->getLocalizations());
        } catch (InvalidDefaultLocalizationException $ex) {
            throw new InvalidWebspaceDefaultLocalizationException($this->webspace);
        }
    }

    /**
     * Validate portal localization.
     *
     * @throws Exception\PortalDefaultLocalizationNotFoundException
     * @throws Exception\InvalidPortalDefaultLocalizationException
     */
    private function validateDefaultPortalLocalization()
    {
        // check all portal localizations
        foreach ($this->webspace->getPortals() as $portal) {
            try {
                if (!$this->validateDefaultLocalization($portal->getLocalizations())) {
                    // try to load the webspace localizations before throwing an exception
                    if (!$this->loadPortalLocalizationDefaultFromWebspace($portal)) {
                        throw new PortalDefaultLocalizationNotFoundException($this->webspace, $portal);
                    }
                }
            } catch (InvalidDefaultLocalizationException $ex) {
                throw new InvalidPortalDefaultLocalizationException($this->webspace, $portal);
            }
        }
    }

    /**
     * Validate that all localizations are used in the portals.
     *
     * @throws Exception\PortalDefaultLocalizationNotFoundException
     * @throws Exception\InvalidPortalDefaultLocalizationException
     */
    private function validateLocalizations()
    {
        $locales = array_unique(
            array_map(
                function (Localization $localization) {
                    return $localization->getLocalization();
                },
                $this->webspace->getAllLocalizations()
            )
        );

        $portalLocales = [];
        foreach ($this->webspace->getPortals() as $portal) {
            $portalLocales = array_merge(
                $portalLocales,
                array_map(
                    function (Localization $localization) {
                        return $localization->getLocalization();
                    },
                    $portal->getLocalizations()
                )
            );
        }

        $portalLocales = array_unique($portalLocales);

        if (array_diff($locales, $portalLocales) || array_diff($portalLocales, $locales)) {
            throw new WebspaceLocalizationNotUsedException($this->webspace);
        }
    }

    /**
     * Validate webspace default segment.
     *
     * @throws Exception\WebspaceDefaultSegmentNotFoundException
     * @throws Exception\InvalidWebspaceDefaultSegmentException
     */
    private function validateWebspaceDefaultSegment()
    {
        // check if there are duplicate defaults in the webspaces segments
        $segments = $this->webspace->getSegments();
        if ($segments) {
            $webspaceDefaultSegmentFound = false;
            foreach ($segments as $webspaceSegment) {
                if ($webspaceSegment->isDefault()) {
                    // throw an exception, if a new default segment is found, although there already is one
                    if ($webspaceDefaultSegmentFound) {
                        throw new InvalidWebspaceDefaultSegmentException($this->webspace);
                    }
                    $webspaceDefaultSegmentFound = true;
                }
            }

            if (!$webspaceDefaultSegmentFound) {
                throw new WebspaceDefaultSegmentNotFoundException($this->webspace);
            }
        }
    }

    /**
     * Returns true if there is one default localization.
     *
     * @param $localizations
     *
     * @return bool
     *
     * @throws Exception\InvalidDefaultLocalizationException
     */
    private function validateDefaultLocalization($localizations)
    {
        $result = false;
        foreach ($localizations as $localization) {
            if ($localization->isDefault()) {
                if ($result) {
                    throw new InvalidDefaultLocalizationException();
                }
                $result = true;
            }
        }

        return $result;
    }
}
