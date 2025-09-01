<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Sulu\Reference;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolverInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;

/**
 * @internal Modifying or depending on this service may result in unexpected behavior and is not supported.
 *
 * To customize the behavior of this class, override the service by providing your own class that implements
 * ReferenceRefresherInterface, and register it using the same resource key.
 */
class SnippetReferenceRefresher implements ReferenceRefresherInterface
{
    /**
     * @var EntityRepository<SnippetDimensionContentInterface>
     */
    private EntityRepository $snippetDimensionContentRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReferenceRepositoryInterface $referenceRepository,
        private ContentViewResolverInterface $contentViewResolver,
        private ContentMergerInterface $contentMerger,
    ) {
        /** @var EntityRepository<SnippetDimensionContentInterface> $repository */
        $repository = $this->entityManager->getRepository(SnippetDimensionContentInterface::class);
        $this->snippetDimensionContentRepository = $repository;
    }

    public static function getResourceKey(): string
    {
        return Snippet::RESOURCE_KEY;
    }

    public function refresh(?array $filter = null): \Generator
    {
        $snippetDimensionContentsGenerator = $this->getSnippetDimensionContentsGenerator($filter);

        $currentResourceId = null;
        $currentGroup = [];
        /** @var SnippetDimensionContentInterface $dimensionContent */
        foreach ($snippetDimensionContentsGenerator as $dimensionContent) {
            $resourceId = $dimensionContent->getResource()->getId();

            if (null === $currentResourceId) {
                $currentResourceId = $resourceId;
            }

            if ($resourceId !== $currentResourceId) {
                // Process finished group
                foreach ($this->resolveSnippetDimensionContents($currentGroup) as $merged) {
                    $this->processSnippetDimensionContent($merged);
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
            foreach ($this->resolveSnippetDimensionContents($currentGroup) as $merged) {
                $this->processSnippetDimensionContent($merged);
                yield $merged;
            }
        }
    }

    /**
     * Process a single snippet dimension content: collect and persist references.
     */
    private function processSnippetDimensionContent(SnippetDimensionContentInterface $snippetDimensionContent): void
    {
        $referenceCollector = new ReferenceCollector(
            referenceRepository: $this->referenceRepository,
            referenceResourceKey: $snippetDimensionContent->getResourceKey(),
            referenceResourceId: (string) $snippetDimensionContent->getResource()->getId(),
            referenceLocale: $snippetDimensionContent->getLocale() ?? '',
            referenceTitle: $snippetDimensionContent->getTitle() ?? '',
            referenceContext: DimensionContentInterface::STAGE_LIVE === $snippetDimensionContent->getStage() ? 'website' : 'admin',
            referenceRouterAttributes: [
                'locale' => $snippetDimensionContent->getLocale() ?? '',
            ]
        );

        $contentViews = $this->contentViewResolver->getContentViews(dimensionContent: $snippetDimensionContent);

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
     * @return iterable<SnippetDimensionContentInterface>
     */
    private function getSnippetDimensionContentsGenerator(?array $filter = null): iterable
    {
        $queryBuilder = $this->snippetDimensionContentRepository->createQueryBuilder('dimensionContent')
            ->where('dimensionContent.version = :version')
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            // Order by resourceId to keep groups intact
            ->orderBy('dimensionContent.snippet', 'ASC');

        if (null !== $filter) {
            $queryBuilder
                ->join(
                    'dimensionContent.snippet',
                    'snippet',
                    Join::WITH,
                    'snippet.uuid = :resourceId'
                )
                ->andWhere('dimensionContent.locale = :locale OR dimensionContent.locale IS NULL')
                ->andWhere('dimensionContent.stage = :stage')
                ->setParameter('resourceId', $filter['resourceId'])
                ->setParameter('locale', $filter['locale'])
                ->setParameter('stage', $filter['stage']);
        }

        /** @var iterable<SnippetDimensionContentInterface> $result */
        $result = $queryBuilder->getQuery()->toIterable();

        return $result;
    }

    /**
     * @param iterable<SnippetDimensionContentInterface> $snippetDimensionContents
     *
     * @return \Generator<SnippetDimensionContentInterface>
     */
    private function resolveSnippetDimensionContents(iterable $snippetDimensionContents): \Generator
    {
        $groupedSnippetDimensionContents = [];
        /** @var SnippetDimensionContentInterface $snippetDimensionContent */
        foreach ($snippetDimensionContents as $snippetDimensionContent) {
            $groupedSnippetDimensionContents[$snippetDimensionContent->getResource()->getId()][$snippetDimensionContent->getStage()][$snippetDimensionContent->getLocale()] = $snippetDimensionContent;
        }

        foreach ($groupedSnippetDimensionContents as $snippetDimensionContentByStage) {
            foreach ($snippetDimensionContentByStage as $stage => $snippetDimensionContentByLocale) {
                $unlocalizedDimensionContent = $snippetDimensionContentByLocale[null] ?? null;
                /** @var SnippetDimensionContentInterface $snippetDimensionContent */
                foreach ($snippetDimensionContentByLocale as $locale => $snippetDimensionContent) {
                    if ('' === $locale) {
                        continue;
                    }
                    yield $this->contentMerger->merge(
                        new DimensionContentCollection(
                            $unlocalizedDimensionContent ? [$snippetDimensionContent, $unlocalizedDimensionContent] : [$snippetDimensionContent],
                            $snippetDimensionContent::getEffectiveDimensionAttributes(['locale' => $locale, 'stage' => $stage]),
                            SnippetDimensionContent::class
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
