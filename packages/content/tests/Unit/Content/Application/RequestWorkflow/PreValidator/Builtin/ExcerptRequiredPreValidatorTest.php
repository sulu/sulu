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

namespace Sulu\Content\Tests\Unit\Content\Application\RequestWorkflow\PreValidator\Builtin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\RequestWorkflow\PreValidator\Builtin\ExcerptRequiredPreValidator;
use Sulu\Content\Application\RequestWorkflow\PreValidator\PreValidationContext;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Fixture\NonTemplateDimensionContent;

#[CoversClass(ExcerptRequiredPreValidator::class)]
final class ExcerptRequiredPreValidatorTest extends TestCase
{
    /**
     * @template T of ContentRichEntityInterface
     */
    /**
     * `fields: title` instead of `fields: ['title']` is an ordinary YAML slip. Iterating a scalar
     * yields nothing, so the gate would silently approve everything.
     */
    public function testAScalarFieldsConfigThrowsInsteadOfApprovingEverything(): void
    {
        $context = $this->createContext(new ExampleDimensionContent(new Example()), ['fields' => 'title']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "excerpt_required" pre-validator needs a non-empty list of fields, got string.');

        (new ExcerptRequiredPreValidator())->check($context);
    }

    public function testAnEmptyFieldsConfigThrowsInsteadOfApprovingEverything(): void
    {
        $context = $this->createContext(new ExampleDimensionContent(new Example()), ['fields' => []]);

        $this->expectException(\LogicException::class);

        (new ExcerptRequiredPreValidator())->check($context);
    }

    /**
     * @template T of \Sulu\Content\Domain\Model\ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     * @param array<string, mixed> $config
     */
    private function createContext(DimensionContentInterface $dimensionContent, array $config): PreValidationContext
    {
        return new PreValidationContext($dimensionContent, $config, 'default');
    }

    public function testPassesWhenContentDoesNotImplementExcerptInterface(): void
    {
        $dimensionContent = new NonTemplateDimensionContent(new Example());
        $context = $this->createContext($dimensionContent, ['fields' => ['title']]);

        $this->assertSame([], (new ExcerptRequiredPreValidator())->check($context));
    }

    public function testPassesWhenAllConfiguredFieldsAreFilled(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setExcerptData(['title' => 'Excerpt Title', 'description' => 'Excerpt Description']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'description']]);

        $this->assertSame([], (new ExcerptRequiredPreValidator())->check($context));
    }

    public function testFailsAndNamesEveryMissingField(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setExcerptData(['more' => '  ']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'more']]);

        $failures = (new ExcerptRequiredPreValidator())->check($context);

        $this->assertCount(1, $failures);
        $this->assertSame(
            'sulu_content.workflow_transition_request.excerpt_required.missing',
            $failures[0]->messageKey,
        );
        $this->assertSame(['fields' => 'title, more'], $failures[0]->messageParameters);
    }

    /**
     * A project may add fields by overriding the excerpt template, so a configured field is looked
     * up in the data rather than matched against a fixed list.
     */
    public function testCustomFieldFromAnOverriddenTemplateIsChecked(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setExcerptData(['title' => 'Set', 'custom_field' => 'Filled']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'custom_field']]);

        $this->assertSame([], (new ExcerptRequiredPreValidator())->check($context));
    }

    public function testCustomFieldLeftEmptyIsReportedMissing(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setExcerptData(['title' => 'Set']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'custom_field']]);

        $failures = (new ExcerptRequiredPreValidator())->check($context);

        $this->assertCount(1, $failures);
        $this->assertSame(['fields' => 'custom_field'], $failures[0]->messageParameters);
    }
}
