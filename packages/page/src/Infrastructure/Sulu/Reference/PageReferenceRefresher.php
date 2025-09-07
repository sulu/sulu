<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Reference;

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
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;

/**
 * @internal Modifying or depending on this service may result in unexpected behavior and is not supported.
 *
 * To customize the behavior of this class, override the service by providing your own class that implements
 * ReferenceRefresherInterface, and register it using the same resource key.
 */
class PageReferenceRefresher implements ReferenceRefresherInterface
{
    /**
     * @var EntityRepository<PageDimensionContentInterface>
     */
    private EntityRepository $pageDimensionContentRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReferenceRepositoryInterface $referenceRepository,
        private ContentViewResolverInterface $contentViewResolver,
        private ContentMergerInterface $contentMerger,
    ) {
        /** @var EntityRepository<PageDimensionContentInterface> $repository */
        $repository = $this->entityManager->getRepository(PageDimensionContentInterface::class);
        $this->pageDimensionContentRepository = $repository;
    }

    public static function getResourceKey(): string
    {
        return PageInterface::RESOURCE_KEY;
    }

    public function refresh(?array $filter = null): \Generator
    {
        $pageDimensionContentsGenerator = $this->getPageDimensionContentsGenerator($filter);

        $currentResourceId = null;
        $currentGroup = [];
        /** @var PageDimensionContentInterface $dimensionContent */
        foreach ($pageDimensionContentsGenerator as $dimensionContent) {
            $resourceId = $dimensionContent->getResourceId();

            if (null === $currentResourceId) {
                $currentResourceId = $resourceId;
            }

            if ($resourceId !== $currentResourceId) {
                // Process finished group
                foreach ($this->resolvePageDimensionContents($currentGroup) as $merged) {
                    $this->processPageDimensionContent($merged);
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
            foreach ($this->resolvePageDimensionContents($currentGroup) as $merged) {
                $this->processPageDimensionContent($merged);
                yield $merged;
            }
        }
    }

    /**
     * Process a single page dimension content: collect and persist references.
     */
    private function processPageDimensionContent(PageDimensionContentInterface $pageDimensionContent): void
    {
        $referenceCollector = new ReferenceCollector(
            referenceRepository: $this->referenceRepository,
            referenceResourceKey: $pageDimensionContent->getResourceKey(),
            referenceResourceId: (string) $pageDimensionContent->getResourceId(),
            referenceLocale: $pageDimensionContent->getLocale() ?? '',
            referenceTitle: $pageDimensionContent->getTitle() ?? '',
            referenceContext: $pageDimensionContent->getStage(),
            referenceRouterAttributes: [
                'locale' => $pageDimensionContent->getLocale() ?? '',
                'webspace' => $pageDimensionContent->getResource()->getWebspaceKey(),
            ]
        );

        $contentViews = $this->contentViewResolver->getContentViews(dimensionContent: $pageDimensionContent);

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
     * @return iterable<PageDimensionContentInterface>
     */
    private function getPageDimensionContentsGenerator(?array $filter = null): iterable
    {
        $queryBuilder = $this->pageDimensionContentRepository->createQueryBuilder('dimensionContent')
            ->where('dimensionContent.version = :version')
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            // Order by resourceId to keep groups intact
            ->orderBy('dimensionContent.page', 'ASC');

        if (null !== $filter) {
            $queryBuilder
                ->join(
                    'dimensionContent.page',
                    'page',
                    Join::WITH,
                    'page.uuid = :resourceId'
                )
                ->andWhere('dimensionContent.locale = :locale OR dimensionContent.locale IS NULL')
                ->andWhere('dimensionContent.stage = :stage')
                ->setParameter('resourceId', $filter['resourceId'])
                ->setParameter('locale', $filter['locale'])
                ->setParameter('stage', $filter['stage']);
        }

        /** @var iterable<PageDimensionContentInterface> $result */
        $result = $queryBuilder->getQuery()->toIterable();

        return $result;
    }

    /**
     * @param iterable<PageDimensionContentInterface> $pageDimensionContents
     *
     * @return \Generator<PageDimensionContentInterface>
     */
    private function resolvePageDimensionContents(iterable $pageDimensionContents): \Generator
    {
        $groupedPageDimensionContents = [];
        /** @var PageDimensionContentInterface $pageDimensionContent */
        foreach ($pageDimensionContents as $pageDimensionContent) {
            $groupedPageDimensionContents[$pageDimensionContent->getResourceId()][$pageDimensionContent->getStage()][$pageDimensionContent->getLocale()] = $pageDimensionContent;
        }

        foreach ($groupedPageDimensionContents as $pageDimensionContentByStage) {
            foreach ($pageDimensionContentByStage as $stage => $pageDimensionContentByLocale) {
                $unlocalizedDimensionContent = $pageDimensionContentByLocale[null] ?? null;
                /** @var PageDimensionContentInterface $pageDimensionContent */
                foreach ($pageDimensionContentByLocale as $locale => $pageDimensionContent) {
                    if ('' === $locale) {
                        continue;
                    }
                    yield $this->contentMerger->merge(
                        new DimensionContentCollection(
                            $unlocalizedDimensionContent ? [$pageDimensionContent, $unlocalizedDimensionContent] : [$pageDimensionContent],
                            $pageDimensionContent::getEffectiveDimensionAttributes(['locale' => $locale, 'stage' => $stage]),
                            PageDimensionContent::class
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
