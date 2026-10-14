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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\MetadataResolver\GlobalBlockMetadataResolver;
use Sulu\Content\Application\RequestWorkflow\PreValidator\PreValidationContext;
use Sulu\Content\Application\RequestWorkflow\PreValidator\PublishRequiredPreValidator;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Fixture\NonTemplateDimensionContent;

#[CoversClass(PublishRequiredPreValidator::class)]
final class PublishRequiredPreValidatorTest extends TestCase
{
    use ProphecyTrait;

    public function testKey(): void
    {
        $this->assertSame('publish_required', PublishRequiredPreValidator::getKey());
    }

    public function testPassesWhenContentHasNoTemplate(): void
    {
        $validator = $this->createValidator([]);
        $context = $this->createContext(new NonTemplateDimensionContent(new Example()));

        $this->assertTrue($validator->check($context)->approved);
    }

    public function testPassesWithoutLoadingMetadataWhenContentHasNoTemplateKeyYet(): void
    {
        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata(Argument::cetera())->shouldNotBeCalled();

        $validator = $this->createValidatorWithRegistry($this->createRegistry($provider->reveal()));

        $this->assertTrue($validator->check($this->createContext(new ExampleDimensionContent(new Example())))->approved);
    }

    public function testPassesWhenTheTemplateTypeHasNoFormMetadata(): void
    {
        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata(Example::TEMPLATE_TYPE, 'en', [])->willThrow(new MetadataNotFoundException('form', Example::TEMPLATE_TYPE));

        $validator = $this->createValidatorWithRegistry($this->createRegistry($provider->reveal()));

        $this->assertTrue($validator->check($this->createContext($this->createDimensionContent(['intro' => ''])))->approved);
    }

    public function testPassesWhenNoPropertyCarriesTheTag(): void
    {
        $validator = $this->createValidator(['intro' => false]);

        $this->assertTrue($validator->check($this->createContext($this->createDimensionContent([])))->approved);
    }

    public function testPassesWhenTaggedPropertyIsFilled(): void
    {
        $validator = $this->createValidator(['intro' => true]);

        $context = $this->createContext($this->createDimensionContent(['intro' => 'Hello']));

        $this->assertTrue($validator->check($context)->approved);
    }

    public function testFailsAndNamesEveryMissingProperty(): void
    {
        $validator = $this->createValidator(['intro' => true, 'summary' => true, 'title' => false]);

        $context = $this->createContext($this->createDimensionContent(['intro' => '', 'title' => '']));

        $result = $validator->check($context);

        $this->assertFalse($result->approved);
        $this->assertCount(1, $result->messages);
        $this->assertSame('sulu_content.workflow_transition_request.publish_required.missing', $result->messages[0]->key);
        $this->assertSame(['fields' => 'intro, summary'], $result->messages[0]->parameters);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function emptyValueProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'whitespace' => ['  '];
        yield 'empty list' => [[]];
        yield 'selection without ids' => [['ids' => []]];
        yield 'media selection with its default display option' => [['ids' => [], 'displayOption' => 'left']];
        yield 'teaser selection with its presentation' => [['items' => [], 'presentAs' => 'two-columns']];
    }

    #[DataProvider('emptyValueProvider')]
    public function testTreatsEmptyValuesAsMissing(mixed $value): void
    {
        $validator = $this->createValidator(['intro' => true]);

        $context = $this->createContext($this->createDimensionContent(['intro' => $value]));

        $this->assertFalse($validator->check($context)->approved);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function filledValueProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'false' => [false];
        yield 'text' => ['a'];
        yield 'selection' => [['ids' => [1]]];
        yield 'media selection' => [['ids' => [1], 'displayOption' => 'left']];
        yield 'single selection holding only its default display option' => [['id' => null, 'displayOption' => 'left']];
    }

    #[DataProvider('filledValueProvider')]
    public function testTreatsAnyOtherValueAsFilled(mixed $value): void
    {
        $validator = $this->createValidator(['intro' => true]);

        $context = $this->createContext($this->createDimensionContent(['intro' => $value]));

        $this->assertTrue($validator->check($context)->approved);
    }

    /**
     * A shadow publishes the live content of its source locale, so its own data says nothing.
     */
    public function testPassesForAShadowLocale(): void
    {
        $validator = $this->createValidator(['intro' => true]);

        $dimensionContent = $this->createDimensionContent(['intro' => '']);
        $dimensionContent->setShadowLocale('de');

        $this->assertTrue($validator->check($this->createContext($dimensionContent))->approved);
    }

