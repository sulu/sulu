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

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

/**
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
 */
class SnippetSmartContentProviderTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateSnippetTrait;
    use CreateTagTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    /**
     * @var array<string, SnippetInterface>
     */
    private static array $snippets = [];

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
        $this->smartContentProvider = $this->getContainer()->get('sulu_snippet.snippet_smart_content_provider');
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();

        $entityManager = self::getEntityManager();

        self::$categories['tech'] = self::createCategory(['en' => ['title' => 'Technology']]);
        self::$categories['sports'] = self::createCategory(['en' => ['title' => 'Sports']]);
        $entityManager->flush();

        $tagEntities = [];
        foreach (['mobile', 'football'] as $tagName) {
            $tagEntities[$tagName] = self::createTag(['name' => $tagName]);
        }
        $entityManager->flush();

        foreach ($tagEntities as $tagName => $tagEntity) {
            self::$tags[$tagName] = $tagEntity->getId();
        }

        self::$snippets['default'] = self::createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Default Snippet',
                    'excerptCategories' => [self::$categories['tech']->getId()],
                    'excerptTags' => [self::$tags['mobile']],
                ],
            ],
        ]);

        self::$snippets['alternate'] = self::createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet-alternate',
                    'title' => 'Alternate Snippet',
                    'excerptCategories' => [self::$categories['sports']->getId()],
                    'excerptTags' => [self::$tags['football']],
                ],
            ],
        ]);
    }

    public function testFindFlatByXmlTemplateKeysIntersectsWithUiTypes(): void
    {
        $result = $this->smartContentProvider->findFlatBy(
            [
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'types' => ['snippet']],
            ],
            [],
            ['templateKeys' => 'snippet,snippet-alternate'],
        );

        $this->assertCount(1, $result);
        $this->assertSame(
            1,
            $this->smartContentProvider->countBy(
                [
                    ...$this->getDefaultFilters(),
                    ...['locale' => 'en', 'types' => ['snippet']],
                ],
                ['templateKeys' => 'snippet,snippet-alternate'],
            ),
        );

        $resultIds = \array_map(fn ($snippet) => $snippet['id'], $result);
        $this->assertContains(self::$snippets['default']->getUuid(), $resultIds);
    }

    public function testFindFlatByUiTypeOutsideXmlTemplateKeysReturnsZero(): void
    {
        $result = $this->smartContentProvider->findFlatBy(
            [
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'types' => ['snippet-alternate']],
            ],
            [],
            ['templateKeys' => 'snippet'],
        );

        $this->assertCount(0, $result);
        $this->assertSame(
            0,
            $this->smartContentProvider->countBy(
                [
                    ...$this->getDefaultFilters(),
                    ...['locale' => 'en', 'types' => ['snippet-alternate']],
                ],
                ['templateKeys' => 'snippet'],
            ),
        );
    }

    public function testFindFlatByCategoryFiltersByExcerptCategory(): void
    {
        $filters = [
            ...$this->getDefaultFilters(),
            ...['categories' => [self::$categories['tech']->getId()]],
        ];

        $result = $this->smartContentProvider->findFlatBy($filters, []);

        $this->assertCount(1, $result);
        $this->assertSame(self::$snippets['default']->getUuid(), $result[0]['id']);
        $this->assertSame(1, $this->smartContentProvider->countBy($filters));
    }

    public function testFindFlatByTagFiltersByExcerptTag(): void
    {
        $filters = [
            ...$this->getDefaultFilters(),
            ...['tags' => [self::$tags['football']]],
        ];

        $result = $this->smartContentProvider->findFlatBy($filters, []);

        $this->assertCount(1, $result);
        $this->assertSame(self::$snippets['alternate']->getUuid(), $result[0]['id']);
        $this->assertSame(1, $this->smartContentProvider->countBy($filters));
    }

    /**
     * @return SmartContentBaseFilters
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

    protected static function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
    }
}
