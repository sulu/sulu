<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Generates a sitemap structure for xml or html.
 */
class SitemapGenerator implements SitemapGeneratorInterface
{
    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQuery;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    public function __construct(
        ContentQueryExecutorInterface $contentQuery,
        WebspaceManagerInterface $webspaceManager,
        ContentQueryBuilderInterface $contentQueryBuilder
    ) {
        $this->contentQuery = $contentQuery;
        $this->webspaceManager = $webspaceManager;
        $this->contentQueryBuilder = $contentQueryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function generateAllLocals($webspaceKey, $flat = false)
    {
        $webSpaceSitemap = $this->getWebspaceSitemap($webspaceKey);
        $webSpaceSitemap->setSitemap(
            $this->generateByLocals($webspaceKey, $webSpaceSitemap->getLocalizations(), $flat)
        );

        return $webSpaceSitemap;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($webspaceKey, $locale, $flat = false)
    {
        $webspaceSitemapInformation = $this->getWebspaceSitemap($webspaceKey);
        $sitemap = $this->generateByLocals($webspaceKey, [$locale], $flat);
        if (count($sitemap) === 1 && !$flat) {
            $sitemap = $sitemap[0];
        }
        $webspaceSitemapInformation->setSitemap(
            $sitemap
        );

        return $webspaceSitemapInformation;
    }

    /**
     * @param $webspaceKey
     *
     * @return WebspaceSitemap
     */
    private function getWebspaceSitemap($webspaceKey)
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        $webspaceSitemap = new WebspaceSitemap();
        $webspaceSitemap->setWebspaceKey($webspace->getKey());

        $defaultLocalization = $webspace->getDefaultLocalization();
        if ($defaultLocalization) {
            $webspaceSitemap->setDefaultLocalization($defaultLocalization->getLocalization());
        }
        foreach ($webspace->getAllLocalizations() as $localization) {
            $webspaceSitemap->addLocalization($localization->getLocalization());
        }

        return $webspaceSitemap;
    }

    /**
     * @param string $webspaceKey
     * @param array  $locales
     * @param bool   $flat
     *
     * @return array
     */
    private function generateByLocals($webspaceKey, $locales, $flat = false)
    {
        return $this->contentQuery->execute(
            $webspaceKey,
            $locales,
            $this->contentQueryBuilder,
            $flat,
            -1,
            null,
            null,
            true
        );
    }
}
