<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Bundle\WebsiteBundle\Sitemap\AbstractSitemapProvider;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal your code should not create direct dependencies on this implementation
 *           projects can create there own sitemap providers or use symfony
 *           dependency injection container to override this sitemap provider service
 *
 * @phpstan-type PageData array{
 *     lastModified: \DateTimeImmutable|null,
 *     changed: \DateTimeImmutable,
 *     locale: string,
 *     availableLocales: string[]|null,
 *     title: string|null,
 *     slug: string,
 *     uuid: string
 * }
 * @phpstan-type AlternateRoute array{
 *     locale: string,
 *     slug: string,
 *     uuid: string
 * }
 */
class PagesSitemapProvider extends AbstractSitemapProvider
{
    /**
     * @var EntityRepository<PageInterface>
     */
    protected EntityRepository $entityRepository;

    /**
     * @param mixed[]|null $permissions
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly WebspaceManagerInterface $webspaceManager,
        private readonly string $environment,
        private readonly AccessControlQueryEnhancer $accessControlQueryEnhancer,
        private readonly ?Security $security,
        private readonly ?array $permissions = null,
    ) {
        $repository = $entityManager->getRepository(PageInterface::class);

        $this->entityRepository = $repository;
    }

    /**
     * @return SitemapUrl[]
     */
    public function build($page, $scheme, $host): array
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains(
            $host,
            $this->environment
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
            $pagesIterator = $this->getPages($webspaceKey, $locale);
            $alternateRoutesIterator = $this->getAlternateRoutes($webspaceKey, $locale);
            $alternateRoutes = [];
            $pages = [];
            $alternatePages = [];

            /** @var AlternateRoute $alternateRoute */
            foreach ($alternateRoutesIterator as $alternateRoute) {
                $alternateLocale = $alternateRoute['locale'];
                $alternateSlug = $alternateRoute['slug'];
                $pageUuid = $alternateRoute['uuid'];

                if (!\array_key_exists($pageUuid, $alternateRoutes)) {
                    $alternateRoutes[$pageUuid] = [];
                }

                if (!\array_key_exists($alternateLocale, $alternateRoutes[$pageUuid])) {
                    $alternateRoutes[$pageUuid][$alternateLocale] = [];
                }

                $alternateRoutes[$pageUuid][$alternateLocale][] = $alternateSlug;
            }

            /** @var PageData $page */
            foreach ($pagesIterator as $page) {
                $pages[] = $page;

                $pageUuid = $page['uuid'];
                $alternateLocales = \array_filter(
                    $page['availableLocales'] ?? [],
                    fn ($availableLocale) => $availableLocale !== $locale
                );

                $pageAlternateRoutes = [];
                foreach ($alternateLocales as $availableLocale) {
                    if (isset($alternateRoutes[$pageUuid][$availableLocale])) {
                        $pageAlternateRoutes[$availableLocale] = $alternateRoutes[$pageUuid][$availableLocale];
                    }
                }

                if (!empty($pageAlternateRoutes)) {
                    $alternatePages[$pageUuid] = $pageAlternateRoutes;
                } else {
                    unset($alternatePages[$pageUuid]);
                }
            }

            foreach ($pages as $page) {
                $sitemapUrl = $this->generateSitemapUrl($page, $alternatePages, $portalInformation, $host, $scheme);

                if (!$sitemapUrl) {
                    continue;
                }

                $result[] = $sitemapUrl;
            }
        }

