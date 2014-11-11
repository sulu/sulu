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
 * Provides helper function to generate meta tags
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
     * Constructor
     */
    function __construct(RequestAnalyzerInterface $requestAnalyzer, ContentPathInterface $contentPath)
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
            new \Twig_SimpleFunction('meta_alternate', array($this, 'getAlternateLinks'))
        );
    }

    /**
     * Returns alternate link HTML tags with href-lang attributes
     * @param $urls
     * @return string
     */
    public function getAlternateLinks($urls)
    {
        // determine default and current values
        $webspaceKey = $this->requestAnalyzer->getCurrentWebspace()->getKey();
        $currentLocale = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();
        $currentPortal = $this->requestAnalyzer->getCurrentPortal();
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
     * Returns link-alternate html tag
     *  - e.g. <link rel="alternate" href="http://sulu.lo/de/test-url" hreflang="de" />
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     * @param bool $default
     * @return string
     */
    private function getAlternate($url, $webspaceKey, $locale, $default = false)
    {
        $url = $this->contentPath->getContentPath($url, $webspaceKey, $locale);

        return sprintf('<link rel="alternate" href="%s" hreflang="%s" />', $url, !$default ? $locale : 'x-default');
    }
}
