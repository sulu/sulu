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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;

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
final class CategoryReindexProvider
{
    /**
     * @var EntityRepository<CategoryInterface>
     */
    protected EntityRepository $categoryRepository;

    /**
     * @var EntityRepository<CategoryTranslationInterface>
     */
    protected EntityRepository $categoryTranslationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $repository = $entityManager->getRepository(CategoryInterface::class);
        $translationRepository = $entityManager->getRepository(CategoryTranslationInterface::class);

        $this->categoryRepository = $repository;
        $this->categoryTranslationRepository = $translationRepository;
    }

    public function total(): int
    {
        return $this->categoryRepository->count([]);
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $categories = $this->loadCategories($reindexConfig->getIdentifiers());

        /** @var Category $category */
        foreach ($categories as $category) {
            yield [
                'id' => CategoryInterface::RESOURCE_KEY . '::' . ((string) $category['id']) . '::' . $category['locale'],
                'resourceKey' => CategoryInterface::RESOURCE_KEY,
                'resourceId' => (string) $category['id'],
                'changedAt' => $category['changed']->format('c'),
                'createdAt' => $category['created']->format('c'),
                'title' => $category['translation'],
                'locale' => $category['locale'],
            ];
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
                $id = \explode('::', $identifier)[1];
                $locale = \explode('::', $identifier)[2];

                $conditions[] = "(category.id = :id{$index} AND translation.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            $qb->where(\implode(' OR ', $conditions));
            $qb->setParameters($parameters);
        }

        /** @var iterable<Category> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
