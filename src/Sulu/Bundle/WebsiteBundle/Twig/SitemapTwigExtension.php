<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig;

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGeneratorInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Provides twig functions for sitemap
 * @package Sulu\Bundle\WebsiteBundle\Twig
 */
class SitemapTwigExtension extends \Twig_Extension
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SitemapGeneratorInterface
     */
    private $sitemapGenerator;

    /**
     * @var string
     */
    private $environment;

    function __construct(
        SitemapGeneratorInterface $sitemapGenerator,
        WebspaceManagerInterface $webspaceManager,
        $environment
    ) {
        $this->environment = $environment;
        $this->sitemapGenerator = $sitemapGenerator;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('sitemap_url', array($this, 'sitemapUrlFunction')),
            new \Twig_SimpleFunction('sitemap', array($this, 'sitemapFunction'))
        );
    }

    /**
     * Returns prefixed resourcelocator with the url and locale
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     * @return string
     */
    public function sitemapUrlFunction($url, $webspaceKey, $locale)
    {
        // FIXME which url or all urls?
        $portalUrls = $this->webspaceManager->findUrlsByResourceLocator(
            $url,
            $this->environment,
            $locale,
            $webspaceKey
        );

        if (sizeof($portalUrls) === 0) {
            return false;
        }

        return rtrim($portalUrls[0] . $url, '/');
    }

    /**
     * Returns full sitemap of webspace and language from the content
     * @param StructureInterface $content
     * @return array
     */
    public function sitemapFunction(StructureInterface $content)
    {
        return $this->sitemapGenerator->generate($content->getWebspaceKey(), $content->getLanguageCode());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_sitemap';
    }
}
