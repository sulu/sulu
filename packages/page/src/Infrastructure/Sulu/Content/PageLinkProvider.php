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

namespace Sulu\Page\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkUrlTrait;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal This class is an integration to the SuluMarkupBundle and can be changed any time.
 *           Use Symfony dependency injection service decoration and change the behaviour if you need.
 */
final class PageLinkProvider implements LinkProviderInterface
{
    use LinkUrlTrait;

    /**
     * @param class-string<PageDimensionContentInterface> $pageContentClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RouteGeneratorInterface $routeGenerator,
        private readonly ReferenceStoreInterface $referenceStore,
        private readonly TranslatorInterface $translator,
        private readonly string $pageContentClass,
    ) {
    }

    public function getConfigurationBuilder(): LinkConfigurationBuilder
    {
        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('sulu_page.pages', [], 'admin'))
            ->setResourceKey(PageInterface::RESOURCE_KEY)
            ->setListAdapter('column_list')
            ->setDisplayProperties(['title'])
            ->setOverlayTitle($this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'))
            ->setEmptyText($this->translator->trans('sulu_page.no_page_selected', [], 'admin'))
            ->setIcon('su-document');
    }

    public function preload(array $hrefs, string $locale, bool $published = true): iterable
    {
        if ([] === $hrefs) {
            return [];
        }

        $stage = $published ? DimensionContentInterface::STAGE_LIVE : DimensionContentInterface::STAGE_DRAFT;
        $version = DimensionContentInterface::CURRENT_VERSION;

        $rows = $this->findPagesByUuids($hrefs, $locale, $stage, $version);

        $internalLinkTargetUuids = [];
        foreach ($rows as $row) {
            if ('page' === $row['linkProvider'] && \is_array($row['linkData']) && \is_string($row['linkData']['href'] ?? null)) {
                $internalLinkTargetUuids[] = $row['linkData']['href'];
            }
        }

        $targetRoutes = [];
        if ([] !== $internalLinkTargetUuids) {
            $targetRows = $this->findTargetPageRoutes($internalLinkTargetUuids, $locale, $stage, $version);

            foreach ($targetRows as $targetRow) {
                $targetRoutes[$targetRow['uuid']] = [
                    'slug' => $targetRow['slug'],
                    'webspaceKey' => $targetRow['webspaceKey'],
                ];
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $this->referenceStore->add($row['uuid'], PageInterface::RESOURCE_KEY);

            $url = $this->resolveUrl($row, $targetRoutes, $locale);
            if (null === $url) {
                continue;
            }

            $result[] = new LinkItem(
                $row['uuid'],
                (string) ($row['title'] ?? ''),
                $url,
                $published,
            );
        }

        return $result;
    }

    /**
     * @param string[] $uuids
     *
     * @return list<array{uuid: string, title: ?string, slug: ?string, webspaceKey: string, linkProvider: ?string, linkData: ?array<string, mixed>}>
     */
    private function findPagesByUuids(array $uuids, string $locale, string $stage, int $version): array
    {
        /** @var list<array{uuid: string, title: ?string, slug: ?string, webspaceKey: string, linkProvider: ?string, linkData: ?array<string, mixed>}> */
        return $this->entityManager->createQueryBuilder()
            ->select('page.uuid', 'dimensionContent.title', 'route.slug', 'page.webspaceKey',
                'dimensionContent.linkProvider', 'dimensionContent.linkData')
            ->from($this->pageContentClass, 'dimensionContent')
            ->join('dimensionContent.page', 'page')
            ->leftJoin('dimensionContent.route', 'route')
            ->where('page.uuid IN (:uuids)')
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('uuids', $uuids)
            ->setParameter('locale', $locale)
            ->setParameter('stage', $stage)
            ->setParameter('version', $version)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param string[] $targetUuids
     *
     * @return list<array{uuid: string, slug: ?string, webspaceKey: string}>
     */
    private function findTargetPageRoutes(array $targetUuids, string $locale, string $stage, int $version): array
    {
        /** @var list<array{uuid: string, slug: ?string, webspaceKey: string}> */
        return $this->entityManager->createQueryBuilder()
            ->select('page.uuid', 'route.slug', 'page.webspaceKey')
            ->from($this->pageContentClass, 'dimensionContent')
            ->join('dimensionContent.page', 'page')
            ->leftJoin('dimensionContent.route', 'route')
            ->where('page.uuid IN (:targetUuids)')
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('targetUuids', $targetUuids)
            ->setParameter('locale', $locale)
            ->setParameter('stage', $stage)
            ->setParameter('version', $version)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param array{uuid: string, title: ?string, slug: ?string, webspaceKey: string, linkProvider: ?string, linkData: ?array<string, mixed>} $row
     * @param array<string, array{slug: ?string, webspaceKey: string}> $targetRoutes
     */
    private function resolveUrl(array $row, array $targetRoutes, string $locale): ?string
    {
        $linkProvider = $row['linkProvider'];
        $linkData = $row['linkData'];

        if ('external' === $linkProvider && \is_array($linkData) && \is_string($linkData['href'] ?? null)) {
            return $this->appendQueryAndAnchor($linkData['href'], $linkData);
        }

        if ('page' === $linkProvider && \is_array($linkData) && \is_string($linkData['href'] ?? null)) {
            $targetUuid = $linkData['href'];
            $target = $targetRoutes[$targetUuid] ?? null;
            if (null === $target || null === $target['slug']) {
                return null;
            }

            $url = $this->routeGenerator->generate(
                $target['slug'],
                $locale,
                $target['webspaceKey'],
            );

            return $this->appendQueryAndAnchor($url, $linkData);
        }

        // Unknown link providers (or no link provider) fall through to using the page's own route
        if (null === $row['slug']) {
            return null;
        }

        return $this->routeGenerator->generate(
            $row['slug'],
            $locale,
            $row['webspaceKey'],
        );
    }
}
