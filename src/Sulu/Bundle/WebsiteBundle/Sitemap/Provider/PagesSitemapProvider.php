<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap\Provider;

use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;

/**
 * Provides sitemap for webspaces.
 */
class PagesSitemapProvider implements SitemapProviderInterface
{
    /**
     * @var ContentRepositoryInterface
     */
    private $contentRepository;

    /**
     * @param ContentRepositoryInterface $contentRepository
     */
    public function __construct(ContentRepositoryInterface $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($page, $portalKey, $locale)
    {
        $pages = $this->contentRepository->findAllByPortal(
            $locale,
            $portalKey,
            MappingBuilder::create()
                ->addProperties(['changed'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        );

        $result = [];
        foreach ($pages as $page) {
            if (!$page->getUrl()) {
                continue;
            }

            $result[] = $sitemapUrl = new SitemapUrl($page->getUrl(), new \DateTime($page['changed']));
            foreach ($page->getUrls() as $urlLocale => $href) {
                $sitemapUrl->addAlternateLink(new SitemapAlternateLink($href, $urlLocale));
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createSitemap($alias)
    {
        return new Sitemap($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPage()
    {
        return 1;
    }
}
