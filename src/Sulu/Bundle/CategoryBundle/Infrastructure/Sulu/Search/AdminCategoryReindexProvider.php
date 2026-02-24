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

namespace Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Search\Visitor\AdminCategoryReindexProviderEnhancerInterface;

/**
 * @phpstan-type Category array{
 *     id: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     translation: string,
 *     locale: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminCategoryReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<CategoryTranslationInterface>
     */
    private EntityRepository $categoryTranslationRepository;

    /**
     * @param iterable<AdminCategoryReindexProviderEnhancerInterface> $enhancers
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly iterable $enhancers = [],
    ) {
        $translationRepository = $entityManager->getRepository(CategoryTranslationInterface::class);

        $this->categoryTranslationRepository = $translationRepository;
    }

    public function total(): ?int
    {
        // Todo: Add correct count for multiple locales.
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $categories = $this->loadCategories($reindexConfig->getIdentifiers());

        /** @var Category $category */
        foreach ($categories as $category) {
            $data = [
                'id' => CategoryInterface::RESOURCE_KEY . '__' . ((string) $category['id']) . '__' . $category['locale'],
                'resourceKey' => CategoryInterface::RESOURCE_KEY,
                'resourceId' => (string) $category['id'],
                'changedAt' => $category['changed']->format('c'),
                'createdAt' => $category['created']->format('c'),
                'title' => $category['translation'],
                'locale' => $category['locale'],
                'securityContext' => CategoryAdmin::SECURITY_CONTEXT,
            ];

            foreach ($this->enhancers as $enhancer) {
                $data = $enhancer->enhanceDocument($category, $data);
            }

            yield $data;
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Category>
     */
    private function loadCategories(array $identifiers = []): iterable
    {
        $qb = $this->categoryTranslationRepository->createQueryBuilder('translation')
            ->select('category.id')
            ->addSelect('category.created')
            ->addSelect('category.changed')
            ->addSelect('translation.translation')
            ->addSelect('translation.locale')
            ->leftJoin('translation.category', 'category');

        if (0 < \count($identifiers)) {
            $conditions = [];
            $parameters = [];

            foreach ($identifiers as $index => $identifier) {
                $resourceKey = \explode('__', $identifier)[0];

                if (CategoryInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('__', $identifier)[1] ?? '';
                $locale = \explode('__', $identifier)[2] ?? '';

                $conditions[] = "(category.id = :id{$index} AND translation.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            if (!$conditions) {
                return [];
            }

            $qb->where(\implode(' OR ', $conditions));
            foreach ($parameters as $parameterKey => $parameterValue) {
                $qb->setParameter($parameterKey, $parameterValue);
            }
        }

        foreach ($this->enhancers as $enhancer) {
            $enhancer->enhanceQuery($qb);
        }

        /** @var iterable<Category> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
