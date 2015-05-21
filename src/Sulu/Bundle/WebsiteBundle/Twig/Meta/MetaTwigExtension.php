<?php
/*
 * This file is part of the Sulu CMS.
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
        return array(
            new \Twig_SimpleFunction('meta_alternate', array($this, 'getAlternateLinks')),
            new \Twig_SimpleFunction('meta_seo', array($this, 'getSeoMetaTags')),
        );
    }

    /**
     * Returns alternate link HTML tags with href-lang attributes.
     *
     * @param array $urls
     *
     * @return string
     */
    public function getAlternateLinks($urls)
    {
        // determine default and current values
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        $currentLocale = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();
        $currentPortal = $this->requestAnalyzer->getPortal();
        $defaultLocale = null;
        if ($currentPortal !== null && ($defaultLocale = $currentPortal->getDefaultLocalization()) !== null) {
            $defaultLocale = $defaultLocale->getLocalization();
        }

        $result = array();
        foreach ($urls as $locale => $url) {
            if ($url !== null) {
                if ($locale === $defaultLocale) {
                    $result[] = $this->getAlternate($url, $webspaceKey, $locale);
                    $result[] = $this->getAlternate($url, $webspaceKey, $locale, true);
                } elseif ($locale !== $currentLocale) {
                    $result[] = $this->getAlternate($url, $webspaceKey, $locale);
                }
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
     */
    public function getSeoMetaTags($extension, $content)
    {
        $seo = array();
        if (array_key_exists('seo', $extension)) {
            $seo = $extension['seo'];
        }
        $excerpt = array();
        if (array_key_exists('excerpt', $extension)) {
            $excerpt = $extension['excerpt'];
        }

        // fallback for seo title
        if (!array_key_exists('title', $seo) || $seo['title'] === '') {
            $seo['title'] = $content['title'];
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
        $robots = array();
        $robots[] = (array_key_exists('noIndex', $seo) && $seo['noIndex'] === true) ? 'noIndex' : 'index';
        $robots[] = (array_key_exists('noFollow', $seo) && $seo['noFollow'] === true) ? 'noFollow' : 'follow';

        // build meta tags
        $result = array();
        $result[] = $this->getMeta('title', $seo['title']);
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

        return sprintf('<link rel="alternate" href="%s" hreflang="%s" />', $url, !$default ? $locale : 'x-default');
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
