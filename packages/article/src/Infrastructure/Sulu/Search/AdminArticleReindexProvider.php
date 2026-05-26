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

namespace Sulu\Article\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\AdminArticleReindexProviderEnhancerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @phpstan-type Article array{
 *     articleId: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     title: string,
 *     locale: string,
 *     templateKey: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminArticleReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<ArticleDimensionContentInterface>
     */
    private EntityRepository $dimensionContentRepository;

    /**
     * @param iterable<AdminArticleReindexProviderEnhancerInterface> $enhancers
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly GroupProviderInterface $groupProvider,
        private readonly iterable $enhancers = [],
    ) {
        $this->dimensionContentRepository = $entityManager->getRepository(ArticleDimensionContentInterface::class);
    }

    public function total(): ?int
    {
        // Todo: Add correct count for multiple locales.
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $articles = $this->loadArticles($reindexConfig->getIdentifiers());
        /** @var FormGroup[] $groups */
        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);

        /** @var Article $article */
        foreach ($articles as $article) {
            $groupIdentifier = null;

            foreach ($groups as $group) {
                if (\in_array($article['templateKey'], $group->templates)) {
                    $groupIdentifier = $group->identifier;
                    break;
                }
            }

            $securityContext = ArticleAdmin::getArticleSecurityContext($groupIdentifier ?? GroupProviderInterface::DEFAULT_GROUP);
            if (1 === \count($groups) || null === $groupIdentifier || GroupProviderInterface::DEFAULT_GROUP === $groupIdentifier) {
                $securityContext = ArticleAdmin::SECURITY_CONTEXT;
            }

            $data = [
                'id' => ArticleInterface::RESOURCE_KEY . '__' . ((string) $article['articleId']) . '__' . $article['locale'],
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => (string) $article['articleId'],
                'changedAt' => $article['changed']->format('c'),
                'createdAt' => $article['created']->format('c'),
                'title' => $article['title'],
                'locale' => $article['locale'],
                'metadata' => [
                    'group' => $groupIdentifier,
                ],
                'securityContext' => $securityContext,
            ];

            foreach ($this->enhancers as $enhancer) {
                $data = $enhancer->enhanceDocument($article, $data);
            }

            yield $data;
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Article>
     */
    private function loadArticles(array $identifiers = []): iterable
    {
        $qb = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->select('IDENTITY(dimensionContent.article) AS articleId')
            ->addSelect('dimensionContent.created')
            ->addSelect('dimensionContent.changed')
            ->addSelect('dimensionContent.title')
            ->addSelect('dimensionContent.locale')
            ->addSelect('dimensionContent.templateKey')
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

                if (ArticleInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('__', $identifier)[1] ?? '';
                $locale = \explode('__', $identifier)[2] ?? '';

                $conditions[] = "(dimensionContent.article = :id{$index} AND dimensionContent.locale = :locale{$index})";
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

        /** @var iterable<Article> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
