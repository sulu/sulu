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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Reference;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ExampleReferenceRefresher implements ReferenceRefresherInterface
{
    /**
     * @var EntityRepository<ExampleDimensionContent>
     */
    private EntityRepository $exampleDimensionContentRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReferenceRepositoryInterface $referenceRepository,
        private ContentViewResolverInterface $contentViewResolver,
        private ContentMergerInterface $contentMerger,
    ) {
        /** @var EntityRepository<ExampleDimensionContent> $repository */
        $repository = $this->entityManager->getRepository(ExampleDimensionContent::class);
        $this->exampleDimensionContentRepository = $repository;
    }

    public static function getResourceKey(): string
    {
        return Example::RESOURCE_KEY;
    }

    public function refresh(?array $filter = null): \Generator
    {
        $exampleDimensionContentsGenerator = $this->getExampleDimensionContentsGenerator($filter);

        $currentResourceId = null;
        $currentGroup = [];
        /** @var ExampleDimensionContent $dimensionContent */
        foreach ($exampleDimensionContentsGenerator as $dimensionContent) {
            $resourceId = $dimensionContent->getResourceId();

            if (null === $currentResourceId) {
                $currentResourceId = $resourceId;
            }

            if ($resourceId !== $currentResourceId) {
                // Process finished group
                foreach ($this->resolveExampleDimensionContents($currentGroup) as $merged) {
                    $this->processExampleDimensionContent($merged);
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
            foreach ($this->resolveExampleDimensionContents($currentGroup) as $merged) {
                $this->processExampleDimensionContent($merged);
                yield $merged;
            }
        }
    }

    /**
     * Process a single example dimension content: collect and persist references.
     */
    private function processExampleDimensionContent(ExampleDimensionContent $exampleDimensionContent): void
    {
        $referenceCollector = new ReferenceCollector(
            referenceRepository: $this->referenceRepository,
            referenceResourceKey: $exampleDimensionContent->getResourceKey(),
            referenceResourceId: (string) $exampleDimensionContent->getResourceId(),
            referenceLocale: $exampleDimensionContent->getLocale() ?? '',
            referenceTitle: $exampleDimensionContent->getTitle() ?? '',
            referenceContext: $exampleDimensionContent->getStage(),
            referenceRouterAttributes: [
                'locale' => $exampleDimensionContent->getLocale() ?? '',
            ]
        );

        $contentViews = $this->contentViewResolver->getContentViews(dimensionContent: $exampleDimensionContent);

        foreach ($contentViews as $key => $contentView) {
            $basePath = 'template' !== $key ? (string) $key : '';
            $references = $contentView->getAllReferencesRecursively($basePath);

            foreach ($references as $reference) {
                $referenceCollector->addReference(
                    $reference->getResourceKey(),
                    (string) $reference->getResourceId(),
                    $reference->getPath()
                );
            }
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
     * @return iterable<ExampleDimensionContent>
     */
    private function getExampleDimensionContentsGenerator(?array $filter = null): iterable
    {
        $queryBuilder = $this->exampleDimensionContentRepository->createQueryBuilder('dimensionContent')
            ->where('dimensionContent.version = :version')
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            // Order by resourceId to keep groups intact
            ->orderBy('dimensionContent.example', 'ASC');

        if (null !== $filter) {
            $queryBuilder
                ->join(
                    'dimensionContent.example',
                    'example',
                    Join::WITH,
                    'example.id = :resourceId'
                )
                ->andWhere('dimensionContent.locale = :locale OR dimensionContent.locale IS NULL')
                ->andWhere('dimensionContent.stage = :stage')
                ->setParameter('resourceId', $filter['resourceId'])
                ->setParameter('locale', $filter['locale'])
                ->setParameter('stage', $filter['stage']);
        }

        /** @var iterable<ExampleDimensionContent> $result */
        $result = $queryBuilder->getQuery()->toIterable();

        return $result;
    }

    /**
     * @param iterable<ExampleDimensionContent> $exampleDimensionContents
     *
     * @return \Generator<ExampleDimensionContent>
     */
    private function resolveExampleDimensionContents(iterable $exampleDimensionContents): \Generator
    {
        $groupedExampleDimensionContents = [];
        /** @var ExampleDimensionContent $exampleDimensionContent */
        foreach ($exampleDimensionContents as $exampleDimensionContent) {
            $groupedExampleDimensionContents[$exampleDimensionContent->getResourceId()][$exampleDimensionContent->getStage()][$exampleDimensionContent->getLocale()] = $exampleDimensionContent;
        }

        foreach ($groupedExampleDimensionContents as $exampleDimensionContentByStage) {
            foreach ($exampleDimensionContentByStage as $stage => $exampleDimensionContentByLocale) {
                $unlocalizedDimensionContent = $exampleDimensionContentByLocale[null] ?? null;
                /** @var ExampleDimensionContent $exampleDimensionContent */
                foreach ($exampleDimensionContentByLocale as $locale => $exampleDimensionContent) {
                    if ('' === $locale) {
                        continue;
                    }
                    yield $this->contentMerger->merge(
                        new DimensionContentCollection(
                            $unlocalizedDimensionContent ? [$exampleDimensionContent, $unlocalizedDimensionContent] : [$exampleDimensionContent],
                            $exampleDimensionContent::getEffectiveDimensionAttributes(['locale' => $locale, 'stage' => $stage]),
                            ExampleDimensionContent::class
                        )
                    );
                }
                $unlocalizedDimensionContent = null;
            }
        }
    }
}
