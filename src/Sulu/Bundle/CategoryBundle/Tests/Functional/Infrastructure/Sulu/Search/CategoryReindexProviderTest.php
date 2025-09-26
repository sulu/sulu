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

namespace Sulu\Bundle\CategoryBundle\Tests\Functional\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Search\CategoryReindexProvider;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CategoryReindexProviderTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;

    private EntityManagerInterface $entityManager;
    private CategoryReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new CategoryReindexProvider($this->entityManager);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', CategoryReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->createCategory();

        $this->entityManager->flush();

        $this->assertSame(1, $this->provider->total());
    }

    public function testProvideAll(): void
    {
        $category1 = $this->createCategory(null, 'en');
        $category1Translation = $this->createCategoryTranslation($category1, 'en', 'Category EN 1');
        $category2 = $this->createCategory(null, 'en');
        $category2Translation1 = $this->createCategoryTranslation($category2, 'en', 'Category EN 2');
        $category2Translation2 = $this->createCategoryTranslation($category2, 'de', 'Category DE 2');

        $this->entityManager->flush();

        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE ca_categories SET changed = :changed WHERE id = :id';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'id' => $category1->getId(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'id' => $category2->getId(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $this->assertSame(
            [
                [
                    'id' => CategoryInterface::RESOURCE_KEY . '::' . $category1->getId() . '::' . $category1Translation->getLocale(),
                    'resourceKey' => CategoryInterface::RESOURCE_KEY,
                    'resourceId' => (string) $category1->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $category1Translation->getTranslation(),
                    'locale' => $category1Translation->getLocale(),
                ],
                [
                    'id' => CategoryInterface::RESOURCE_KEY . '::' . $category2->getId() . '::' . $category2Translation1->getLocale(),
                    'resourceKey' => CategoryInterface::RESOURCE_KEY,
                    'resourceId' => (string) $category2->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $category2Translation1->getTranslation(),
                    'locale' => $category2Translation1->getLocale(),
                ],
                [
                    'id' => CategoryInterface::RESOURCE_KEY . '::' . $category2->getId() . '::' . $category2Translation2->getLocale(),
                    'resourceKey' => CategoryInterface::RESOURCE_KEY,
                    'resourceId' => (string) $category2->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $category2Translation2->getTranslation(),
                    'locale' => $category2Translation2->getLocale(),
                ],
            ],
            [...$results],
        );
    }

    public function testProvideWithSpecificIdentifiers(): void
    {
        $category1 = $this->createCategory(null, 'en');
        $category1Translation = $this->createCategoryTranslation($category1, 'en', 'Category EN 1');
        $category2 = $this->createCategory(null, 'en');
        $category2Translation1 = $this->createCategoryTranslation($category2, 'en', 'Category EN 2');
        $category2Translation2 = $this->createCategoryTranslation($category2, 'de', 'Category DE 2');
        $category3 = $this->createCategory(null, 'en');
        $category3Translation1 = $this->createCategoryTranslation($category3, 'en', 'Category EN 3');

        $this->entityManager->flush();

        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE ca_categories SET changed = :changed WHERE id = :id';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'id' => $category1->getId(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'id' => $category2->getId(),
        ]);

        $identifiers = [
            CategoryInterface::RESOURCE_KEY . '::' . $category1->getId() . '::' . $category1Translation->getLocale(),
            CategoryInterface::RESOURCE_KEY . '::' . $category2->getId() . '::' . $category2Translation2->getLocale(),
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Category EN 1', $resultTitles);
        $this->assertContains('Category DE 2', $resultTitles);
        $this->assertNotContains('Category EN 2', $resultTitles);
        $this->assertNotContains('Category EN 3', $resultTitles);
    }

    private function createCategory(
        ?string $key = null,
        string $defaultLocale = 'en',
        ?CategoryInterface $parentCategory = null,
    ): Category {
        $category = new Category();
        $category->setKey($key);
        $category->setDefaultLocale($defaultLocale);
        $category->setCreated(new \DateTimeImmutable('2000-01-01 12:00:00'));

        if ($parentCategory) {
            $category->setParent($parentCategory);
            $parentCategory->addChild($category);
        }

        $this->entityManager->persist($category);

        return $category;
    }

    private function createCategoryTranslation(CategoryInterface $category, string $locale, string $title): CategoryTranslation
    {
        $categoryTrans = new CategoryTranslation();
        $categoryTrans->setLocale($locale);
        $categoryTrans->setTranslation($title);
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        $this->entityManager->persist($categoryTrans);

        return $categoryTrans;
    }
}
