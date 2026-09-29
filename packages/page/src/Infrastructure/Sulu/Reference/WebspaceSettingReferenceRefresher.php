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

namespace Sulu\Page\Infrastructure\Sulu\Reference;

use Doctrine\Common\Collections\ArrayCollection;
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
use Sulu\Page\Domain\Model\WebspaceSettingDimensionContent;
use Sulu\Page\Domain\Model\WebspaceSettingDimensionContentInterface;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;

/**
 * @internal Modifying or depending on this service may result in unexpected behavior and is not supported.
 *
 * To customize the behavior of this class, override the service by providing your own class that implements
 * ReferenceRefresherInterface, and register it using the same resource key.
 */
class WebspaceSettingReferenceRefresher implements ReferenceRefresherInterface
{
    /**
     * @var EntityRepository<WebspaceSettingDimensionContentInterface>
     */
    private EntityRepository $dimensionContentRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReferenceRepositoryInterface $referenceRepository,
        private ContentViewResolverInterface $contentViewResolver,
        private ContentMergerInterface $contentMerger,
    ) {
        $this->dimensionContentRepository = $this->entityManager->getRepository(WebspaceSettingDimensionContentInterface::class);
    }

    public static function getResourceKey(): string
    {
        return WebspaceSettingInterface::RESOURCE_KEY;
    }

    public function refresh(?array $filter = null): \Generator
    {
        $currentResourceId = null;
        $currentGroup = [];
        foreach ($this->getDimensionContents($filter) as $dimensionContent) {
            $resourceId = $dimensionContent->getResource()->getId();

            if (null !== $currentResourceId && $resourceId !== $currentResourceId) {
                yield from $this->processGroup($currentGroup);
                $currentGroup = [];
            }

            $currentResourceId = $resourceId;
            $currentGroup[] = $dimensionContent;
        }

        yield from $this->processGroup($currentGroup);
    }

    /**
     * @param WebspaceSettingDimensionContentInterface[] $dimensionContents
     *
     * @return \Generator<WebspaceSettingDimensionContentInterface>
     */
    private function processGroup(array $dimensionContents): \Generator
    {
        foreach ($this->mergeDimensionContents($dimensionContents) as $dimensionContent) {
            $this->collectReferences($dimensionContent);

            yield $dimensionContent;
        }
    }

    private function collectReferences(WebspaceSettingDimensionContentInterface $dimensionContent): void
    {
        $webspaceSetting = $dimensionContent->getResource();
        $locale = $dimensionContent->getLocale() ?? '';

        $referenceCollector = new ReferenceCollector(
            referenceRepository: $this->referenceRepository,
            referenceResourceKey: $dimensionContent::getResourceKey(),
            referenceResourceId: $webspaceSetting->getId(),
            referenceLocale: $locale,
            referenceTitle: $webspaceSetting->getWebspaceKey(),
            referenceContext: $dimensionContent->getStage(),
            referenceRouterAttributes: [
                'locale' => $locale,
                'webspace' => $webspaceSetting->getWebspaceKey(),
            ],
        );

        foreach ($this->contentViewResolver->getContentViews(dimensionContent: $dimensionContent) as $key => $contentView) {
            $basePath = 'template' !== $key ? (string) $key : '';

            foreach ($contentView->getAllReferencesRecursively($basePath) as $reference) {
                $referenceCollector->addReference(
                    $reference->getResourceKey(),
                    (string) $reference->getResourceId(),
                    $reference->getPath(),
                );
            }
        }

        $referenceCollector->persistReferences();
    }

    /**
     * @param array{
     *     resourceId: string,
     *     resourceKey: string,
     *     locale: string,
     *     stage: string
     * }|null $filter
     *
     * @return iterable<WebspaceSettingDimensionContentInterface>
     */
    private function getDimensionContents(?array $filter): iterable
    {
        $queryBuilder = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->where('dimensionContent.version = :version')
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->orderBy('dimensionContent.webspaceSetting', 'ASC');

        if (null !== $filter) {
            $queryBuilder
                ->join('dimensionContent.webspaceSetting', 'webspaceSetting', Join::WITH, 'webspaceSetting.webspaceKey = :resourceId')
                ->andWhere('dimensionContent.locale = :locale OR dimensionContent.locale IS NULL')
                ->andWhere('dimensionContent.stage = :stage')
                ->setParameter('resourceId', $filter['resourceId'])
                ->setParameter('locale', $filter['locale'])
                ->setParameter('stage', $filter['stage']);
        }

        /** @var iterable<WebspaceSettingDimensionContentInterface> */
        return $queryBuilder->getQuery()->toIterable();
    }

    /**
     * @param WebspaceSettingDimensionContentInterface[] $dimensionContents
     *
     * @return \Generator<WebspaceSettingDimensionContentInterface>
     */
    private function mergeDimensionContents(array $dimensionContents): \Generator
    {
        $dimensionContentsByStage = [];
        foreach ($dimensionContents as $dimensionContent) {
            $dimensionContentsByStage[$dimensionContent->getStage()][$dimensionContent->getLocale() ?? ''] = $dimensionContent;
        }

        foreach ($dimensionContentsByStage as $stage => $dimensionContentsByLocale) {
            $unlocalizedDimensionContent = $dimensionContentsByLocale[''] ?? null;

            foreach ($dimensionContentsByLocale as $locale => $dimensionContent) {
                if ('' === $locale) {
                    continue;
                }

                yield $this->contentMerger->merge(new DimensionContentCollection(
                    new ArrayCollection($unlocalizedDimensionContent ? [$dimensionContent, $unlocalizedDimensionContent] : [$dimensionContent]),
                    $dimensionContent::getEffectiveDimensionAttributes(['locale' => (string) $locale, 'stage' => $stage]),
                    WebspaceSettingDimensionContent::class,
                ));
            }
        }
    }
}
