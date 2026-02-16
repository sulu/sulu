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

namespace Sulu\Snippet\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;
use Sulu\Snippet\Infrastructure\Sulu\Search\Visitor\AdminSnippetReindexProviderEnhancerInterface;

/**
 * @phpstan-type Snippet array{
 *     snippetId: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     title: string,
 *     locale: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminSnippetReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<SnippetDimensionContentInterface>
     */
    private EntityRepository $dimensionContentRepository;

    /**
     * @param iterable<AdminSnippetReindexProviderEnhancerInterface> $enhancers
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly iterable $enhancers = [],
    ) {
        $this->dimensionContentRepository = $entityManager->getRepository(SnippetDimensionContentInterface::class);
    }

    public function total(): ?int
    {
        // Todo: Add correct count for multiple locales.
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $snippets = $this->loadSnippets($reindexConfig->getIdentifiers());

        /** @var Snippet $snippet */
        foreach ($snippets as $snippet) {
            $data = [
                'id' => SnippetInterface::RESOURCE_KEY . '__' . ((string) $snippet['snippetId']) . '__' . $snippet['locale'],
                'resourceKey' => SnippetInterface::RESOURCE_KEY,
                'resourceId' => (string) $snippet['snippetId'],
                'changedAt' => $snippet['changed']->format('c'),
                'createdAt' => $snippet['created']->format('c'),
                'title' => $snippet['title'],
                'locale' => $snippet['locale'],
                'securityContext' => SnippetAdmin::SECURITY_CONTEXT,
            ];

            foreach ($this->enhancers as $enhancer) {
                $data = $enhancer->enhanceDocument($snippet, $data);
            }

            yield $data;
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Snippet>
     */
    private function loadSnippets(array $identifiers = []): iterable
    {
        $qb = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->select('IDENTITY(dimensionContent.snippet) AS snippetId')
            ->addSelect('dimensionContent.created')
            ->addSelect('dimensionContent.changed')
            ->addSelect('dimensionContent.title')
            ->addSelect('dimensionContent.locale')
            ->where('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.locale IS NOT NULL')
            ->andWhere('dimensionContent.version = :version');

        $parameters = [
            'stage' => DimensionContentInterface::STAGE_DRAFT,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];

        if (0 < \count($identifiers)) {
            $conditions = [];

            foreach ($identifiers as $index => $identifier) {
                $resourceKey = \explode('__', $identifier)[0];

                if (SnippetInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('__', $identifier)[1] ?? '';
                $locale = \explode('__', $identifier)[2] ?? '';

                $conditions[] = "(dimensionContent.snippet = :id{$index} AND dimensionContent.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            if (!$conditions) {
                return [];
            }

            $qb->andWhere(\implode(' OR ', $conditions));
        }

        foreach ($parameters as $parameterKey => $parameterValue) {
            $qb->setParameter($parameterKey, $parameterValue);
        }

        foreach ($this->enhancers as $enhancer) {
            $enhancer->enhanceQuery($qb);
        }

        /** @var iterable<Snippet> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