    public function testChecksPropertiesInsideBlocksAndNamesThemByPath(): void
    {
        $validator = $this->createBlockValidator();

        $context = $this->createContext($this->createDimensionContent([
            'blocks' => [
                ['type' => 'text', 'intro' => 'Fine', 'body' => ''],
                ['type' => 'text', 'intro' => ' ', 'body' => ''],
                ['type' => 'quote', 'text' => ''],
            ],
        ]));

        $result = $validator->check($context);

        $this->assertFalse($result->approved);
        $this->assertSame(['fields' => 'blocks/1/intro'], $result->messages[0]->parameters);
    }

    public function testChecksPropertiesOfAGlobalBlockTheTypeReferences(): void
    {
        $referenceType = new FormMetadata();
        $referenceType->setKey('teaser');
        $reference = new TagMetadata();
        $reference->setName('sulu.global_block');
        $reference->setAttributes(['global_block' => 'teaser']);
        $referenceType->addTag($reference);

        $blocks = $this->createField('blocks', false, 'block');
        $blocks->addType($referenceType);

        $form = new FormMetadata();
        $form->setKey('default');
        $form->addItem($blocks);

        $globalBlock = new FormMetadata();
        $globalBlock->setKey('teaser');
        $globalBlock->addItem($this->createField('headline', true));

        $validator = $this->createValidatorForForm($form, $globalBlock);

        $result = $validator->check($this->createContext($this->createDimensionContent([
            'blocks' => [['type' => 'teaser', 'headline' => '']],
        ])));

        $this->assertFalse($result->approved);
        $this->assertSame(['fields' => 'blocks/0/headline'], $result->messages[0]->parameters);
    }

    public function testPassesWhenNoBlockExistsToCarryTheTaggedProperty(): void
    {
        $context = $this->createContext($this->createDimensionContent(['blocks' => []]));

        $this->assertTrue($this->createBlockValidator()->check($context)->approved);
    }

    /**
     * @param array<string, bool> $properties name => carries the tag
     */
    private function createValidator(array $properties): PublishRequiredPreValidator
    {
        $form = new FormMetadata();
        $form->setKey('default');

        foreach ($properties as $name => $tagged) {
            $form->addItem($this->createField($name, $tagged));
        }

        return $this->createValidatorForForm($form);
    }

    private function createBlockValidator(): PublishRequiredPreValidator
    {
        $textType = new FormMetadata();
        $textType->setKey('text');
        $textType->addItem($this->createField('intro', true));
        $textType->addItem($this->createField('body', false));

        $quoteType = new FormMetadata();
        $quoteType->setKey('quote');
        $quoteType->addItem($this->createField('text', false));

        $blocks = $this->createField('blocks', false, 'block');
        $blocks->addType($textType);
        $blocks->addType($quoteType);

        $form = new FormMetadata();
        $form->setKey('default');
        $form->addItem($blocks);

        return $this->createValidatorForForm($form);
    }

    private function createField(string $name, bool $tagged, string $type = 'text_line'): FieldMetadata
    {
        $field = new FieldMetadata($name);
        $field->setType($type);

        if ($tagged) {
            $tag = new TagMetadata();
            $tag->setName(PublishRequiredPreValidator::TAG);
            $field->addTag($tag);
        }

        return $field;
    }

    private function createValidatorForForm(FormMetadata $form, ?FormMetadata $globalBlock = null): PublishRequiredPreValidator
    {
        $typed = new TypedFormMetadata();
        $typed->addForm('default', $form);

        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata(Example::TEMPLATE_TYPE, 'en', Argument::any())->willReturn($typed);

        $globalBlocks = new TypedFormMetadata();
        if ($globalBlock instanceof FormMetadata) {
            $globalBlocks->addForm($globalBlock->getKey(), $globalBlock);
        }
        $provider->getMetadata('block', 'en', Argument::any())->willReturn($globalBlocks);

        return $this->createValidatorWithRegistry($this->createRegistry($provider->reveal()));
    }

    private function createValidatorWithRegistry(MetadataProviderRegistry $registry): PublishRequiredPreValidator
    {
        return new PublishRequiredPreValidator($registry, new GlobalBlockMetadataResolver($registry));
    }

    private function createRegistry(MetadataProviderInterface $provider): MetadataProviderRegistry
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('form')->willReturn(true);
        $container->get('form')->willReturn($provider);

        return new MetadataProviderRegistry($container->reveal());
    }

    /**
     * @param array<string, mixed> $templateData
     */
    private function createDimensionContent(array $templateData): ExampleDimensionContent
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');
        $dimensionContent->setTemplateData($templateData);

        return $dimensionContent;
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     */
    private function createContext(DimensionContentInterface $dimensionContent): PreValidationContext
    {
        return new PreValidationContext($dimensionContent, [], 'default');
    }
}
