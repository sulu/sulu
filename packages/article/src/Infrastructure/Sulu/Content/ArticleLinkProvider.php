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

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal This class is an integration to the SuluMarkupBundle and can be changed any time.
 *           Use Symfony dependency injection service decoration and change the behaviour if you need.
 */
final class ArticleLinkProvider implements LinkProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RouteGeneratorInterface $routeGenerator,
        private readonly RequestAnalyzerInterface $requestAnalyzer,
        private readonly ReferenceStoreInterface $referenceStore,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getConfigurationBuilder(): LinkConfigurationBuilder
    {
        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('sulu_article.articles', [], 'admin'))
            ->setResourceKey(ArticleInterface::RESOURCE_KEY)
            ->setListAdapter('table')
            ->setDisplayProperties(['title'])
            ->setOverlayTitle($this->translator->trans('sulu_article.selection_overlay_title', [], 'admin'))
            ->setEmptyText($this->translator->trans('sulu_article.no_article_selected', [], 'admin'))
            ->setIcon('su-document');
    }

    public function preload(array $hrefs, string $locale, bool $published = true): iterable
    {
        if ([] === $hrefs) {
            return [];
        }

        $stage = $published ? DimensionContentInterface::STAGE_LIVE : DimensionContentInterface::STAGE_DRAFT;
        $version = DimensionContentInterface::CURRENT_VERSION;

        $requestWebspace = $this->requestAnalyzer->getWebspace()?->getKey();
        $rows = $this->findArticlesByUuids($hrefs, $locale, $stage, $version, $requestWebspace);

        $result = [];
        foreach ($rows as $row) {
            $this->referenceStore->add($row['uuid'], ArticleInterface::RESOURCE_KEY);

            if (null === $row['slug']) {
                continue;
            }

            $webspace = $this->determineWebspace($row, $requestWebspace);

            $url = $this->routeGenerator->generate(
                $row['slug'],
                $locale,
                $webspace,
            );

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
     * @return list<array{uuid: string, title: ?string, slug: ?string, mainWebspace: ?string, additionalWebspace?: ?string}>
     */
    private function findArticlesByUuids(
        array $uuids,
        string $locale,
        string $stage,
        int $version,
        ?string $requestWebspace,
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('article.uuid', 'dimensionContent.title', 'route.slug', 'dimensionContent.mainWebspace')
            ->from(ArticleDimensionContent::class, 'dimensionContent')
            ->join('dimensionContent.article', 'article')
            ->leftJoin('dimensionContent.route', 'route')
            ->where('article.uuid IN (:uuids)')
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('uuids', $uuids)
            ->setParameter('locale', $locale)
            ->setParameter('stage', $stage)
            ->setParameter('version', $version);

        if (null !== $requestWebspace) {
            $queryBuilder
                ->addSelect('additionalWebspace.additionalWebspace')
                ->leftJoin(
                    'dimensionContent.additionalWebspaces',
                    'additionalWebspace',
                    'WITH',
                    'additionalWebspace.additionalWebspace = :requestWebspace',
                )
                ->setParameter('requestWebspace', $requestWebspace);
        }

        /** @var list<array{uuid: string, title: ?string, slug: ?string, mainWebspace: ?string, additionalWebspace?: ?string}> */
        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param array{mainWebspace: ?string, additionalWebspace?: ?string} $row
     */
    private function determineWebspace(array $row, ?string $requestWebspace): ?string
    {
        if (null === $requestWebspace) {
            return $row['mainWebspace'];
        }

        if ($row['mainWebspace'] === $requestWebspace || ($row['additionalWebspace'] ?? null) !== null) {
            return $requestWebspace;
        }

        return $row['mainWebspace'];
    }
}
