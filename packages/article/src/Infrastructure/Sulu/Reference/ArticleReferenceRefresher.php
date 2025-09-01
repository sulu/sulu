<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Sulu\Reference;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolverInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal Modifying or depending on this service may result in unexpected behavior and is not supported.
 *
 * To customize the behavior of this class, override the service by providing your own class that implements
 * ReferenceRefresherInterface, and register it using the same resource key.
 */
class ArticleReferenceRefresher implements ReferenceRefresherInterface
{
    /**
     * @var EntityRepository<ArticleDimensionContentInterface>
     */
    private EntityRepository $articleDimensionContentRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReferenceRepositoryInterface $referenceRepository,
        private ContentViewResolverInterface $contentViewResolver,
        private ContentMergerInterface $contentMerger,
    ) {
        /** @var EntityRepository<ArticleDimensionContentInterface> $repository */
        $repository = $this->entityManager->getRepository(ArticleDimensionContentInterface::class);
        $this->articleDimensionContentRepository = $repository;
    }

    public static function getResourceKey(): string
    {
        return Article::RESOURCE_KEY;
    }

    public function refresh(?array $filter = null): \Generator
    {
        $articleDimensionContentsGenerator = $this->getArticleDimensionContentsGenerator($filter);

        $currentResourceId = null;
        $currentGroup = [];
        /** @var ArticleDimensionContentInterface $dimensionContent */
        foreach ($articleDimensionContentsGenerator as $dimensionContent) {
            $resourceId = $dimensionContent->getResource()->getId();

            if (null === $currentResourceId) {
                $currentResourceId = $resourceId;
            }

            if ($resourceId !== $currentResourceId) {
                // Process finished group
                foreach ($this->resolveArticleDimensionContents($currentGroup) as $merged) {
                    $this->processArticleDimensionContent($merged);
                    yield $merged;
                }

                // Reset for next group
                $currentGroup = [];
                $currentResourceId = $resourceId;
            }

            $currentGroup[] = $dimensionContent;
        }

        // Process the last group if present
        if ([] !== $currentGroup) {
            foreach ($this->resolveArticleDimensionContents($currentGroup) as $merged) {
                $this->processArticleDimensionContent($merged);
                yield $merged;
            }
        }
    }

    /**
     * Process a single article dimension content: collect and persist references.
     */
    private function processArticleDimensionContent(ArticleDimensionContentInterface $articleDimensionContent): void
    {
        $referenceCollector = new ReferenceCollector(
            referenceRepository: $this->referenceRepository,
            referenceResourceKey: $articleDimensionContent->getResourceKey(),
            referenceResourceId: (string) $articleDimensionContent->getResourceId(),
            referenceLocale: $articleDimensionContent->getLocale() ?? '',
            referenceTitle: $articleDimensionContent->getTitle() ?? '',
            referenceContext: DimensionContentInterface::STAGE_LIVE === $articleDimensionContent->getStage() ? 'website' : 'admin',
            referenceRouterAttributes: [
                'locale' => $articleDimensionContent->getLocale() ?? '',
            ]
        );

        $contentViews = $this->contentViewResolver->getContentViews(dimensionContent: $articleDimensionContent);

        foreach ($contentViews as $key => $contentView) {
            $this->addReferences(
                $referenceCollector,
                $contentView,
                'template' !== $key ? (string) $key : ''
            );
        }

        $referenceCollector->persistReferences();
    }

    /**
     * @param array{
     *      resourceId: string,
     *      resourceKey: string,
     *      locale: string,
     *      stage: string
     *  }|null $filter
     *
     * @return iterable<ArticleDimensionContentInterface>
     */
    private function getArticleDimensionContentsGenerator(?array $filter = null): iterable
    {
        $queryBuilder = $this->articleDimensionContentRepository->createQueryBuilder('dimensionContent')
            ->where('dimensionContent.version = :version')
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            // Order by resourceId to keep groups intact
            ->orderBy('dimensionContent.article', 'ASC');

        if (null !== $filter) {
            $queryBuilder
                ->join(
                    'dimensionContent.article',
                    'article',
                    Join::WITH,
                    'article.uuid = :resourceId'
                )
                ->andWhere('dimensionContent.locale = :locale OR dimensionContent.locale IS NULL')
                ->andWhere('dimensionContent.stage = :stage')
                ->setParameter('resourceId', $filter['resourceId'])
                ->setParameter('locale', $filter['locale'])
                ->setParameter('stage', $filter['stage']);
        }

        /** @var iterable<ArticleDimensionContentInterface> $result */
        $result = $queryBuilder->getQuery()->toIterable();

        return $result;
    }

    /**
     * @param iterable<ArticleDimensionContentInterface> $articleDimensionContents
     *
     * @return \Generator<ArticleDimensionContentInterface>
     */
    private function resolveArticleDimensionContents(iterable $articleDimensionContents): \Generator
    {
        $groupedArticleDimensionContents = [];
        /** @var ArticleDimensionContentInterface $articleDimensionContent */
        foreach ($articleDimensionContents as $articleDimensionContent) {
            $groupedArticleDimensionContents[$articleDimensionContent->getResource()->getId()][$articleDimensionContent->getStage()][$articleDimensionContent->getLocale()] = $articleDimensionContent;
        }

        foreach ($groupedArticleDimensionContents as $articleDimensionContentByStage) {
            foreach ($articleDimensionContentByStage as $stage => $articleDimensionContentByLocale) {
                $unlocalizedDimensionContent = $articleDimensionContentByLocale[null] ?? null;
                /** @var ArticleDimensionContentInterface $articleDimensionContent */
                foreach ($articleDimensionContentByLocale as $locale => $articleDimensionContent) {
                    if ('' === $locale) {
                        continue;
                    }
                    yield $this->contentMerger->merge(
                        new DimensionContentCollection(
                            $unlocalizedDimensionContent ? [$articleDimensionContent, $unlocalizedDimensionContent] : [$articleDimensionContent],
                            $articleDimensionContent::getEffectiveDimensionAttributes(['locale' => $locale, 'stage' => $stage]),
                            ArticleDimensionContent::class
                        )
                    );
                }
                $unlocalizedDimensionContent = null;
            }
        }
    }

    private function addReferences(ReferenceCollector $referenceCollector, ContentView $contentView, string $path): void
    {
        $content = $contentView->getContent();

        if (\is_iterable($content)) {
            foreach ($content as $key => $value) {
                $keyStr = \is_string($key) || \is_numeric($key) ? (string) $key : '';
                $newPath = \ltrim($path . '.' . $keyStr, '.');
                if ($value instanceof ContentView) {
                    $this->addReferences($referenceCollector, $value, $newPath);
                }
            }
        }
        foreach ($contentView->getReferences() as $reference) {
            $referenceCollector->addReference(
                $reference->getResourceKey(),
                (string) $reference->getResourceId(),
                $path
            );
        }
    }
}
