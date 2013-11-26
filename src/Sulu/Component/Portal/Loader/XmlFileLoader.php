<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal\Loader;

use Sulu\Component\Portal\Environment;
use Sulu\Component\Portal\Language;
use Sulu\Component\Portal\Localization;
use Sulu\Component\Portal\Portal;
use Sulu\Component\Portal\Segment;
use Sulu\Component\Portal\Theme;
use Sulu\Component\Portal\Url;
use Sulu\Component\Portal\Workspace;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

class XmlFileLoader extends FileLoader
{
    const SCHEME_PATH = '/schema/workspace/workspace-1.0.xsd';

    /**
     * Loads a portal from a xml file
     *
     * @param mixed $resource The resource
     * @param string $type     The resource type
     * @return Portal The portal object for the given resource
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
        $xpath = new \DOMXPath($xmlDoc);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/workspace/workspace');

        // set simple workspace properties
        $workspace = new Workspace();
        $workspace->setName($xpath->query('/x:workspace/x:name')->item(0)->nodeValue);
        $workspace->setKey($xpath->query('/x:workspace/x:key')->item(0)->nodeValue);

        // set localizations on workspaces
        foreach ($xpath->query('/x:workspace/x:localizations/x:localization') as $localizationNode) {
            $localization = $this->generateLocalization($localizationNode);

            $workspace->addLocalization($localization);
        }

        // set segments on workspaces
        foreach ($xpath->query('/x:workspace/x:segments/x:segment') as $segmentNode) {
            /** @var \DOMNode $segmentNode */
            $segment = new Segment();
            $segment->setName($segmentNode->nodeValue);
            $segment->setKey($segmentNode->attributes->getNamedItem('key'));

            $workspace->addSegment($segment);
        }

        // set portals on workspaces
        foreach ($xpath->query('/x:workspace/x:portals/x:portal') as $portalNode) {
            /** @var \DOMNode $portalNode */
            $portal = new Portal();

            $portal->setName($xpath->query('x:name', $portalNode)->item(0)->nodeValue);
            $portal->setResourceLocatorStrategy($xpath->query('x:resource-locator/x:strategy', $portalNode)->item(0)->nodeValue);

            // set theme on portal
            $theme = new Theme();
            $theme->setKey($xpath->query('x:theme/x:key', $portalNode)->item(0)->nodeValue);

            foreach($xpath->query('x:theme/x:excluded/x:template') as $templateNode) {
                /** @var \DOMNode $templateNode */
                $theme->addExcludedTemplate($templateNode->nodeValue);
            }

            $portal->setTheme($theme);

            // set localization on portal
            foreach ($xpath->query('x:localizations/x:localization', $portalNode) as $localizationNode) {
                $localization = $this->generateLocalization($localizationNode);

                $portal->addLocalization($localization);
            }

            $workspace->addPortal($portal);

            // set environments
            foreach ($xpath->query('x:environments/x:environment', $portalNode) as $environmentNode) {
                /** @var \DOMNode $environmentNode */
                $environment = new Environment();
                $environment->setType($environmentNode->attributes->getNamedItem('type')->nodeValue);

                foreach ($xpath->query('x:urls/x:url', $environmentNode) as $urlNode) {
                    /** @var \DOMNode $urlNode */
                    $url = new Url();

                    $url->setUrl($urlNode->nodeValue);

                    // set optional nodes
                    $mainNode = $urlNode->attributes->getNamedItem('main');
                    $url->setMain($this->convertBoolean($mainNode));

                    $environment->addUrl($url);
                }

                $portal->addEnvironment($environment);
            }
        }

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
     * @param $localizationNode
     * @return Localization
     */
    private function generateLocalization($localizationNode)
    {
        /** @var \DOMNode $localizationNode */
        $localization = new Localization();
        $localization->setLanguage($localizationNode->attributes->getNamedItem('language')->nodeValue);
        $localization->setCountry($localizationNode->attributes->getNamedItem('country')->nodeValue);

        // set optional nodes
        $localization->setDefault($this->convertBoolean($localizationNode->attributes->getNamedItem('default')));

        $shadowNode = $localizationNode->attributes->getNamedItem('shadow');
        if ($shadowNode) {
            $localization->setShadow($shadowNode->nodeValue);

            return $localization;
        }

        return $localization;
    }
}
