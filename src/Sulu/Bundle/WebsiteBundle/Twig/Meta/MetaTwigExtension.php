<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Meta;

use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Provides helper function to generate meta tags.
 *
 * @deprecated will be removed in 1.2
 */
class MetaTwigExtension extends \Twig_Extension
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ContentPathInterface
     */
    private $contentPath;

    /**
     * Constructor.
     */
    public function __construct(RequestAnalyzerInterface $requestAnalyzer, ContentPathInterface $contentPath)
    {
        $this->contentPath = $contentPath;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_meta';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_meta_alternate', [$this, 'getAlternateLinks']),
            new \Twig_SimpleFunction('sulu_meta_seo', [$this, 'getSeoMetaTags']),
        ];
    }

    /**
     * Returns alternate link HTML tags with href-lang attributes.
     *
     * @param array $urls
     *
     * @return string
     *
     * @deprecated since 1.1 use SeoTwigExtension::renderSeoTags - sulu_seo
     */
    public function getAlternateLinks($urls)
    {
        // determine default and current values
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        $currentPortal = $this->requestAnalyzer->getPortal();

        $defaultLocale = null;
        if ($currentPortal !== null && ($defaultLocale = $currentPortal->getXDefaultLocalization()) !== null) {
            $defaultLocale = $defaultLocale->getLocalization();
        }

        $result = [];
        foreach ($urls as $locale => $url) {
            // url = '/' means that there is no translation for this page
            // the only exception is the homepage where the requested resource-locator is '/'
            if ($url !== '/' || $this->requestAnalyzer->getResourceLocator() === '/') {
                if ($locale === $defaultLocale) {
                    $result[] = $this->getAlternate($url, $webspaceKey, $locale, true);
                }
                $result[] = $this->getAlternate($url, $webspaceKey, $locale);
            }
        }

        return implode(PHP_EOL, $result);
    }

    /**
     * Returns seo meta tags with fallbacks.
     *
     * @param array $extension
     * @param array $content
     *
     * @return string
     *
     * @deprecated since 1.1 use SeoTwigExtension::renderSeoTags - sulu_seo
     */
    public function getSeoMetaTags($extension, $content)
    {
        $seo = [];
        if (array_key_exists('seo', $extension)) {
            $seo = $extension['seo'];
        }
        $excerpt = [];
        if (array_key_exists('excerpt', $extension)) {
            $excerpt = $extension['excerpt'];
        }

        // fallback for seo description
        if (
            (!array_key_exists('description', $seo) || $seo['description'] === '') &&
            array_key_exists('description', $excerpt) && $excerpt['description'] !== ''
        ) {
            $seo['description'] = strip_tags($excerpt['description']);
        }

        $seo['description'] = substr($seo['description'], 0, 155);

        // generate robots content
        $robots = [];
        $robots[] = (array_key_exists('noIndex', $seo) && $seo['noIndex'] === true) ? 'noIndex' : 'index';
        $robots[] = (array_key_exists('noFollow', $seo) && $seo['noFollow'] === true) ? 'noFollow' : 'follow';

        // build meta tags
        $result = [];
        $result[] = $this->getMeta('description', $seo['description']);
        $result[] = $this->getMeta('keywords', $seo['keywords']);
        $result[] = $this->getMeta('robots', strtoupper(implode(', ', $robots)));

        return implode(PHP_EOL, $result);
    }

    /**
     * Returns link-alternate html tag
     *  - e.g. <link rel="alternate" href="http://sulu.lo/de/test-url" hreflang="de" />.
     *
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     * @param bool $default
     *
     * @return string
     */
    private function getAlternate($url, $webspaceKey, $locale, $default = false)
    {
        $url = $this->contentPath->getContentPath($url, $webspaceKey, $locale);

        return sprintf(
            '<link rel="alternate" href="%s" hreflang="%s" />',
            $url,
            !$default ? str_replace('_', '-', $locale) : 'x-default'
        );
    }

    /**
     * Returns meta html tag
     *  - e.g. <meta name="description" content="That's a good example">.
     *
     * @param string $name
     * @param string $content
     *
     * @return string
     */
    private function getMeta($name, $content)
    {
        return sprintf('<meta name="%s" content="%s">', $name, $content);
    }
}
