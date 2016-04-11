<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Seo;

use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This twig extension provides support for the SEO functionality provided by Sulu.
 */
class SeoTwigExtension extends \Twig_Extension
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
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        ContentPathInterface $contentPath,
        RequestStack $requestStack
    ) {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->requestStack = $requestStack;
        // FIXME Should not use another twig extension here, that is not the intended use case of twig extensions
        $this->contentPath = $contentPath;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_seo';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_seo', [$this, 'renderSeoTags']),
        ];
    }

    /**
     * Renders all the SEO tags supported by Sulu.
     *
     * @param array $seoExtension The values delivered by the SEO extension of Sulu
     * @param array $content The content of the current page
     * @param string[] $urls All the localized URLs for the current page
     * @param string $shadowBaseLocale The displayed language, in case the current page is a shadow
     *
     * @return string The rendered HTML tags of the SEO extension
     */
    public function renderSeoTags(array $seoExtension, array $content, array $urls, $shadowBaseLocale)
    {
        $request = $this->requestStack->getCurrentRequest();
        $requestSeo = $request->get('_seo', []);
        $seoExtension = array_merge($seoExtension, $requestSeo);

        $html = '';
        // FIXME this is only necessary because we have to set a default parameter
        $webspace = $this->requestAnalyzer->getWebspace();
        $webspaceKey = null;
        if ($webspace) {
            $webspaceKey = $webspace->getKey();
        }

        $request = $this->requestStack->getCurrentRequest();

        $html .= $this->renderTitle($seoExtension, $content);
        $html .= $this->renderMetaTags($seoExtension);
        $html .= $this->renderAlternateLinks($urls, $webspaceKey, $request->getScheme());
        $html .= $this->renderCanonicalTag(
            $seoExtension,
            $urls,
            $shadowBaseLocale,
            $webspaceKey,
            $request->getScheme()
        );

        return $html;
    }

    /**
     * Renders the correct title of the current page. The correct title is either the title provided by the SEO
     * extension, or the title of the content, if the SEO extension does not provide one.
     *
     * @param array $seoExtension The values delivered by the SEO extension of Sulu
     * @param array $content The content of the current page
     *
     * @return string The rendered title tag
     */
    private function renderTitle(array $seoExtension, array $content)
    {
        $titleHtml = '<title>%s</title>';

        if (isset($seoExtension['title']) && $seoExtension['title'] !== '') {
            return sprintf($titleHtml, htmlentities($seoExtension['title']));
        }

        if (isset($content['title'])) {
            return sprintf($titleHtml . PHP_EOL, htmlentities($content['title']));
        }

        return '';
    }

    /**
     * Renders the meta tags of the SEO extension. Contains the description, keywords and the robots settings.
     *
     * @param array $seoExtension The values delivered by the SEO extension of Sulu
     *
     * @return string The rendered meta tags
     */
    private function renderMetaTags(array $seoExtension)
    {
        $html = '';

        if (isset($seoExtension['description']) && $seoExtension['description'] !== '') {
            $html .= $this->renderMetaTag('description', $seoExtension['description']);
        }

        if (isset($seoExtension['keywords']) && $seoExtension['keywords'] !== '') {
            $html .= $this->renderMetaTag('keywords', $seoExtension['keywords']);
        }

        $robots = [];
        if (isset($seoExtension['noIndex']) && $seoExtension['noIndex'] === true) {
            $robots[] = 'noIndex';
        } else {
            $robots[] = 'index';
        }

        if (isset($seoExtension['noFollow']) && $seoExtension['noFollow'] === true) {
            $robots[] = 'noFollow';
        } else {
            $robots[] = 'follow';
        }

        $html .= $this->renderMetaTag('robots', implode(',', $robots));

        return $html;
    }

    /**
     * Renders a simple meta tag.
     *
     * @param string $name The name of the meta tag
     * @param string $content The content of the meta tag
     *
     * @return string The HTMl meta tag filled with the given values
     */
    private function renderMetaTag($name, $content)
    {
        return sprintf('<meta name="%s" content="%s"/>' . PHP_EOL, $name, htmlentities($content));
    }

    /**
     * Renders the alternate links for this page, this means all the localizations in which this page is available. In
     * addition the default localization is also rendered.
     *
     * @param string[] $urls All the localized URLs for the current page
     * @param string $webspaceKey The key of the current webspace
     * @param string $scheme scheme of request (http or https)
     *
     * @return string The rendered HTML tags
     */
    private function renderAlternateLinks(array $urls, $webspaceKey, $scheme)
    {
        $html = '';
        $concreteTranslations = 0;

        $defaultLocale = null;
        $portal = $this->requestAnalyzer->getPortal();
        if ($portal) {
            $defaultLocale = $portal->getXDefaultLocalization()->getLocalization();
        }

        foreach ($urls as $locale => $url) {
            // url = '/' means that there is no translation for this page
            // the only exception is the homepage where the requested resource-locator is false
            if ($url !== '/' || $this->requestAnalyzer->getResourceLocator() === false) {
                if ($defaultLocale === $locale) {
                    $html .= $this->renderAlternateLink($url, $webspaceKey, $locale, true, $scheme);
                }

                $html .= $this->renderAlternateLink($url, $webspaceKey, $locale, false, $scheme);
                ++$concreteTranslations;
            }
        }

        // if only one translation is available dismiss hreflang tags
        if ($concreteTranslations <= 1) {
            return '';
        }

        return $html;
    }

    /**
     * Renders a single alternate link.
     *
     * @param string $url The url for the given locale of the current page
     * @param string $webspaceKey The key of the current webspace
     * @param string $locale The locale for which the tag should be rendered
     * @param bool $default If true the tag will be rendered as default locale
     * @param string $scheme scheme of request (http or https)
     *
     * @return string The rendered alternate link tag
     */
    private function renderAlternateLink($url, $webspaceKey, $locale, $default = false, $scheme = 'http')
    {
        return sprintf(
            '<link rel="alternate" href="%s" hreflang="%s"/>' . PHP_EOL,
            rtrim($this->contentPath->getContentPath($url, $webspaceKey, $locale, null, $scheme), '/'),
            $default ? 'x-default' : str_replace('_', '-', $locale)
        );
    }

    /**
     * Renders the canonical tag for the current page. Uses the value provided by the SEO extension. If the SEO
     * extension does not provide a value, it checks if the current page is a shadow, and writes the correct canonical
     * tag automatically.
     *
     * @param array $seoExtension The values delivered by the SEO extension of Sulu
     * @param string[] $urls All the localized URLs for the current page
     * @param string $shadowBaseLocale The displayed language, in case the current page is a shadow
     * @param string $webspaceKey The key of the current webspace
     * @param string $scheme scheme of request (http or https)
     *
     * @return string The rendered canonical link tag
     */
    private function renderCanonicalTag(array $seoExtension, array $urls, $shadowBaseLocale, $webspaceKey, $scheme)
    {
        $canonicalTagHtml = '<link rel="canonical" href="%s"/>' . PHP_EOL;

        if (isset($seoExtension['canonicalUrl']) && $seoExtension['canonicalUrl'] !== '') {
            return sprintf($canonicalTagHtml, $seoExtension['canonicalUrl']);
        }

        if ($shadowBaseLocale && isset($urls[$shadowBaseLocale])) {
            return sprintf(
                $canonicalTagHtml,
                $this->contentPath->getContentPath(
                    $urls[$shadowBaseLocale],
                    $webspaceKey,
                    $shadowBaseLocale,
                    null,
                    $scheme
                )
            );
        }

        return '';
    }
}
