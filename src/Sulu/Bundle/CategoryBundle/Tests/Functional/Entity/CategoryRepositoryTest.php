<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CategoryRepositoryTest extends SuluTestCase
{
    private EntityManagerInterface $em;

    private CategoryRepositoryInterface $categoryRepository;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->categoryRepository = $this->getContainer()->get('sulu.repository.category');
    }

    public function testFindAncestorCategories(): void
    {
        $shop = $this->createCategory('shop');
        $shirts = $this->createCategory('shirts', $shop);
        $polo = $this->createCategory('polo', $shirts);
        $this->createCategory('shoes', $shop);
        $this->createCategory('sweater', $shirts);
        $this->createCategory('other');

        $this->em->flush();
        $this->em->clear();

        $ancestors = $this->categoryRepository->findAncestorCategories($this->find($polo));

        $this->assertSame(['shop', 'shirts'], \array_map(fn (CategoryInterface $category) => $category->getKey(), $ancestors));
    }

    public function testFindAncestorCategoriesOfARootCategory(): void
    {
        $shop = $this->createCategory('shop');
        $this->createCategory('shirts', $shop);

        $this->em->flush();
        $this->em->clear();

        $this->assertSame([], $this->categoryRepository->findAncestorCategories($this->find($shop)));
    }

    /**
     * The ancestors come with their translations, so reading a name does not query again.
     */
    public function testFindAncestorCategoriesNeedsOneQueryForAnyDepth(): void
    {
        $category = null;
        $leaves = [];
        for ($depth = 1; $depth <= 5; ++$depth) {
            $category = $this->createCategory('depth-' . $depth, $category);
            $leaves[$depth] = $category;
        }

        $this->em->flush();
        $this->em->clear();

        $queryCounts = [];
        foreach ([1, 3, 5] as $depth) {
            $leaf = $this->find($leaves[$depth]);
            $before = $this->countSelectQueries();

            foreach ($this->categoryRepository->findAncestorCategories($leaf) as $ancestor) {
                $ancestor->findTranslationByLocale('en');
            }

            $queryCounts[$depth] = $this->countSelectQueries() - $before;
        }

        $this->assertSame([1 => 1, 3 => 1, 5 => 1], $queryCounts);
    }

    private function createCategory(string $key, ?CategoryInterface $parent = null): CategoryInterface
    {
        /** @var CategoryInterface $category */
        $category = $this->categoryRepository->createNew();
        $category->setKey($key);
        $category->setDefaultLocale('en');

        if ($parent) {
            $category->setParent($parent);
            $parent->addChild($category);
        }

        /** @var CategoryTranslationInterface $translation */
        $translation = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $translation->setLocale('en');
        $translation->setTranslation(\ucfirst($key));
        $translation->setCategory($category);
        $category->addTranslation($translation);

        $this->em->persist($category);
        $this->em->persist($translation);

        return $category;
    }

    private function find(CategoryInterface $category): CategoryInterface
    {
        $found = $this->categoryRepository->findCategoryById($category->getId());
        $this->assertNotNull($found);

        return $found;
    }

    private function countSelectQueries(): int
    {
        $container = self::getContainer();
        if (!$container->has('doctrine.debug_data_holder')) {
            $this->markTestSkipped('Counting queries needs the debug data holder of DoctrineBundle 2.7 or later.');
        }

        $count = 0;
        foreach ($container->get('doctrine.debug_data_holder')->getData()['default'] ?? [] as $query) {
            if (\is_string($query['sql'] ?? null) && \str_starts_with(\ltrim($query['sql']), 'SELECT')) {
                ++$count;
            }
        }

        return $count;
    }
}
