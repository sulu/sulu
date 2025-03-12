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
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageSelectionPropertyResolver;

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
        $this->contentResolver = new ContentResolver(
            $this->resolvers,
            $this->resourceLoaderProvider,
            $this->contentAggregator->reveal()
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

        /**
         * @var array{
         *     resource: Page,
         *     content: array{
         *         title: string,
         *         url: string,
         *         article: string|null,
         *         pages: array{
         *             array{
         *                 resource: Page,
         *                 content: array{
         *                     title: string,
         *                     url: string,
         *                     article: string|null,
         *                     pages: array{
         *                         array{
         *                             resource: Page,
         *                             content: array{
         *                                 title: string,
         *                                 url: string,
         *                                 article: string|null,
         *                                 pages: array{
         *                                     array{
         *                                         resource: Page,
         *                                         content: array{
         *                                             title: string,
         *                                             url: string,
         *                                             article: string|null,
         *                                             pages: array{0: mixed},
         *                                         },
         *                                         view: array{
         *                                             title: mixed[],
         *                                             url: mixed[],
         *                                             article: mixed[],
         *                                             pages: array{0: array{ids: array<string>}}
         *                                         }
         *                                     }
         *                                 }
         *                             },
         *                             view: array{
         *                                 title: mixed[],
         *                                 url: mixed[],
         *                                 article: mixed[],
         *                                 pages: array{0: array{ids: array<string>}}
         *                             }
         *                         }
         *                     }
         *                 },
         *                 view: array{
         *                     title: mixed[],
         *                     url: mixed[],
         *                     article: mixed[],
         *                     pages: array{0: array{ids: array<string>}}
         *                 }
         *             }
         *         }
         *     },
         *     view: array{
         *         title: mixed[],
         *         url: mixed[],
         *         article: mixed[],
         *         pages: array{0: array{ids: array<string>}}
         *     }
         * } $result
         */
        $result = $this->contentResolver->resolve($dimensionContent1);

        self::assertSame($page1, $result['resource']);

        $content = $result['content'];
        // level 0
        self::assertSame('Sulu', $content['title']);
        self::assertSame('/page', $content['url']);
        self::assertNull($content['article']);

        $view = $result['view'];
        self::assertSame([], $view['title']);
        self::assertSame([], $view['url']);
        self::assertSame([], $view['article']);
        self::assertSame(['111-111-222'], $view['pages'][0]['ids']);

        // level 1
        self::assertSame($content['pages'][0]['resource'], $page2);
        $innerContent = $content['pages'][0]['content'];
        self::assertSame('Page 2', $innerContent['title']);
        self::assertSame('/page-2', $innerContent['url']);
        self::assertSame('<p>Page 2 article</p>', $innerContent['article']);

        $innerView = $content['pages'][0]['view'];
        self::assertSame([], $innerView['title']);
        self::assertSame([], $innerView['url']);
        self::assertSame([], $innerView['article']);
        self::assertSame(['111-111-111'], $innerView['pages'][0]['ids']);

        // level 2
        self::assertSame($innerContent['pages'][0]['resource'], $page1);
        $innerInnerContent = $innerContent['pages'][0]['content'];
        self::assertSame('Sulu', $innerInnerContent['title']);
        self::assertSame('/page', $innerInnerContent['url']);
        self::assertNull($innerInnerContent['article']);

        $innerInnerView = $innerContent['pages'][0]['view'];
        self::assertSame([], $innerInnerView['title']);
        self::assertSame([], $innerInnerView['url']);
        self::assertSame([], $innerInnerView['article']);
        self::assertSame(['111-111-222'], $innerInnerView['pages'][0]['ids']);

        // level 3
        self::assertSame($innerInnerContent['pages'][0]['resource'], $page2);
        $innerInnerInnerContent = $innerInnerContent['pages'][0]['content'];
        self::assertSame('Page 2', $innerInnerInnerContent['title']);
        self::assertSame('/page-2', $innerInnerInnerContent['url']);
        self::assertSame('<p>Page 2 article</p>', $innerInnerInnerContent['article']);

        $innerInnerInnerView = $innerInnerContent['pages'][0]['view'];
        self::assertSame([], $innerInnerInnerView['title']);
        self::assertSame([], $innerInnerInnerView['url']);
        self::assertSame([], $innerInnerInnerView['article']);
        self::assertSame(['111-111-111'], $innerInnerInnerView['pages'][0]['ids']);

        // level 4 should be null to break the circular loop
        self::assertNull($innerInnerInnerContent['pages'][0]);
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
