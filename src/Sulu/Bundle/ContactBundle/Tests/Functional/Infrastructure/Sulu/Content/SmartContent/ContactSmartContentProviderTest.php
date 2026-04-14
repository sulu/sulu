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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;

/**
 * Tests for the ContactSmartContentProvider.
 */
class ContactSmartContentProviderTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateTagTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    /**
     * @var array<string, ContactInterface>
     */
    private static array $contacts = [];

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
        $this->smartContentProvider = $this->getContainer()->get('sulu_contact.contact_smart_content_provider');
    }

    public static function setUpBeforeClass(): void
    {
        self::purgeDatabase();
        self::bootKernel();

        $entityManager = self::getEntityManager();

        // Create categories
        self::$categories['tech'] = self::createCategory(['en' => ['title' => 'Technology']]);
        self::$categories['sports'] = self::createCategory(['en' => ['title' => 'Sports']]);
        self::$categories['health'] = self::createCategory(['en' => ['title' => 'Health']]);

        // Create tags
        $tagMobile = self::createTag(['name' => 'mobile']);
        $tagWeb = self::createTag(['name' => 'web']);
        $tagCloud = self::createTag(['name' => 'cloud']);
        $tagFitness = self::createTag(['name' => 'fitness']);
        $tagFootball = self::createTag(['name' => 'football']);

        $entityManager->flush();

        // Store tag IDs for filtering tests
        self::$tags = [
            'mobile' => $tagMobile->getId(),
            'web' => $tagWeb->getId(),
            'cloud' => $tagCloud->getId(),
            'fitness' => $tagFitness->getId(),
            'football' => $tagFootball->getId(),
        ];

        // Create contacts with various tags and categories
        self::$contacts['tech1'] = self::createContact(
            'John',
            'Developer',
            [$tagMobile, $tagWeb],
            [self::$categories['tech']],
        );

        self::$contacts['tech2'] = self::createContact(
            'Jane',
            'CloudExpert',
            [$tagCloud],
            [self::$categories['tech']],
        );

        self::$contacts['sports1'] = self::createContact(
            'Mike',
            'Footballer',
            [$tagFootball],
            [self::$categories['sports']],
        );

        self::$contacts['health1'] = self::createContact(
            'Sarah',
            'FitnessCoach',
            [$tagFitness],
            [self::$categories['health']],
        );

        self::$contacts['multi'] = self::createContact(
            'Alex',
            'MultiSkill',
            [$tagMobile, $tagFitness],
            [self::$categories['tech'], self::$categories['health']],
        );

        self::$contacts['notag'] = self::createContact(
            'Bob',
            'NoTag',
            [],
            [],
        );

        $entityManager->flush();
    }

    /**
     * @param Tag[] $tags
     * @param CategoryInterface[] $categories
     */
    private static function createContact(
        string $firstName,
        string $lastName,
        array $tags = [],
        array $categories = [],
    ): ContactInterface {
        $entityManager = self::getEntityManager();

        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setFormOfAddress(0);

        foreach ($tags as $tag) {
            $contact->addTag($tag);
        }

        foreach ($categories as $category) {
            $contact->addCategory($category);
        }

        $entityManager->persist($contact);

        return $contact;
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
                'tagKeys' => ['mobile'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'multi'],
                'expectedCount' => 2,
            ],
            'multiple_tags_OR' => [
                'tagKeys' => ['mobile', 'cloud'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'tech2', 'multi'],
                'expectedCount' => 3,
            ],
            'single_tag_AND' => [
                'tagKeys' => ['fitness'],
                'operator' => 'AND',
                'expectedKeys' => ['health1', 'multi'],
                'expectedCount' => 2,
            ],
            'multiple_tags_AND' => [
                'tagKeys' => ['mobile', 'fitness'],
                'operator' => 'AND',
                'expectedKeys' => ['multi'],
                'expectedCount' => 1,
            ],
            'no_match_AND' => [
                'tagKeys' => ['mobile', 'football'],
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

        $resultIds = \array_map(fn ($contact) => $contact['id'], $result);

        foreach ($expectedKeys as $key) {
            $this->assertContains(
                self::$contacts[$key]->getId(),
                $resultIds,
                "Contact '" . $key . "' should be in the result",
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
                'categoryKeys' => ['tech', 'sports'],
                'operator' => 'OR',
                'expectedKeys' => ['tech1', 'tech2', 'sports1', 'multi'],
                'expectedCount' => 4,
            ],
            'multiple_categories_AND' => [
                'categoryKeys' => ['tech', 'health'],
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

        $resultIds = \array_map(fn ($contact) => $contact['id'], $result);

        foreach ($expectedKeys as $key) {
            $this->assertContains(
                self::$contacts[$key]->getId(),
                $resultIds,
                "Contact '" . $key . "' should be in the result",
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
                'tags' => [self::$tags['mobile']],
                'tagOperator' => 'OR',
            ],
        ], []);

        // tech1 has both tech category and mobile tag
        // multi has both tech category and mobile tag
        $this->assertCount(2, $result);

        $resultIds = \array_map(fn ($contact) => $contact['id'], $result);

        $this->assertContains(self::$contacts['tech1']->getId(), $resultIds);
        $this->assertContains(self::$contacts['multi']->getId(), $resultIds);
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
            'contact.firstName' => 'asc',
        ]);

        $this->assertCount(2, $result);

        // Total count should still return all contacts
        $this->assertSame(
            6,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en'],
            ]),
        );
    }

    public function testFindFlatBySorting(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en'],
        ], [
            'contact.firstName' => 'asc',
        ]);

        $this->assertCount(6, $result);
        $this->assertSame('Alex MultiSkill', $result[0]['title']);
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