        return $result;
    }

    /**
     * Enhances the query builder with access control filtering if webspace has security enabled.
     */
    private function enhanceQueryBuilderWithAccessControl(
        QueryBuilder $queryBuilder,
        string $webspaceKey,
        string $alias
    ): void {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        /** @var UserInterface|null $user */
        $user = $webspace && $webspace->hasWebsiteSecurity() && $this->security ? $this->security->getUser() : null;
        /** @var int|null $permission */
        $permission = $webspace && $webspace->hasWebsiteSecurity() && $this->permissions
            ? $this->permissions[PermissionTypes::VIEW]
            : null;

        if ($permission) {
            $this->accessControlQueryEnhancer->enhance(
                $queryBuilder,
                $user,
                $permission,
                Page::class,
                $alias,
                'uuid'
            );
        }
    }

    /**
     * @return iterable<PageData>
     */
    private function getPages(string $webspaceKey, string $locale): iterable
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('page');

        $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')
            ->setParameter('webspaceKey', $webspaceKey);

        $queryBuilder->distinct()->join('page.dimensionContents', 'dimensionContent', 'WITH', '
            dimensionContent.locale = :locale
            AND dimensionContent.stage = :stage
            AND dimensionContent.version = :version
            AND dimensionContent.seoHideInSitemap = :hide
            AND dimensionContent.linkProvider IS NULL
        ')
            ->leftJoin('page.dimensionContents', 'unLocalizedDimensionContent', 'WITH', '
            unLocalizedDimensionContent.locale IS NULL
            AND unLocalizedDimensionContent.stage = :stage
            AND unLocalizedDimensionContent.version = :version
            AND unLocalizedDimensionContent.seoHideInSitemap = :hide
        ')
            ->leftJoin('dimensionContent.route', 'route')
            ->setParameter('locale', $locale)
            ->setParameter('stage', DimensionContentInterface::STAGE_LIVE)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->setParameter('hide', false);

        $this->enhanceQueryBuilderWithAccessControl($queryBuilder, $webspaceKey, 'page');

        $queryBuilder->select('dimensionContent.lastModified');
        $queryBuilder->addSelect('dimensionContent.changed');
        $queryBuilder->addSelect('dimensionContent.locale');

        $queryBuilder->addSelect('unLocalizedDimensionContent.availableLocales');
        $queryBuilder->addSelect('dimensionContent.title');

        $queryBuilder->addSelect('route.slug');

        $queryBuilder->addSelect('page.uuid');

        $queryBuilder->orderBy('route.slug', 'ASC');

        /**
         * @var iterable<PageData>
         */
        return $queryBuilder->getQuery()->toIterable();
    }

    /**
     * @return iterable<AlternateRoute>
     */
    private function getAlternateRoutes(string $webspaceKey, string $locale): iterable
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('page');

        $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')
            ->setParameter('webspaceKey', $webspaceKey);

        $queryBuilder->distinct()->leftJoin('page.dimensionContents', 'dimensionContent', 'WITH', '
            dimensionContent.locale != :locale
            AND dimensionContent.locale IS NOT NULL
            AND dimensionContent.stage = :stage
            AND dimensionContent.version = :version
            AND dimensionContent.seoHideInSitemap = :hide
            AND dimensionContent.linkProvider IS NULL
        ')
            ->leftJoin('dimensionContent.route', 'route')
            ->setParameter('locale', $locale)
            ->setParameter('stage', DimensionContentInterface::STAGE_LIVE)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->setParameter('hide', false);

        $this->enhanceQueryBuilderWithAccessControl($queryBuilder, $webspaceKey, 'page');

        $queryBuilder->select('dimensionContent.locale');
        $queryBuilder->addSelect('route.slug');
        $queryBuilder->addSelect('page.uuid');

        $queryBuilder->orderBy('route.slug', 'ASC');

        /**
         * @var iterable<AlternateRoute>
         */
        return $queryBuilder->getQuery()->toIterable();
    }

    /**
     * @param PageData $page
     * @param array<string, array<string, string[]>> $alternatePages
     */
    private function generateSitemapUrl(
        array $page,
        array $alternatePages,
        PortalInformation $portalInformation,
        string $host,
        string $scheme
    ): ?SitemapUrl {
        $changed = $page['changed'];
        /** @var string|null $webspaceKey */
        $webspaceKey = $portalInformation->getWebspaceKey();

        if (!empty($page['lastModified'])) {
            $changed = $page['lastModified'];
        }

        $url = $this->webspaceManager->findUrlByResourceLocator(
            $page['slug'],
            $this->environment,
            $page['locale'],
            $webspaceKey,
            $host,
            $scheme
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
            $page['locale'],
            $defaultLocale,
            $changed,
            title: $page['title'] ?? null
        );

        if ($alternatePages[$page['uuid']] ?? null) {
            foreach ($alternatePages[$page['uuid']] as $alternateLocale => $alternateSlugs) {
                foreach ($alternateSlugs as $alternateSlug) {
                    $alternateUrl = $this->webspaceManager->findUrlByResourceLocator(
                        $alternateSlug,
                        $this->environment,
                        $alternateLocale,
                        $webspaceKey,
                        $host,
                        $scheme
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
        return 'pages';
    }
}
