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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Search\Visitor;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexContentEnhancer;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

class WebsiteArticleReindexContentEnhancerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MetadataProviderInterface>
     */
    private ObjectProphecy $formMetadataProvider;

    private WebsiteArticleReindexContentEnhancer $enhancer;

    protected function setUp(): void
    {
        $this->formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $this->enhancer = new WebsiteArticleReindexContentEnhancer($this->formMetadataProvider->reveal());
    }

    public function testEnhanceQueryAddsTemplateFields(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->addSelect('dimensionContent.templateKey')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->addSelect('dimensionContent.templateData')->willReturn($queryBuilder)->shouldBeCalled();

        $this->enhancer->enhanceQuery($queryBuilder->reveal());
    }

    public function testVisitWithoutTemplateKey(): void
    {
        $result = [
            'locale' => 'en',
            'templateData' => ['title' => 'Test'],
        ];

        $data = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($result, $data);

        $this->assertSame($data, $returnedData);
        $this->formMetadataProvider->getMetadata(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testVisitWithoutLocale(): void
    {
        $result = [
            'templateKey' => 'default',
            'templateData' => ['title' => 'Test'],
        ];

        $data = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($result, $data);

        $this->assertSame($data, $returnedData);
        $this->formMetadataProvider->getMetadata(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testVisitWithEmptyTemplateData(): void
    {
        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [],
        ];

        $data = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($result, $data);

        $this->assertSame($data, $returnedData);
        $this->formMetadataProvider->getMetadata(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testVisitExtractsTaggedFields(): void
    {
        $titleField = new FieldMetadata('title');
        $titleField->setType('text_line');
        $titleField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $descriptionField = new FieldMetadata('description');
        $descriptionField->setType('text_area');
        $descriptionField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $urlField = new FieldMetadata('url');
        $urlField->setType('text_line');

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($titleField);
        $formMetadata->addItem($descriptionField);
        $formMetadata->addItem($urlField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'title' => 'Test Title',
                'description' => 'Test Description',
                'url' => '/test-url',
            ],
        ];

        $data = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($result, $data);

        $this->assertSame(['Test Title', 'Test Description'], $returnedData['content']);
    }

    public function testVisitStripsHtmlTags(): void
    {
        $textField = new FieldMetadata('text');
        $textField->setType('text_editor');
        $textField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($textField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'text' => '<p>Hello <strong>world</strong></p>',
            ],
        ];

        $data = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($result, $data);

        $this->assertSame(['Hello world'], $returnedData['content']);
    }

    public function testVisitFiltersNonTextFieldTypes(): void
    {
        $titleField = new FieldMetadata('title');
        $titleField->setType('text_line');
        $titleField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $mediaField = new FieldMetadata('media');
        $mediaField->setType('media_selection');
        $mediaField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $selectField = new FieldMetadata('category');
        $selectField->setType('single_select');
        $selectField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $checkboxField = new FieldMetadata('featured');
        $checkboxField->setType('checkbox');
        $checkboxField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $dateField = new FieldMetadata('published');
        $dateField->setType('date');
        $dateField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($titleField);
        $formMetadata->addItem($mediaField);
        $formMetadata->addItem($selectField);
        $formMetadata->addItem($checkboxField);
        $formMetadata->addItem($dateField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'title' => 'Test Title',
                'media' => ['id' => 123],
                'category' => 5,
                'featured' => true,
                'published' => 1735689600,
            ],
        ];

        $data = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($result, $data);

        $this->assertSame(['Test Title'], $returnedData['content']);
    }

    /**
     * @param array<string, mixed> $templateData
     * @param array<int, string> $expectedContent
     */
    #[DataProvider('provideBlockScenarios')]
    public function testVisitExtractsContentFromBlocks(array $templateData, array $expectedContent, string $scenario): void
    {
        $formMetadata = $this->createBlockFormMetadata();

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => $templateData,
        ];

        $data = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($result, $data);

        $this->assertSame($expectedContent, $returnedData['content'], 'Failed for scenario: ' . $scenario);
    }

    /**
     * @return array<string, array{array<string, mixed>, array<int, string>, string}>
     */
    public static function provideBlockScenarios(): array
    {
        return [
            'single block with one item' => [
                [
                    'title' => 'Article Title',
                    'blocks' => [
                        [
                            'type' => 'text',
                            'text' => 'First block text',
                        ],
                    ],
                ],
                ['Article Title', 'First block text'],
                'single block with one item',
            ],
            'single block with multiple items' => [
                [
                    'title' => 'Article Title',
                    'blocks' => [
                        [
                            'type' => 'text',
                            'text' => 'First block',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Second block',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Third block',
                        ],
                    ],
                ],
                ['Article Title', 'First block', 'Second block', 'Third block'],
                'single block with multiple items',
            ],
            'blocks with HTML content' => [
                [
                    'title' => 'Article Title',
                    'blocks' => [
                        [
                            'type' => 'text',
                            'text' => '<p>Block with <strong>HTML</strong></p>',
                        ],
                        [
                            'type' => 'text',
                            'text' => '<div>Another <em>formatted</em> text</div>',
                        ],
                    ],
                ],
                ['Article Title', 'Block with HTML', 'Another formatted text'],
                'blocks with HTML content',
            ],
            'empty block array' => [
                [
                    'title' => 'Article Title',
                    'blocks' => [],
                ],
                ['Article Title'],
                'empty block array',
            ],
            'block with non-searchable fields' => [
                [
                    'title' => 'Article Title',
                    'blocks' => [
                        [
                            'type' => 'text',
                            'text' => 'Searchable text',
                            'settings' => 'Non-searchable setting',
                        ],
                    ],
                ],
                ['Article Title', 'Searchable text'],
                'block with non-searchable fields',
            ],
            'nested blocks (2 levels)' => [
                [
                    'title' => 'Article Title',
                    'blocks' => [
                        [
                            'type' => 'wrapper',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Nested text content',
                                ],
                            ],
                        ],
                    ],
                ],
                ['Article Title', 'Nested text content'],
                'nested blocks (2 levels)',
            ],
            'deeply nested blocks (3 levels)' => [
                [
                    'title' => 'Article Title',
                    'blocks' => [
                        [
                            'type' => 'wrapper',
                            'content' => [
                                [
                                    'type' => 'wrapper',
                                    'content' => [
                                        [
                                            'type' => 'text',
                                            'text' => 'Deeply nested text',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                ['Article Title', 'Deeply nested text'],
                'deeply nested blocks (3 levels)',
            ],
            'multiple nested blocks in list' => [
                [
                    'title' => 'Article Title',
                    'blocks' => [
                        [
                            'type' => 'wrapper',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'First nested',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'Second nested',
                                ],
                            ],
                        ],
                        [
                            'type' => 'wrapper',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Third nested',
                                ],
                            ],
                        ],
                    ],
                ],
                ['Article Title', 'First nested', 'Second nested', 'Third nested'],
                'multiple nested blocks in list',
            ],
        ];
    }

    public function testVisitExtractsContentFromGlobalBlocks(): void
    {
        // A global block "ref" type carries the tag but no items (as produced by the metadata parser).
        $globalBlockType = new FormMetadata();
        $globalBlockType->setKey('text_content');
        $globalBlockType->addTag($this->createGlobalBlockTag('text_content'));

        $blockField = new FieldMetadata('main_content');
        $blockField->setType('block');
        $blockField->addType($globalBlockType);

        $articleForm = new FormMetadata();
        $articleForm->setKey('default');
        $articleForm->addItem($blockField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $articleForm);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $contentField = new FieldMetadata('content');
        $contentField->setType('text_editor');
        $contentField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $globalBlockForm = new FormMetadata();
        $globalBlockForm->setKey('text_content');
        $globalBlockForm->addItem($contentField);

        $blockMetadata = new TypedFormMetadata();
        $blockMetadata->addForm('text_content', $globalBlockForm);

        $this->formMetadataProvider->getMetadata('block', 'en', ['ignore_global_blocks' => true])
            ->willReturn($blockMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'main_content' => [
                    [
                        'type' => 'text_content',
                        'content' => '<p>Global block content</p>',
                    ],
                ],
            ],
        ];

        $returnedData = $this->enhancer->enhanceDocument($result, ['content' => []]);

        $this->assertSame(['Global block content'], $returnedData['content']);
    }

    public function testRoleImageSingleMediaSelectionSetsMediaId(): void
    {
        $imageField = new FieldMetadata('header_image');
        $imageField->setType('single_media_selection');
        $imageField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG, 'image'));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($imageField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'header_image' => ['id' => 42],
            ],
        ];

        $returnedData = $this->enhancer->enhanceDocument($result, ['content' => []]);

        $this->assertSame('42', $returnedData['mediaId']);
    }

    public function testRoleImageMediaSelectionSetsMediaIdFromFirstId(): void
    {
        $imageField = new FieldMetadata('images');
        $imageField->setType('media_selection');
        $imageField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG, 'image'));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($imageField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'images' => ['ids' => [10, 20, 30]],
            ],
        ];

        $returnedData = $this->enhancer->enhanceDocument($result, ['content' => []]);

        $this->assertSame('10', $returnedData['mediaId']);
    }

    public function testRoleImageWithEmptyDataDoesNotSetMediaId(): void
    {
        $imageField = new FieldMetadata('header_image');
        $imageField->setType('single_media_selection');
        $imageField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG, 'image'));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($imageField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'header_image' => [],
            ],
        ];

        $returnedData = $this->enhancer->enhanceDocument($result, ['content' => []]);

        $this->assertArrayNotHasKey('mediaId', $returnedData);
    }

    public function testRoleTitleOverridesDocumentTitle(): void
    {
        $titleField = new FieldMetadata('headline');
        $titleField->setType('text_line');
        $titleField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG, 'title'));

        $descriptionField = new FieldMetadata('description');
        $descriptionField->setType('text_area');
        $descriptionField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($titleField);
        $formMetadata->addItem($descriptionField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'headline' => 'Custom Headline',
                'description' => 'Some description',
            ],
        ];

        $returnedData = $this->enhancer->enhanceDocument($result, ['content' => [], 'title' => 'Original Title']);

        $this->assertSame('Custom Headline', $returnedData['title']);
        $this->assertSame(['Some description'], $returnedData['content']);
    }

    public function testRoleTitleFieldExcludedFromContent(): void
    {
        $titleField = new FieldMetadata('headline');
        $titleField->setType('text_line');
        $titleField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG, 'title'));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($titleField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'headline' => 'Custom Headline',
            ],
        ];

        $returnedData = $this->enhancer->enhanceDocument($result, ['content' => []]);

        $this->assertSame([], $returnedData['content']);
    }

    public function testRoleUrlFieldExcludedFromContent(): void
    {
        $urlField = new FieldMetadata('custom_url');
        $urlField->setType('text_line');
        $urlField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG, 'url'));

        $descriptionField = new FieldMetadata('description');
        $descriptionField->setType('text_area');
        $descriptionField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($urlField);
        $formMetadata->addItem($descriptionField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'custom_url' => '/some-url',
                'description' => 'Some description',
            ],
        ];

        $returnedData = $this->enhancer->enhanceDocument($result, ['content' => []]);

        $this->assertSame(['Some description'], $returnedData['content']);
    }

    public function testNoRoleFieldIncludedInContent(): void
    {
        $textField = new FieldMetadata('body');
        $textField->setType('text_editor');
        $textField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($textField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', $formMetadata);

        $this->formMetadataProvider->getMetadata('article', 'en', [])
            ->willReturn($typedFormMetadata)
            ->shouldBeCalledOnce();

        $result = [
            'templateKey' => 'default',
            'locale' => 'en',
            'templateData' => [
                'body' => '<p>Body content</p>',
            ],
        ];

        $returnedData = $this->enhancer->enhanceDocument($result, ['content' => []]);

        $this->assertSame(['Body content'], $returnedData['content']);
    }

    private function createBlockFormMetadata(): FormMetadata
    {
        $titleField = new FieldMetadata('title');
        $titleField->setType('text_line');
        $titleField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $textField = new FieldMetadata('text');
        $textField->setType('text_editor');
        $textField->addTag($this->createTag(TagMetadata::SEARCH_FIELD_TAG));

        $settingsField = new FieldMetadata('settings');
        $settingsField->setType('text_line');

        $textBlockForm = new FormMetadata();
        $textBlockForm->setKey('text');
        $textBlockForm->addItem($textField);
        $textBlockForm->addItem($settingsField);

        $contentFieldLevel3 = new FieldMetadata('content');
        $contentFieldLevel3->setType('block');
        $contentFieldLevel3->addType($textBlockForm);

        $wrapperBlockFormLevel3 = new FormMetadata();
        $wrapperBlockFormLevel3->setKey('wrapper');
        $wrapperBlockFormLevel3->addItem($contentFieldLevel3);

        $contentFieldLevel2 = new FieldMetadata('content');
        $contentFieldLevel2->setType('block');
        $contentFieldLevel2->addType($textBlockForm);
        $contentFieldLevel2->addType($wrapperBlockFormLevel3);

        $wrapperBlockFormLevel2 = new FormMetadata();
        $wrapperBlockFormLevel2->setKey('wrapper');
        $wrapperBlockFormLevel2->addItem($contentFieldLevel2);

        $contentFieldLevel1 = new FieldMetadata('content');
        $contentFieldLevel1->setType('block');
        $contentFieldLevel1->addType($textBlockForm);
        $contentFieldLevel1->addType($wrapperBlockFormLevel2);

        $wrapperBlockFormLevel1 = new FormMetadata();
        $wrapperBlockFormLevel1->setKey('wrapper');
        $wrapperBlockFormLevel1->addItem($contentFieldLevel1);

        $blocksField = new FieldMetadata('blocks');
        $blocksField->setType('block');
        $blocksField->addType($textBlockForm);
        $blocksField->addType($wrapperBlockFormLevel1);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($titleField);
        $formMetadata->addItem($blocksField);

        return $formMetadata;
    }

    private function createTag(string $name, ?string $role = null): TagMetadata
    {
        $tag = new TagMetadata();
        $tag->setName($name);

        if (null !== $role) {
            $tag->setAttributes(['role' => $role]);
        }

        return $tag;
    }

    private function createGlobalBlockTag(string $blockName): TagMetadata
    {
        $tag = new TagMetadata();
        $tag->setName('sulu.global_block');
        $tag->setAttributes(['global_block' => $blockName]);

        return $tag;
    }
}
