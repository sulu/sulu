<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateFilterTypedFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadataVisitorInterface;

class TemplateFilterTypedFormMetadataVisitorTest extends TestCase
{
    private TemplateFilterTypedFormMetadataVisitor $visitor;

    protected function setUp(): void
    {
        $this->visitor = new TemplateFilterTypedFormMetadataVisitor();
    }

    public function testImplementsInterface(): void
    {
        // This test verifies the visitor implements the correct interface
        $interfaces = \class_implements($this->visitor);
        $this->assertContains(TypedFormMetadataVisitorInterface::class, $interfaces);
    }

    public function testGetDefaultPriority(): void
    {
        $this->assertSame(-100, TemplateFilterTypedFormMetadataVisitor::getDefaultPriority());
    }

    public function testVisitWithoutTemplatesOption(): void
    {
        $typedFormMetadata = new TypedFormMetadata();

        $formMetadata1 = new FormMetadata();
        $formMetadata1->setKey('article');
        $formMetadata2 = new FormMetadata();
        $formMetadata2->setKey('blog');
        $formMetadata3 = new FormMetadata();
        $formMetadata3->setKey('news');

        $typedFormMetadata->addForm('article', $formMetadata1);
        $typedFormMetadata->addForm('blog', $formMetadata2);
        $typedFormMetadata->addForm('news', $formMetadata3);

        // No templates option provided - should not filter anything
        $this->visitor->visitTypedFormMetadata($typedFormMetadata, 'test', 'en', []);

        $this->assertCount(3, $typedFormMetadata->getForms());
        $this->assertArrayHasKey('article', $typedFormMetadata->getForms());
        $this->assertArrayHasKey('blog', $typedFormMetadata->getForms());
        $this->assertArrayHasKey('news', $typedFormMetadata->getForms());
    }

    public function testVisitWithStringTemplatesOption(): void
    {
        $typedFormMetadata = new TypedFormMetadata();

        $formMetadata1 = new FormMetadata();
        $formMetadata1->setKey('article');
        $formMetadata2 = new FormMetadata();
        $formMetadata2->setKey('blog');
        $formMetadata3 = new FormMetadata();
        $formMetadata3->setKey('news');

        $typedFormMetadata->addForm('article', $formMetadata1);
        $typedFormMetadata->addForm('blog', $formMetadata2);
        $typedFormMetadata->addForm('news', $formMetadata3);

        // Filter using comma-separated string
        $this->visitor->visitTypedFormMetadata($typedFormMetadata, 'test', 'en', [
            'templates' => 'article,news',
        ]);

        $this->assertCount(2, $typedFormMetadata->getForms());
        $this->assertArrayHasKey('article', $typedFormMetadata->getForms());
        $this->assertArrayHasKey('news', $typedFormMetadata->getForms());
        $this->assertArrayNotHasKey('blog', $typedFormMetadata->getForms());
    }

    public function testVisitWithEmptyString(): void
    {
        $typedFormMetadata = new TypedFormMetadata();

        $formMetadata1 = new FormMetadata();
        $formMetadata1->setKey('article');

        $typedFormMetadata->addForm('article', $formMetadata1);

        // Empty string should result in no templates kept
        $this->visitor->visitTypedFormMetadata($typedFormMetadata, 'test', 'en', [
            'templates' => '',
        ]);

        $this->assertCount(0, $typedFormMetadata->getForms());
    }

    public function testVisitWithNonExistentTemplates(): void
    {
        $typedFormMetadata = new TypedFormMetadata();

        $formMetadata1 = new FormMetadata();
        $formMetadata1->setKey('article');
        $formMetadata2 = new FormMetadata();
        $formMetadata2->setKey('blog');

        $typedFormMetadata->addForm('article', $formMetadata1);
        $typedFormMetadata->addForm('blog', $formMetadata2);

        // Filter for templates that don't exist
        $this->visitor->visitTypedFormMetadata($typedFormMetadata, 'test', 'en', [
            'templates' => ['non-existent', 'also-missing'],
        ]);

        // All forms should be removed since none match
        $this->assertCount(0, $typedFormMetadata->getForms());
    }

    /**
     * @param string[] $initialTemplates
     * @param string[] $expectedRemainingTemplates
     */
    #[DataProvider('templateFilteringDataProvider')]
    public function testVariousTemplateFilteringScenarios(
        array $initialTemplates,
        mixed $templatesFilter,
        array $expectedRemainingTemplates,
    ): void {
        $typedFormMetadata = new TypedFormMetadata();

        // Add all initial templates
        foreach ($initialTemplates as $templateKey) {
            $formMetadata = new FormMetadata();
            $formMetadata->setKey($templateKey);
            $typedFormMetadata->addForm($templateKey, $formMetadata);
        }

        // Apply filter
        $this->visitor->visitTypedFormMetadata($typedFormMetadata, 'test', 'en', [
            'templates' => $templatesFilter,
        ]);

        // Verify results
        $remainingForms = $typedFormMetadata->getForms();
        $this->assertCount(\count($expectedRemainingTemplates), $remainingForms);

        foreach ($expectedRemainingTemplates as $expectedTemplate) {
            $this->assertArrayHasKey($expectedTemplate, $remainingForms);
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function templateFilteringDataProvider(): array
    {
        return [
            'keep_all_with_array' => [
                ['article', 'blog', 'news'],
                ['article', 'blog', 'news'],
                ['article', 'blog', 'news'],
            ],
            'keep_subset_with_array' => [
                ['article', 'blog', 'news', 'event'],
                ['article', 'news'],
                ['article', 'news'],
            ],
            'keep_single_with_string' => [
                ['article', 'blog'],
                'article',
                ['article'],
            ],
            'keep_multiple_with_string' => [
                ['article', 'blog', 'news'],
                'article,news',
                ['article', 'news'],
            ],
            'keep_none_empty_array' => [
                ['article', 'blog'],
                [],
                [],
            ],
            'keep_none_empty_string' => [
                ['article', 'blog'],
                '',
                [],
            ],
            'filter_with_spaces_in_string' => [
                ['article', 'blog', 'news'],
                'article, blog , news',
                ['article'], // Only 'article' matches exactly; ' blog ' and ' news' have spaces
            ],
            'filter_with_empty_values' => [
                ['article', 'blog', 'news'],
                'article,,blog,',
                ['article', 'blog'],
            ],
        ];
    }
}
