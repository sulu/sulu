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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentResolver\Resolver\TemplateMetadataResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class TemplateMetadataResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MetadataProviderInterface>
     */
    private ObjectProphecy $formMetadataProvider;

    private TemplateMetadataResolver $resolver;

    protected function setUp(): void
    {
        $this->formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $this->resolver = new TemplateMetadataResolver($this->formMetadataProvider->reveal());
    }

    public function testResolve(): void
    {
        $dimensionContent = $this->createDimensionContent('en', 'default');

        $rlpTag = new TagMetadata();
        $rlpTag->setName('sulu.rlp');
        $rlpTag->setPriority(10);
        $rlpTag->setAttributes(['part' => 'title']);
        $searchTag = new TagMetadata();
        $searchTag->setName('sulu.search.field');
        $form = new FormMetadata();
        $form->setKey('default');
        $form->setGroup('blog');
        $form->setTags([$rlpTag, $searchTag]);
        $this->mockForms('en', ['default' => $form]);

        self::assertSame([
            'template' => [
                'key' => 'default',
                'group' => 'blog',
                'tags' => [
                    ['name' => 'sulu.rlp', 'priority' => 10, 'part' => 'title'],
                    ['name' => 'sulu.search.field', 'priority' => null],
                ],
            ],
        ], $this->resolve($dimensionContent));
    }

    public function testResolveWithoutGroupAndTags(): void
    {
        $dimensionContent = $this->createDimensionContent('en', 'default');

        $form = new FormMetadata();
        $form->setKey('default');
        $this->mockForms('en', ['default' => $form]);

        self::assertSame(
            ['template' => ['key' => 'default', 'group' => null, 'tags' => []]],
            $this->resolve($dimensionContent),
        );
    }

    public function testResolveUnknownTemplate(): void
    {
        $dimensionContent = $this->createDimensionContent('en', 'removed');
        $this->mockForms('en', []);

        self::assertSame(
            ['template' => ['key' => 'removed', 'group' => null, 'tags' => []]],
            $this->resolve($dimensionContent),
        );
    }

    public function testResolveWithoutLocale(): void
    {
        $dimensionContent = $this->createDimensionContent(null, 'default');
        $this->formMetadataProvider->getMetadata(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(
            ['template' => ['key' => 'default', 'group' => null, 'tags' => []]],
            $this->resolve($dimensionContent),
        );
    }

    public function testResolveWithoutTemplateKey(): void
    {
        $dimensionContent = $this->createDimensionContent('en', null);
        $this->formMetadataProvider->getMetadata(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(
            ['template' => ['key' => null, 'group' => null, 'tags' => []]],
            $this->resolve($dimensionContent),
        );
    }

    public function testResolveWithoutTemplateInterface(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $this->formMetadataProvider->getMetadata(Argument::cetera())->shouldNotBeCalled();

        self::assertNull($this->resolver->resolve($dimensionContent->reveal()));
    }

    public function testTypeAndOutputPath(): void
    {
        self::assertSame('templateMetadata', $this->resolver->getType());
        self::assertSame('', $this->resolver->getOutputPath());
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve(ExampleDimensionContent $dimensionContent): array
    {
        $contentView = $this->resolver->resolve($dimensionContent);
        self::assertInstanceOf(ContentView::class, $contentView);
        self::assertSame([], $contentView->getView());

        /** @var array<string, mixed> */
        return $contentView->getContent();
    }

    private function createDimensionContent(?string $locale, ?string $templateKey): ExampleDimensionContent
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setLocale($locale);
        if (null !== $templateKey) {
            $dimensionContent->setTemplateKey($templateKey);
        }

        return $dimensionContent;
    }

    /**
     * @param array<string, FormMetadata> $forms
     */
    private function mockForms(string $locale, array $forms): void
    {
        $typedFormMetadata = new TypedFormMetadata();
        foreach ($forms as $key => $form) {
            $typedFormMetadata->addForm($key, $form);
        }

        $this->formMetadataProvider->getMetadata('example', $locale, [])->willReturn($typedFormMetadata);
    }
}
