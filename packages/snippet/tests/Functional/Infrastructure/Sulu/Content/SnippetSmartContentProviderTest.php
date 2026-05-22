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

use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

/**
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
 */
class SnippetSmartContentProviderTest extends SuluTestCase
{
    use CreateSnippetTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    /**
     * @var array<string, SnippetInterface>
     */
    private static array $snippets = [];

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

        self::$snippets['default'] = self::createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Default Snippet',
                ],
            ],
        ]);

        self::$snippets['alternate'] = self::createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet-alternate',
                    'title' => 'Alternate Snippet',
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
}
