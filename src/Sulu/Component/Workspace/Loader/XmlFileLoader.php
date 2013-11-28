<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\Loader;

use Sulu\Component\Workspace\Environment;
use Sulu\Component\Workspace\Localization;
use Sulu\Component\Workspace\Portal;
use Sulu\Component\Workspace\Segment;
use Sulu\Component\Workspace\Theme;
use Sulu\Component\Workspace\Url;
use Sulu\Component\Workspace\Workspace;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

class XmlFileLoader extends FileLoader
{
    const SCHEME_PATH = '/schema/workspace/workspace-1.0.xsd';

    /**
     * @var  \DOMXPath
     */
    private $xpath;

    /**
     * Loads a workspace from a xml file
     *
     * @param mixed $resource The resource
     * @param string $type     The resource type
     * @return Workspace The workspace object for the given resource
     */
    public function load($resource, $type = null)
    {
        $path = $this->getLocator()->locate($resource);

        // load data in path
        $portal = $this->parseXml($path);

        return $portal;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     *
     * @param $file
     * @return Portal
     */
    private function parseXml($file)
    {
        // load xml file
        $xmlDoc = XmlUtils::loadFile($file, __DIR__ . static::SCHEME_PATH);
        $this->xpath = new \DOMXPath($xmlDoc);
        $this->xpath->registerNamespace('x', 'http://schemas.sulu.io/workspace/workspace');

        // set simple workspace properties
        $workspace = new Workspace();
        $workspace->setName($this->xpath->query('/x:workspace/x:name')->item(0)->nodeValue);
        $workspace->setKey($this->xpath->query('/x:workspace/x:key')->item(0)->nodeValue);

        // set localizations on workspaces
        $this->generateWorkspaceLocalizations($workspace);

        // set segments on workspaces
        $this->generateSegments($workspace);

        // set portals on workspaces
        $this->generatePortals($workspace);

        return $workspace;
    }

    /**
     * @param $node
     * @return bool
     */
    private function convertBoolean($node)
    {
        return ($node) ? $node->nodeValue == 'true' : false;
    }

    /**
     * @param \DOMNode $portalNode
     * @param Portal $portal
     */
    private function generatePortalLocalizations(\DOMNode $portalNode, Portal $portal)
    {
        if ($this->xpath->query('x:localizations', $portalNode)->length > 0) {
            // set localizations from portal, if they are set
            $localizationNodes = $this->xpath->query('x:localizations/x:localization', $portalNode);
            $this->generateLocalizationsFromNodeList($localizationNodes, $portal);
        } else {
            // if the portal has no localizations fallback to the localizations from the workspace
            $localizationNodes = $this->xpath->query('/x:workspace/x:localizations//x:localization');
            $this->generateLocalizationsFromNodeList($localizationNodes, $portal, true);
        }
    }

    /**
     * @param \DOMNodeList $localizationNodes
     * @param Portal $portal
     * @param bool $flat
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
     * @param bool $flat
     * @internal param \DOMXPath $xpath
     * @return Localization
     */
    private function generateLocalizationFromNode(\DOMElement $localizationNode, $flat = false)
    {
        $localization = new Localization();
        $localization->setLanguage($localizationNode->attributes->getNamedItem('language')->nodeValue);
        $localization->setCountry($localizationNode->attributes->getNamedItem('country')->nodeValue);

        // set optional nodes
        $localization->setDefault($this->convertBoolean($localizationNode->attributes->getNamedItem('default')));

        $shadowNode = $localizationNode->attributes->getNamedItem('shadow');
        if ($shadowNode) {
            $localization->setShadow($shadowNode->nodeValue);
        }

        // set child nodes
        if (!$flat) {
            foreach ($this->xpath->query('x:localization', $localizationNode) as $childNode) {
                $localization->addChild($this->generateLocalizationFromNode($childNode));
            }
        }

        return $localization;
    }

    /**
     * @param Workspace $workspace
     */
    private function generateWorkspaceLocalizations(Workspace $workspace)
    {
        foreach ($this->xpath->query('/x:workspace/x:localizations/x:localization') as $localizationNode) {
            $localization = $this->generateLocalizationFromNode($localizationNode);

            $workspace->addLocalization($localization);
        }
    }

    /**
     * @param $workspace
     */
    private function generateSegments(Workspace $workspace)
    {
        foreach ($this->xpath->query('/x:workspace/x:segments/x:segment') as $segmentNode) {
            /** @var \DOMNode $segmentNode */
            $segment = new Segment();
            $segment->setName($segmentNode->nodeValue);
            $segment->setKey($segmentNode->attributes->getNamedItem('key')->nodeValue);

            $workspace->addSegment($segment);
        }
    }

    /**
     * @param Workspace $workspace
     */
    private function generatePortals(Workspace $workspace)
    {
        foreach ($this->xpath->query('/x:workspace/x:portals/x:portal') as $portalNode) {
            /** @var \DOMNode $portalNode */
            $portal = new Portal();

            $portal->setName($this->xpath->query('x:name', $portalNode)->item(0)->nodeValue);
            $portal->setKey($this->xpath->query('x:key', $portalNode)->item(0)->nodeValue);
            $portal->setResourceLocatorStrategy(
                $this->xpath->query('x:resource-locator/x:strategy', $portalNode)->item(0)->nodeValue
            );

            // set theme on portal
            $theme = $this->generateTheme($portalNode);

            $portal->setTheme($theme);

            // set localization on portal
            $this->generatePortalLocalizations($portalNode, $portal);

            $workspace->addPortal($portal);

            // set environments
            $this->generateEnvironments($portalNode, $portal);
        }
    }

    /**
     * @param \DOMNode $portalNode
     * @return Theme
     */
    private function generateTheme($portalNode)
    {
        $theme = new Theme();
        $theme->setKey($this->xpath->query('x:theme/x:key', $portalNode)->item(0)->nodeValue);

        foreach ($this->xpath->query('x:theme/x:excluded/x:template', $portalNode) as $templateNode) {
            /** @var \DOMNode $templateNode */
            $theme->addExcludedTemplate($templateNode->nodeValue);
        }

        return $theme;
    }

    /**
     * @param \DOMNode $portalNode
     * @param Portal $portal
     */
    private function generateEnvironments(\DOMNode $portalNode, Portal $portal)
    {
        foreach ($this->xpath->query('x:environments/x:environment', $portalNode) as $environmentNode) {
            /** @var \DOMNode $environmentNode */
            $environment = new Environment();
            $environment->setType($environmentNode->attributes->getNamedItem('type')->nodeValue);

            $this->generateUrls($environmentNode, $environment);

            $portal->addEnvironment($environment);
        }
    }

    /**
     * @param \DOMNode $environmentNode
     * @param Environment $environment
     */
    private function generateUrls(\DOMNode $environmentNode, Environment $environment)
    {
        foreach ($this->xpath->query('x:urls/x:url', $environmentNode) as $urlNode) {
            /** @var \DOMNode $urlNode */
            $url = new Url();

            $url->setUrl($urlNode->nodeValue);

            // set optional nodes
            $mainNode = $urlNode->attributes->getNamedItem('main');
            $url->setMain($this->convertBoolean($mainNode));

            $environment->addUrl($url);
        }
    }
}
