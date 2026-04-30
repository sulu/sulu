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

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Infrastructure\Sulu\Content\SmartContent;

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;

/**
 * Tests for the AccountSmartContentProvider.
 */
class AccountSmartContentProviderTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateTagTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    /**
     * @var array<string, AccountInterface>
     */
    private static array $accounts = [];

    /**
     * @var array<string, CategoryInterface>
     */
    private static array $categories = [];

    /**
     * @var array<string, int>
     */
    private static array $tags = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->smartContentProvider = $this->getContainer()->get('sulu_contact.account_smart_content_provider');
    }

    public static function setUpBeforeClass(): void
    {
        self::purgeDatabase();
        self::bootKernel();

        $entityManager = self::getEntityManager();

        // Create categories
        self::$categories['tech'] = self::createCategory(['en' => ['title' => 'Technology']]);
        self::$categories['finance'] = self::createCategory(['en' => ['title' => 'Finance']]);
        self::$categories['health'] = self::createCategory(['en' => ['title' => 'Health']]);

        // Create tags
        $tagStartup = self::createTag(['name' => 'startup']);
        $tagEnterprise = self::createTag(['name' => 'enterprise']);
        $tagCloud = self::createTag(['name' => 'cloud']);
        $tagInnovation = self::createTag(['name' => 'innovation']);
        $tagGlobal = self::createTag(['name' => 'global']);

        $entityManager->flush();

        // Store tag IDs for filtering tests
        self::$tags = [
            'startup' => $tagStartup->getId(),
            'enterprise' => $tagEnterprise->getId(),
            'cloud' => $tagCloud->getId(),
            'innovation' => $tagInnovation->getId(),
            'global' => $tagGlobal->getId(),
        ];

        // Create accounts with various tags and categories
        self::$accounts['tech1'] = self::createAccount(
            'TechCorp',
            [$tagStartup, $tagCloud],
            [self::$categories['tech']],
        );

        self::$accounts['tech2'] = self::createAccount(
            'CloudServices Inc',
            [$tagCloud, $tagEnterprise],
            [self::$categories['tech']],
        );

        self::$accounts['finance1'] = self::createAccount(
            'GlobalBank',
            [$tagGlobal, $tagEnterprise],
            [self::$categories['finance']],
        );

        self::$accounts['health1'] = self::createAccount(
            'HealthTech',
            [$tagInnovation],
            [self::$categories['health']],
        );

        self::$accounts['multi'] = self::createAccount(
            'MultiIndustry Corp',
            [$tagStartup, $tagInnovation, $tagGlobal],
            [self::$categories['tech'], self::$categories['finance']],
        );

        self::$accounts['notag'] = self::createAccount(
            'Simple Company',
            [],
            [],
        );

        $entityManager->flush();
    }

    /**
     * @param Tag[] $tags
     * @param CategoryInterface[] $categories
     */
    private static function createAccount(
        string $name,
        array $tags = [],
        array $categories = [],
    ): AccountInterface {
        $entityManager = self::getEntityManager();

        $account = new Account();
        $account->setName($name);

        foreach ($tags as $tag) {
            $account->addTag($tag);
        }

        foreach ($categories as $category) {
            $account->addCategory($category);
        }

        $entityManager->persist($account);

        return $account;
    }

    public function testFindFlatByWithoutFilters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], []);

        $this->assertCount(6, $result);
        $count = $this->smartContentProvider->countBy([...$this->getDefaultFilters(), ...['locale' => 'en']]);
        $this->assertSame(6, $count);
    }

    /**
     * @return array<string, array{
     *     tagKeys: string[],
     *     operator: 'OR'|'AND',
     *     expectedKeys: string[],
     *     expectedCount: int
     * }>
     */
    public static function tagFilterProvider(): array
    {
        return [
            'single_tag_OR' => [
                'tagKeys' => ['startup'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'multi'],
                'expectedCount' => 2,
            ],
            'multiple_tags_OR' => [
                'tagKeys' => ['startup', 'enterprise'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'tech2', 'finance1', 'multi'],
                'expectedCount' => 4,
            ],
            'single_tag_AND' => [
                'tagKeys' => ['cloud'],
                'operator' => 'AND',
                'expectedKeys' => ['tech1', 'tech2'],
                'expectedCount' => 2,
            ],
            'multiple_tags_AND' => [
                'tagKeys' => ['startup', 'innovation'],
                'operator' => 'AND',
                'expectedKeys' => ['multi'],
                'expectedCount' => 1,
            ],
            'no_match_AND' => [
                'tagKeys' => ['startup', 'enterprise'],
                'operator' => 'AND',
                'expectedKeys' => [],
                'expectedCount' => 0,
            ],
        ];
    }

    /**
     * @param string[] $tagKeys
     * @param string[] $expectedKeys
     */
    #[DataProvider('tagFilterProvider')]
    public function testTagFiltering(array $tagKeys, string $operator, array $expectedKeys, int $expectedCount): void
    {
        $tags = \array_map(fn ($key) => self::$tags[$key], $tagKeys);

        /** @var 'AND'|'OR' $tagOperator */
        $tagOperator = $operator;

        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'tags' => $tags,
                'tagOperator' => $tagOperator,
            ],
        ], []);

        $this->assertCount($expectedCount, $result);
        $this->assertSame(
            $expectedCount,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'tags' => $tags,
                    'tagOperator' => $tagOperator,
                ],
            ]),
        );

        $resultIds = \array_map(fn ($account) => $account['id'], $result);

        foreach ($expectedKeys as $key) {
            $this->assertContains(
                self::$accounts[$key]->getId(),
                $resultIds,
                "Account '" . $key . "' should be in the result",
            );
        }
    }

    /**
     * @return array<string, array{
     *     categoryKeys: string[],
     *     operator: 'OR'|'AND',
     *     expectedKeys: string[],
     *     expectedCount: int
     * }>
     */
    public static function categoryFilterProvider(): array
    {
        return [
            'single_category_OR' => [
                'categoryKeys' => ['tech'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'tech2', 'multi'],
                'expectedCount' => 3,
            ],
            'multiple_categories_OR' => [
                'categoryKeys' => ['tech', 'health'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'tech2', 'health1', 'multi'],
                'expectedCount' => 4,
            ],
            'multiple_categories_AND' => [
                'categoryKeys' => ['tech', 'finance'],
                'operator' => 'AND',
                'expectedKeys' => ['multi'],
                'expectedCount' => 1,
            ],
        ];
    }

    /**
     * @param string[] $categoryKeys
     * @param string[] $expectedKeys
     */
    #[DataProvider('categoryFilterProvider')]
    public function testCategoryFiltering(array $categoryKeys, string $operator, array $expectedKeys, int $expectedCount): void
    {
        $categoryIds = \array_map(fn ($key) => self::$categories[$key]->getId(), $categoryKeys);

        /** @var 'AND'|'OR' $categoryOperator */
        $categoryOperator = $operator;

        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => $categoryIds,
                'categoryOperator' => $categoryOperator,
            ],
        ], []);

        $this->assertCount($expectedCount, $result);
        $this->assertSame(
            $expectedCount,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'categories' => $categoryIds,
                    'categoryOperator' => $categoryOperator,
                ],
            ]),
        );

        $resultIds = \array_map(fn ($account) => $account['id'], $result);

        foreach ($expectedKeys as $key) {
            $this->assertContains(
                self::$accounts[$key]->getId(),
                $resultIds,
                "Account '" . $key . "' should be in the result",
            );
        }
    }

    public function testFindFlatByCategoryAndTagFilters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => [self::$categories['tech']->getId()],
                'categoryOperator' => 'OR',
                'tags' => [self::$tags['startup']],
                'tagOperator' => 'OR',
            ],
        ], []);

        // tech1 has both tech category and startup tag
        // multi has both tech category and startup tag
        $this->assertCount(2, $result);

        $resultIds = \array_map(fn ($account) => $account['id'], $result);

        $this->assertContains(self::$accounts['tech1']->getId(), $resultIds);
        $this->assertContains(self::$accounts['multi']->getId(), $resultIds);
    }

    public function testFindFlatByPagination(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'limit' => 2,
                'offset' => 0,
            ],
        ], [
            'account.name' => 'asc',
        ]);

        $this->assertCount(2, $result);

        // Total count should still return all accounts
        $this->assertSame(
            6,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en'],
            ]),
        );
    }

    /**
     * @return array{
     *     categories: int[],
     *     categoryOperator: 'AND'|'OR',
     *     websiteCategories: string[],
     *     websiteCategoryOperator: 'AND'|'OR',
     *     tags: int[],
     *     tagOperator: 'AND'|'OR',
     *     websiteTags: string[],
     *     websiteTagOperator: 'AND'|'OR',
     *     types: string[],
     *     typesOperator: 'OR',
     *     locale: string,
     *     dataSource: string|null,
     *     limit: int|null,
     *     offset: int,
     *     includeSubFolders: bool,
     *     excludeDuplicates: bool,
     * }
     */
    private function getDefaultFilters(): array
    {
        return [
            'categories' => [],
            'categoryOperator' => 'OR',
            'websiteCategories' => [],
            'websiteCategoryOperator' => 'OR',
            'tags' => [],
            'tagOperator' => 'OR',
            'websiteTags' => [],
            'websiteTagOperator' => 'OR',
            'types' => [],
            'typesOperator' => 'OR',
            'locale' => 'en',
            'dataSource' => null,
            'limit' => null,
            'offset' => 0,
            'includeSubFolders' => false,
            'excludeDuplicates' => false,
        ];
    }
}
