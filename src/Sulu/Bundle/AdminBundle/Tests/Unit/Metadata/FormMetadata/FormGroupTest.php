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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;

class FormGroupTest extends TestCase
{
    public function testConstructor(): void
    {
        $formGroup = new FormGroup('content', 'Content');

        $this->assertSame('content', $formGroup->identifier);
        $this->assertSame('Content', $formGroup->title);
        $this->assertSame([], $formGroup->templates);
    }

    public function testConstructorWithTemplates(): void
    {
        $templates = ['article', 'blog'];
        $formGroup = new FormGroup('content', 'Content', $templates);

        $this->assertSame('content', $formGroup->identifier);
        $this->assertSame('Content', $formGroup->title);
        $this->assertSame($templates, $formGroup->templates);
    }

    public function testWithTemplate(): void
    {
        $formGroup = new FormGroup('content', 'Content');
        $updatedFormGroup = $formGroup->withTemplate('article');

        // Original object should remain unchanged (immutable)
        $this->assertSame([], $formGroup->templates);

        // New object should contain the template
        $this->assertSame(['article'], $updatedFormGroup->templates);
        $this->assertSame('content', $updatedFormGroup->identifier);
        $this->assertSame('Content', $updatedFormGroup->title);
    }

    public function testWithMultipleTemplates(): void
    {
        $formGroup = new FormGroup('content', 'Content');
        $updatedFormGroup1 = $formGroup->withTemplate('article');
        $updatedFormGroup2 = $updatedFormGroup1->withTemplate('blog');
        $updatedFormGroup3 = $updatedFormGroup2->withTemplate('news');

        // Original object should remain unchanged
        $this->assertSame([], $formGroup->templates);

        // Each subsequent object should contain previous templates plus the new one
        $this->assertSame(['article'], $updatedFormGroup1->templates);
        $this->assertSame(['article', 'blog'], $updatedFormGroup2->templates);
        $this->assertSame(['article', 'blog', 'news'], $updatedFormGroup3->templates);
    }

    public function testWithTemplatePreservesExistingTemplates(): void
    {
        $initialTemplates = ['existing1', 'existing2'];
        $formGroup = new FormGroup('content', 'Content', $initialTemplates);
        $updatedFormGroup = $formGroup->withTemplate('new_template');

        $expectedTemplates = ['existing1', 'existing2', 'new_template'];
        $this->assertSame($expectedTemplates, $updatedFormGroup->templates);
    }

    public function testWithTemplateCreatesNewInstance(): void
    {
        $formGroup = new FormGroup('content', 'Content');
        $updatedFormGroup = $formGroup->withTemplate('article');

        $this->assertNotSame($formGroup, $updatedFormGroup);
    }

    public function testWithDuplicateTemplate(): void
    {
        $formGroup = new FormGroup('content', 'Content', ['article']);
        $updatedFormGroup = $formGroup->withTemplate('article');

        // Should add duplicate template (no uniqueness constraint)
        $this->assertSame(['article', 'article'], $updatedFormGroup->templates);
    }

    public function testWithEmptyTemplate(): void
    {
        $formGroup = new FormGroup('content', 'Content');
        $updatedFormGroup = $formGroup->withTemplate('');

        $this->assertSame([''], $updatedFormGroup->templates);
    }

    public function testReadonlyProperties(): void
    {
        $formGroup = new FormGroup('content', 'Content', ['article']);

        // Test that properties are accessible
        $this->assertSame('content', $formGroup->identifier);
        $this->assertSame('Content', $formGroup->title);
        $this->assertSame(['article'], $formGroup->templates);
    }

    public function testImmutability(): void
    {
        $originalTemplates = ['template1'];
        $formGroup = new FormGroup('content', 'Content', $originalTemplates);

        // Modify the original array
        $originalTemplates[] = 'template2';

        // FormGroup should not be affected
        $this->assertSame(['template1'], $formGroup->templates);
    }

    /**
     * @param string[] $templates
     */
    #[DataProvider('formGroupDataProvider')]
    public function testVariousInputs(string $identifier, string $title, array $templates): void
    {
        $formGroup = new FormGroup($identifier, $title, $templates);

        $this->assertSame($identifier, $formGroup->identifier);
        $this->assertSame($title, $formGroup->title);
        $this->assertSame($templates, $formGroup->templates);
    }

    /**
     * @return array<string, array{string, string, string[]}>
     */
    public static function formGroupDataProvider(): array
    {
        return [
            'basic_content' => ['content', 'Content', ['article', 'blog']],
            'news_group' => ['news', 'News Articles', ['breaking', 'sport', 'weather']],
            'empty_templates' => ['empty', 'Empty Group', []],
            'single_template' => ['single', 'Single Template', ['only_template']],
            'special_chars' => ['special-chars_123', 'Special & Chars!', ['temp_1', 'temp-2']],
            'numeric_identifier' => ['123', '123 Group', ['template123']],
            'empty_strings' => ['', '', ['']],
        ];
    }
}
