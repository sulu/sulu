<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolver;
use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Resolver\TemplateResolver;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Content\Application\ResourceLoader\ResourceLoaderProvider;
use Sulu\Content\Application\SmartResolver\Resolver\SmartResolverInterface;
use Sulu\Content\Application\SmartResolver\SmartResolverProvider;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageSelectionPropertyResolver;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ContentResolverTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    private PropertyResolverProvider $propertyResolverProvider;

    private ResourceLoaderProvider $resourceLoaderProvider;

    private MetadataResolver $metadataResolver;

    private ContentResolver $contentResolver;

    /**
     * @var ObjectProphecy<MetadataProviderInterface>
     */
    private $metadataProvider;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private $contentAggregator;

    /**
     * @var ResolverInterface[]
     */
    private array $resolvers = [];

    /**
     * @var ObjectProphecy<ResourceLoaderInterface>[]
     */
    private array $resourceLoaders = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyResolverProvider = new PropertyResolverProvider(
            new \ArrayIterator([
                'default' => new DefaultPropertyResolver(),
                'page_selection' => new PageSelectionPropertyResolver(),
            ])
        );
        $this->metadataResolver = new MetadataResolver($this->propertyResolverProvider);
        $this->metadataProvider = $this->prophesize(MetadataProviderInterface::class);

        // ResourceLoaders
        $pageResourceLoader = $this->prophesize(ResourceLoaderInterface::class);
        $this->resourceLoaders['page'] = $pageResourceLoader;

        // Resolvers
        $templateResolver = new TemplateResolver(
            $this->metadataProvider->reveal(),
            $this->metadataResolver
        );
        $this->resolvers = ['template' => $templateResolver];

        $this->resourceLoaderProvider = new ResourceLoaderProvider(\array_map(fn (ObjectProphecy $resourceLoader) => $resourceLoader->reveal(), $this->resourceLoaders));
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        /** @var ServiceLocator<SmartResolverInterface> $serviceLocator */
        $serviceLocator = new ServiceLocator([]);
        $this->contentResolver = new ContentResolver(
            $this->resolvers,
            $this->resourceLoaderProvider,
            $this->contentAggregator->reveal(),
            new SmartResolverProvider($serviceLocator),
        );
    }

    public function testResolveSubPage(): void
    {
        $contentRichEntity = new Page();
        $this->setPrivateProperty($contentRichEntity, 'uuid', '111-111-111');
        /** @var PageDimensionContentInterface $dimensionContent */
        $dimensionContent = $contentRichEntity->createDimensionContent();
        $dimensionContent->setStage('live');
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default_with_page_selection');
        $dimensionContent->setTemplateData([
            'title' => 'Sulu',
            'url' => '/page',
            'pages' => [
                '111-111-222', //$page2 uuid
            ],
        ]);

        $page2 = new Page();
        $this->setPrivateProperty($page2, 'uuid', '111-111-222');
        /** @var PageDimensionContentInterface $dimensionContent2 */
        $dimensionContent2 = $page2->createDimensionContent();
        $dimensionContent2->setStage('live');
        $dimensionContent2->setLocale('en');
        $dimensionContent2->setTemplateKey('default');
        $dimensionContent2->setTemplateData([
            'title' => 'Page 2',
            'url' => '/page-2',
            'article' => '<p>Page 2 article</p>',
        ]);

        $this->resourceLoaders['page']->load(['111-111-222' => '111-111-222'], 'en')
            ->shouldBeCalledOnce()
            ->willReturn(['111-111-222' => $page2]);

        $this->contentAggregator->aggregate($page2, ['stage' => 'live', 'locale' => 'en'])
            ->shouldBeCalledOnce()
            ->willReturn($dimensionContent2);

        $this->metadataProvider->getMetadata('page', 'en', [])
            ->shouldBeCalled()
            ->willReturn($this->getTemplateFormMetadata('page'));

        /**
         * @var array{
         *   resource: Page,
         *   content: array{
         *     title: string,
         *     url: string,
         *     article: string|null,
         *     pages: array{
         *       array{
         *         resource: Page,
         *         content: array{
         *           title: string,
         *           url: string,
         *           article: string|null
         *         },
         *         view: array{
         *           title: mixed[],
         *           url: mixed[],
         *           article: mixed[]
         *         }
         *       }
         *     }
         *   },
         *   view: array{
         *     title: mixed[],
         *     url: mixed[],
         *     article: mixed[],
         *     pages: array{0: array{ids: array<string>}}
         *   }
         * } $result
         */
        $result = $this->contentResolver->resolve($dimensionContent);

        self::assertSame($contentRichEntity, $result['resource']);

        $content = $result['content'];
        self::assertSame('Sulu', $content['title']);
        self::assertSame('/page', $content['url']);
        self::assertNull($content['article']);

        $view = $result['view'];
        self::assertSame([], $view['title']);
        self::assertSame([], $view['url']);
        self::assertSame([], $view['article']);
        self::assertSame(['111-111-222'], $view['pages'][0]['ids']);

        // SubEntity
        self::assertSame($content['pages'][0]['resource'], $page2);
        $innerContent = $content['pages'][0]['content'];
        self::assertSame('Page 2', $innerContent['title']);
        self::assertSame('/page-2', $innerContent['url']);
        self::assertSame('<p>Page 2 article</p>', $innerContent['article']);

        $innerView = $content['pages'][0]['view'];
        self::assertSame([], $innerView['title']);
        self::assertSame([], $innerView['url']);
        self::assertSame([], $innerView['article']);
    }

    public function testResolveCircularLoopPages(): void
    {
        $page1 = new Page();
        $this->setPrivateProperty($page1, 'uuid', '111-111-111');
        /** @var PageDimensionContentInterface $dimensionContent1 */
        $dimensionContent1 = $page1->createDimensionContent();
        $dimensionContent1->setStage('live');
        $dimensionContent1->setLocale('en');
        $dimensionContent1->setTemplateKey('default_with_page_selection');
        $dimensionContent1->setTemplateData([
            'title' => 'Sulu',
            'url' => '/page',
            'pages' => [
                '111-111-222', //$page2 uuid
            ],
        ]);

        $page2 = new Page();
        $this->setPrivateProperty($page2, 'uuid', '111-111-222');
        /** @var PageDimensionContentInterface $dimensionContent2 */
        $dimensionContent2 = $page2->createDimensionContent();
        $dimensionContent2->setStage('live');
        $dimensionContent2->setLocale('en');
        $dimensionContent2->setTemplateKey('default_with_page_selection');
        $dimensionContent2->setTemplateData([
            'title' => 'Page 2',
            'url' => '/page-2',
            'article' => '<p>Page 2 article</p>',
            'pages' => [
                '111-111-111', //$page1 uuid
            ],
        ]);

        $this->resourceLoaders['page']->load(['111-111-222' => '111-111-222'], 'en')
            ->shouldBeCalled()
            ->willReturn(['111-111-222' => $page2]);

        $this->resourceLoaders['page']->load(['111-111-111' => '111-111-111'], 'en')
            ->shouldBeCalled()
            ->willReturn(['111-111-111' => $page1]);

        $this->contentAggregator->aggregate($page1, ['stage' => 'live', 'locale' => 'en'])
            ->shouldBeCalled()
            ->willReturn($dimensionContent1);

        $this->contentAggregator->aggregate($page2, ['stage' => 'live', 'locale' => 'en'])
            ->shouldBeCalled()
            ->willReturn($dimensionContent2);

        $this->metadataProvider->getMetadata('page', 'en', [])
            ->shouldBeCalled()
            ->willReturn($this->getTemplateFormMetadata('page'));

        $result = $this->contentResolver->resolve($dimensionContent1);

        // Define expected data for each level in the circular reference
        $expectedData = [
            // Level 0 - page1
            [
                'resource' => $page1,
                'title' => 'Sulu',
                'url' => '/page',
                'article' => null,
                'nextId' => '111-111-222',
            ],
            // Level 1 - page2
            [
                'resource' => $page2,
                'title' => 'Page 2',
                'url' => '/page-2',
                'article' => '<p>Page 2 article</p>',
                'nextId' => '111-111-111',
            ],
            // Levels 2, 4 - page1 (repeats)
            [
                'resource' => $page1,
                'title' => 'Sulu',
                'url' => '/page',
                'article' => null,
                'nextId' => '111-111-222',
            ],
            // Levels 3, 5 - page2 (repeats)
            [
                'resource' => $page2,
                'title' => 'Page 2',
                'url' => '/page-2',
                'article' => '<p>Page 2 article</p>',
                'nextId' => '111-111-111',
            ],
            // Back to page1 for level 4
            [
                'resource' => $page1,
                'title' => 'Sulu',
                'url' => '/page',
                'article' => null,
                'nextId' => '111-111-222',
            ],
            // Back to page2 for level 5
            [
                'resource' => $page2,
                'title' => 'Page 2',
                'url' => '/page-2',
                'article' => '<p>Page 2 article</p>',
                'nextId' => '111-111-111',
            ],
        ];

        $contentPointer = $result;
        // Loop through expected levels and verify the content
        for ($level = 0; $level < 6; ++$level) {
            $expectedLevel = $expectedData[$level % \count($expectedData)];

            // Test based on the level index
            self::assertSame($expectedLevel['resource'], $contentPointer['resource'], "Level $level resource incorrect"); //@phpstan-ignore-line
            self::assertSame($expectedLevel['title'], $contentPointer['content']['title'], "Level $level title incorrect"); //@phpstan-ignore-line
            self::assertSame($expectedLevel['url'], $contentPointer['content']['url'], "Level $level url incorrect"); //@phpstan-ignore-line
            self::assertSame($expectedLevel['article'], $contentPointer['content']['article'], "Level $level article incorrect"); //@phpstan-ignore-line

            // Check view data for the current level
            self::assertSame([], $contentPointer['view']['title'], "Level $level view title incorrect"); //@phpstan-ignore-line
            self::assertSame([], $contentPointer['view']['url'], "Level $level view url incorrect"); //@phpstan-ignore-line
            self::assertSame([], $contentPointer['view']['article'], "Level $level view article incorrect"); //@phpstan-ignore-line
            self::assertSame([$expectedLevel['nextId']], $contentPointer['view']['pages'][0]['ids'], "Level $level view page ids incorrect"); //@phpstan-ignore-line

            // Level 5 is where we expect it to break the circular reference
            if (5 === $level) {
                self::assertNull($contentPointer['content']['pages'][0], 'Circular reference should break after level 5'); //@phpstan-ignore-line
                break;
            }

            // Move the pointer to the next page in the reference chain
            $contentPointer = $contentPointer['content']['pages'][0]; //@phpstan-ignore-line
        }
    }

    private function getTemplateFormMetadata(string $templateType): TypedFormMetadata
    {
        return match ($templateType) {
            'page' => $this->getTypedFormMetadataForPage(),
            default => throw new \RuntimeException('TemplateType with type "' . $templateType . '" not configured.'),
        };
    }

    private function getTypedFormMetadataForPage(): TypedFormMetadata
    {
        $typedFormMetadata = new TypedFormMetadata();
        foreach (['default', 'default_with_page_selection'] as $templateKey) {
            $formMetadata = new FormMetadata();

            $fieldMetadataCallback = match ($templateKey) {
                'default' => function() {
                    $titleFieldMetadata = new FieldMetadata('title');
                    $titleFieldMetadata->setType('text_line');
                    $urlFieldMetadata = new FieldMetadata('url');
                    $urlFieldMetadata->setType('resource_locator');
                    $articleFieldMetadata = new FieldMetadata('article');
                    $articleFieldMetadata->setType('text_editor');

                    return [$titleFieldMetadata, $urlFieldMetadata, $articleFieldMetadata];
                },
                'default_with_page_selection' => function() {
                    $titleFieldMetadata = new FieldMetadata('title');
                    $titleFieldMetadata->setType('text_line');
                    $urlFieldMetadata = new FieldMetadata('url');
                    $urlFieldMetadata->setType('resource_locator');
                    $articleFieldMetadata = new FieldMetadata('article');
                    $articleFieldMetadata->setType('text_editor');
                    $pagesFieldMetadata = new FieldMetadata('pages');
                    $pagesFieldMetadata->setType('page_selection');

                    return [$titleFieldMetadata, $urlFieldMetadata, $articleFieldMetadata, $pagesFieldMetadata];
                },
            };

            $formMetadata->setItems($fieldMetadataCallback());
            $typedFormMetadata->addForm($templateKey, $formMetadata);
        }

        return $typedFormMetadata;
    }
}
