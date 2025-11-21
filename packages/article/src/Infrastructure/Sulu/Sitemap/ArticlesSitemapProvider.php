<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Sulu\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\AbstractSitemapProvider;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal your code should not create direct dependencies on this implementation
 *           projects can create there own sitemap providers or use symfony
 *           dependency injection container to override this sitemap provider service
 *
 * @phpstan-type Article array{
 *     lastModified: \DateTimeImmutable|null,
 *     changed: \DateTimeImmutable,
 *     locale: string,
 *     availableLocales: string[]|null,
 *     slug: string,
 *     uuid: string
 * }
 * @phpstan-type AlternateRoute array{
 *     locale: string,
 *     slug: string,
 *     uuid: string
 * }
 */
class ArticlesSitemapProvider extends AbstractSitemapProvider
{
    /**
     * @var EntityRepository<ArticleInterface>
     */
    protected EntityRepository $entityRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly WebspaceManagerInterface $webspaceManager,
        private readonly string $environment,
    ) {
        $repository = $entityManager->getRepository(ArticleInterface::class);

        $this->entityRepository = $repository;
    }

    /**
     * @return SitemapUrl[]
     */
    public function build($page, $scheme, $host): array
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains(
            $host,
            $this->environment,
        );

        $result = [];

        foreach ($portalInformations as $portalInformation) {
            /** @var Localization|null $localization */
            $localization = $portalInformation->getLocalization();

            if (!$localization) {
                continue;
            }

            $locale = $portalInformation->getLocalization()->getLocale();
            /** @var string $webspaceKey */
            $webspaceKey = $portalInformation->getWebspaceKey();
            $articleIterator = $this->getArticles($webspaceKey, $locale);
            $alternateRoutesIterator = $this->getAlternateRoutes($webspaceKey, $locale);
            $alternateRoutes = [];
            $articles = [];
            $alternateArticles = [];

            /** @var AlternateRoute $alternateRoute */
            foreach ($alternateRoutesIterator as $alternateRoute) {
                $alternateLocale = $alternateRoute['locale'];
                $alternateSlug = $alternateRoute['slug'];
                $articleUuid = $alternateRoute['uuid'];

                if (!\array_key_exists($articleUuid, $alternateRoutes)) {
                    $alternateRoutes[$articleUuid] = [];
                }

                if (!\array_key_exists($alternateLocale, $alternateRoutes[$articleUuid])) {
                    $alternateRoutes[$articleUuid][$alternateLocale] = [];
                }

                $alternateRoutes[$articleUuid][$alternateLocale][] = $alternateSlug;
            }

            /** @var Article $article */
            foreach ($articleIterator as $article) {
                $articles[] = $article;

                $articleUuid = $article['uuid'];
                $alternateLocales = \array_filter(
                    $article['availableLocales'] ?? [],
                    fn ($availableLocale) => $availableLocale !== $locale,
                );

                $articleAlternateRoutes = [];
                foreach ($alternateLocales as $availableLocale) {
                    if (isset($alternateRoutes[$articleUuid][$availableLocale])) {
                        $articleAlternateRoutes[$availableLocale] = $alternateRoutes[$articleUuid][$availableLocale];
                    }
                }

                if (!empty($articleAlternateRoutes)) {
                    $alternateArticles[$articleUuid] = $articleAlternateRoutes;
                } else {
                    unset($alternateArticles[$articleUuid]);
                }
            }

            foreach ($articles as $article) {
                // Todo: Add access control check.

                $sitemapUrl = $this->generateSitemapUrl($article, $alternateArticles, $portalInformation, $host, $scheme);

                if (!$sitemapUrl) {
                    continue;
                }

                $result[] = $sitemapUrl;
            }
        }

        return $result;
    }

    /**
     * @return iterable<Article>
     */
    private function getArticles(string $webspaceKey, string $locale)
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('article');

        $queryBuilder->distinct()->join('article.dimensionContents', 'dimensionContent', 'WITH', '
            dimensionContent.locale = :locale
            AND dimensionContent.stage = :stage
            AND dimensionContent.version = :version
            AND dimensionContent.seoHideInSitemap = :hide
        ')
            ->leftJoin('dimensionContent.additionalWebspaces', 'additionalWebspace')
            ->leftJoin('article.dimensionContents', 'unLocalizedDimensionContent', 'WITH', '
            unLocalizedDimensionContent.locale IS NULL
            AND unLocalizedDimensionContent.stage = :stage
            AND unLocalizedDimensionContent.version = :version
            AND unLocalizedDimensionContent.seoHideInSitemap = :hide
        ')
            ->leftJoin('dimensionContent.route', 'route')
            ->andWhere('dimensionContent.mainWebspace = :webspaceKey OR additionalWebspace.additionalWebspace = :webspaceKey')
            ->setParameter('locale', $locale)
            ->setParameter('stage', DimensionContentInterface::STAGE_LIVE)
            ->setParameter('webspaceKey', $webspaceKey)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->setParameter('hide', false);

        $queryBuilder->select('dimensionContent.lastModified');
        $queryBuilder->addSelect('dimensionContent.changed');
        $queryBuilder->addSelect('dimensionContent.locale');

        $queryBuilder->addSelect('unLocalizedDimensionContent.availableLocales');

        $queryBuilder->addSelect('route.slug');

        $queryBuilder->addSelect('article.uuid');

        $queryBuilder->orderBy('route.slug', 'ASC');

        /**
         * @var iterable<Article>
         */
        return $queryBuilder->getQuery()->toIterable();
    }

    /**
     * @return iterable<AlternateRoute>
     */
    private function getAlternateRoutes(string $webspaceKey, string $locale): iterable
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('article');

        $queryBuilder->distinct()->leftJoin('article.dimensionContents', 'dimensionContent', 'WITH', '
            dimensionContent.locale != :locale
            AND dimensionContent.locale IS NOT NULL
            AND dimensionContent.stage = :stage
            AND dimensionContent.version = :version
            AND dimensionContent.seoHideInSitemap = :hide
        ')
            ->leftJoin('dimensionContent.additionalWebspaces', 'additionalWebspace')
            ->leftJoin('dimensionContent.route', 'route')
            ->andWhere('dimensionContent.mainWebspace = :webspaceKey OR additionalWebspace.additionalWebspace = :webspaceKey')
            ->setParameter('locale', $locale)
            ->setParameter('stage', DimensionContentInterface::STAGE_LIVE)
            ->setParameter('webspaceKey', $webspaceKey)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->setParameter('hide', false);

        $queryBuilder->select('dimensionContent.locale');
        $queryBuilder->addSelect('route.slug');
        $queryBuilder->addSelect('article.uuid');

        $queryBuilder->orderBy('route.slug', 'ASC');

        /**
         * @var iterable<AlternateRoute>
         */
        return $queryBuilder->getQuery()->toIterable();
    }

    /**
     * @param Article $article
     * @param array<string, array<string, string[]>> $alternateArticle
     */
    private function generateSitemapUrl(
        array $article,
        array $alternateArticle,
        PortalInformation $portalInformation,
        string $host,
        string $scheme,
    ): ?SitemapUrl {
        $changed = $article['changed'];
        /** @var string|null $webspaceKey */
        $webspaceKey = $portalInformation->getWebspaceKey();

        if (!empty($article['lastModified'])) {
            $changed = $article['lastModified'];
        }

        $url = $this->webspaceManager->findUrlByResourceLocator(
            $article['slug'],
            $this->environment,
            $article['locale'],
            $webspaceKey,
            $host,
            $scheme,
        );

        if (!$url) {
            return null;
        }

        $defaultLocale = $portalInformation
            ->getWebspace()
            ->getDefaultLocalization()
            ->getLocale(Localization::DASH);

        $sitemapUrl = new SitemapUrl(
            $url,
            $article['locale'],
            $defaultLocale,
            $changed,
        );

        if ($alternateArticle[$article['uuid']] ?? null) {
            foreach ($alternateArticle[$article['uuid']] as $alternateLocale => $alternateSlugs) {
                foreach ($alternateSlugs as $alternateSlug) {
                    $alternateUrl = $this->webspaceManager->findUrlByResourceLocator(
                        $alternateSlug,
                        $this->environment,
                        $alternateLocale,
                        $webspaceKey,
                        $host,
                        $scheme,
                    );

                    if ($alternateUrl) {
                        $sitemapUrl->addAlternateLink(new SitemapAlternateLink($alternateUrl, $alternateLocale));
                    }
                }
            }
        }

        return $sitemapUrl;
    }

    public function getAlias(): string
    {
        return 'articles';
    }
}
