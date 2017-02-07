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
use Sulu\Component\Content\Document\RedirectType;
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
    public function build($page, $portalKey, $locale)
    {
        $pages = $this->contentRepository->findAllByPortal(
            $locale,
            $portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        );

        $result = [];
        foreach ($pages as $contentPage) {
            if (!$contentPage->getUrl()
                || true === $contentPage['seo-hideInSitemap']
                || $contentPage->getNodeType() !== RedirectType::NONE
            ) {
                continue;
            }

            $changed = $contentPage['changed'];
            if (is_string($changed)) {
                $changed = new \DateTime($changed);
            }

            $result[] = $sitemapUrl = new SitemapUrl($contentPage->getUrl(), $changed);
            foreach ($contentPage->getUrls() as $urlLocale => $href) {
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
        return new Sitemap($alias, $this->getMaxPage());
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPage()
    {
        return 1;
    }
}
