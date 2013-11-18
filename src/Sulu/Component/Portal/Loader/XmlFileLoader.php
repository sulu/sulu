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
use Sulu\Component\Portal\Portal;
use Sulu\Component\Portal\Theme;
use Sulu\Component\Portal\Url;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

class XmlFileLoader extends FileLoader
{
    const SCHEME_PATH = '/schema/portal/portal-1.0.xsd';

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
        $xmlDoc = XmlUtils::loadFile($file, __DIR__ . static::SCHEME_PATH);
        $xpath = new \DOMXPath($xmlDoc);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/portal/portal');

        $portal = new Portal();
        // set simple properties
        $portal->setName($xpath->query('/x:portal/x:name')->item(0)->nodeValue);
        $portal->setKey($xpath->query('/x:portal/x:key')->item(0)->nodeValue);

        // set resource locator
        $portal->setResourceLocatorStrategy($xpath->query('/x:portal/x:resource-locator/x:strategy')->item(0)->nodeValue);

        // add languages
        foreach ($xpath->query('/x:portal/x:languages/x:language') as $languageNode) {
            /** @var \DOMNode $languageNode */
            $language = new Language();
            $language->setCode($languageNode->nodeValue);

            // set the optional attributes
            if ($languageNode->hasAttributes()) {
                $mainNode = $languageNode->attributes->getNamedItem('main');
                $language->setMain($this->convertBoolean($mainNode));

                $fallbackNode = $languageNode->attributes->getNamedItem('fallback');
                $language->setFallback($this->convertBoolean($fallbackNode));
            }

            $portal->addLanguage($language);
        }

        // set theme
        $theme = new Theme();
        $theme->setKey($xpath->query('/x:portal/x:theme/x:key')->item(0)->nodeValue);

        foreach ($xpath->query('/x:portal/x:theme/x:excluded/x:template') as $templateNode) {
            /** @var \DOMNode $templateNode */
            $theme->addExcludedTemplate($templateNode->nodeValue);
        }

        $portal->setTheme($theme);

        // set environments
        foreach ($xpath->query('/x:portal/x:environments/x:environment') as $environmentNode) {
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

        return $portal;
    }

    /**
     * @param $node
     * @return bool
     */
    private function convertBoolean($node)
    {
        return ($node) ? $node->nodeValue == 'true' : false;
    }
}
