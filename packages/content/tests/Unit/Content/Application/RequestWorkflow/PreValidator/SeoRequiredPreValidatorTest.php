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

namespace Sulu\Content\Tests\Unit\Content\Application\RequestWorkflow\PreValidator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\RequestWorkflow\PreValidator\PreValidationContext;
use Sulu\Content\Application\RequestWorkflow\PreValidator\SeoRequiredPreValidator;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Fixture\NonTemplateDimensionContent;

#[CoversClass(SeoRequiredPreValidator::class)]
final class SeoRequiredPreValidatorTest extends TestCase
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
        $this->expectExceptionMessage('The "seo_required" pre-validator needs a non-empty list of fields, got string.');

        (new SeoRequiredPreValidator())->check($context);
    }

    public function testAnEmptyFieldsConfigThrowsInsteadOfApprovingEverything(): void
    {
        $context = $this->createContext(new ExampleDimensionContent(new Example()), ['fields' => []]);

        $this->expectException(\LogicException::class);

        (new SeoRequiredPreValidator())->check($context);
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

    public function testPassesWhenContentDoesNotImplementSeoInterface(): void
    {
        $dimensionContent = new NonTemplateDimensionContent(new Example());
        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'description']]);

        $this->assertTrue((new SeoRequiredPreValidator())->check($context)->approved);
    }

    public function testPassesWhenAllConfiguredFieldsAreFilled(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setSeoData(['title' => 'My Title', 'description' => 'My Description']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'description']]);

        $this->assertTrue((new SeoRequiredPreValidator())->check($context)->approved);
    }

    public function testFailsAndNamesEveryMissingField(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setSeoData(['title' => '']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'description']]);

        $messages = (new SeoRequiredPreValidator())->check($context)->messages;

        $this->assertCount(1, $messages);
        $this->assertSame(
            'sulu_content.workflow_transition_request.seo_required.missing',
            $messages[0]->key,
        );
        $this->assertSame(['fields' => 'title, description'], $messages[0]->parameters);
    }

    /**
     * A project may add fields by overriding the seo template, so a configured field is looked up
     * in the data rather than matched against a fixed list.
     */
    public function testCustomFieldFromAnOverriddenTemplateIsChecked(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setSeoData(['title' => 'Set', 'custom_field' => 'Filled']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'custom_field']]);

        $this->assertTrue((new SeoRequiredPreValidator())->check($context)->approved);
    }

    public function testCustomFieldLeftEmptyIsReportedMissing(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setSeoData(['title' => 'Set']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title', 'custom_field']]);

        $messages = (new SeoRequiredPreValidator())->check($context)->messages;

        $this->assertCount(1, $messages);
        $this->assertSame(['fields' => 'custom_field'], $messages[0]->parameters);
    }

    public function testWhitespaceOnlyValueCountsAsMissing(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setSeoData(['title' => '   ']);

        $context = $this->createContext($dimensionContent, ['fields' => ['title']]);

        $messages = (new SeoRequiredPreValidator())->check($context)->messages;

        $this->assertCount(1, $messages);
        $this->assertSame(['fields' => 'title'], $messages[0]->parameters);
    }

    /**
     * There is no default field list: a workflow that wants the check has to say what it wants.
     */
    public function testMissingFieldsConfigIsRefused(): void
    {
        $dimensionContent = new ExampleDimensionContent(new Example());

        $context = $this->createContext($dimensionContent, []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "seo_required" pre-validator needs a non-empty list of fields, got null.');

        (new SeoRequiredPreValidator())->check($context);
    }
}
