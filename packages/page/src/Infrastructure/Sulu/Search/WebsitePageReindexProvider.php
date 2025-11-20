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

namespace Sulu\Page\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\WebsitePageReindexProviderVisitorInterface;

/**
 * @phpstan-type PageData array{
 *     pageId: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     title: string,
 *     locale: string,
 *     slug: string,
 *     authored: \DateTimeImmutable|null,
 *     webspaceKey: string,
 *     templateKey: string|null,
 *     templateData: array<string, mixed>,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class WebsitePageReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<PageInterface>
     */
    protected EntityRepository $pageRepository;

    /**
     * @var EntityRepository<PageDimensionContentInterface>
     */
    protected EntityRepository $dimensionContentRepository;

    /**
     * @param iterable<WebsitePageReindexProviderVisitorInterface> $visitors
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private iterable $visitors = [],
    ) {
        $repository = $entityManager->getRepository(PageInterface::class);
        $dimensionContentRepository = $entityManager->getRepository(PageDimensionContentInterface::class);

        $this->pageRepository = $repository;
        $this->dimensionContentRepository = $dimensionContentRepository;
    }

    public function total(): ?int
    {
        // Todo: Add correct count for multiple locales.
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $pages = $this->loadPages($reindexConfig->getIdentifiers());

        /** @var PageData $page */
        foreach ($pages as $page) {
            $authoredAt = $page['authored'] ?? $page['changed'];

            $data = [
                'id' => PageInterface::RESOURCE_KEY . '__' . ((string) $page['pageId']) . '__' . $page['locale'],
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => (string) $page['pageId'],
                'locale' => $page['locale'],
                'webspaces' => [$page['webspaceKey']],
                'title' => $page['title'],
                'url' => $page['slug'],
                'content' => [],
                'mediaId' => '',
                'authoredAt' => $authoredAt->format('c'),
            ];

            foreach ($this->visitors as $visitor) {
                $data = $visitor->visit($page, $data);
            }

            yield $data;
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<PageData>
     */
    private function loadPages(array $identifiers = []): iterable
    {
        $qb = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->leftJoin('dimensionContent.route', 'route')
            ->leftJoin('dimensionContent.page', 'page')
            ->leftJoin(
                AccessControl::class,
                'accessControl',
                'WITH',
                'accessControl.entityId = page.uuid AND accessControl.entityClass = :pageClass'
            )
            ->select('IDENTITY(dimensionContent.page) AS pageId')
            ->addSelect('dimensionContent.authored')
            ->addSelect('dimensionContent.changed')
            ->addSelect('dimensionContent.title')
            ->addSelect('dimensionContent.locale')
            ->addSelect('dimensionContent.templateKey')
            ->addSelect('dimensionContent.templateData')
            ->addSelect('route.slug')
            ->addSelect('page.webspaceKey')
            ->where('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.locale IS NOT NULL')
            ->andWhere('dimensionContent.version = :version')
            ->andWhere('accessControl.id IS NULL');

        $parameters = [
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
            'pageClass' => Page::class,
        ];

        if (0 < \count($identifiers)) {
            $conditions = [];

            foreach ($identifiers as $index => $identifier) {
                $resourceKey = \explode('__', $identifier)[0];

                if (PageInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('__', $identifier)[1] ?? '';
                $locale = \explode('__', $identifier)[2] ?? '';

                $conditions[] = "(dimensionContent.page = :id{$index} AND dimensionContent.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            if (!$conditions) {
                return [];
            }

            $qb->andWhere(\implode(' OR ', $conditions));
        }

        $qb->setParameters($parameters);

        /** @var iterable<PageData> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'website';
    }
}
