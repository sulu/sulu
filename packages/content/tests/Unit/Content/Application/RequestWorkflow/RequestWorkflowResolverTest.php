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

namespace Sulu\Content\Tests\Unit\Content\Application\RequestWorkflow;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\RequestWorkflow\PreValidator\RequestWorkflowPreValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflow;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistry;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolver;
use Sulu\Content\Application\RequestWorkflow\Validator\RequestWorkflowValidatorInterface;
use Sulu\Content\Domain\Exception\UnknownRequestWorkflowException;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Fixture\NonTemplateDimensionContent;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(RequestWorkflowResolver::class)]
class RequestWorkflowResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param array<string, array{resources?: list<string>}> $workflows
     */
    private function makeResolver(array $workflows, ?MetadataProviderInterface $provider = null): RequestWorkflowResolver
    {
        $container = $this->prophesize(ContainerInterface::class);

        if (null !== $provider) {
            $container->has('form')->willReturn(true);
            $container->get('form')->willReturn($provider);
        } else {
            $container->has('form')->willReturn(false);
        }

        /** @var ServiceLocator<RequestWorkflowValidatorInterface> $validators */
        $validators = new ServiceLocator([]);
        /** @var ServiceLocator<RequestWorkflowPreValidatorInterface> $preValidators */
        $preValidators = new ServiceLocator([]);

        return new RequestWorkflowResolver(
            new RequestWorkflowRegistry($workflows, $validators, $preValidators),
            new MetadataProviderRegistry($container->reveal()),
        );
    }

    private function makeMetadataProviderWithTypedForm(string $templateType, string $templateKey, ?string $workflowName): MetadataProviderInterface
    {
        $provider = $this->prophesize(MetadataProviderInterface::class);
        $typed = new TypedFormMetadata();

        $form = new FormMetadata();
        $form->setKey($templateKey);

        if (null !== $workflowName) {
            $tag = new TagMetadata();
            $tag->setName(RequestWorkflowResolver::TEMPLATE_TAG);
            $tag->setAttributes([RequestWorkflowResolver::TEMPLATE_TAG_ATTRIBUTE => $workflowName]);
            $form->addTag($tag);
        }

        $typed->addForm($templateKey, $form);

        $provider->getMetadata($templateType, 'en', [])->willReturn($typed);

        return $provider->reveal();
    }

    public function testResolveForContentWithNonTemplateInterfaceReturnsDefaultWorkflow(): void
    {
        $resolver = $this->makeResolver([RequestWorkflow::DEFAULT_NAME => []]);

        $result = $resolver->resolveForContent(new NonTemplateDimensionContent(new Example()));

        $this->assertSame(RequestWorkflow::DEFAULT_NAME, $result?->name);
    }

    public function testResolveForContentWithNonTemplateInterfaceReturnsNullWhenNoDefaultRegistered(): void
    {
        $resolver = $this->makeResolver([]);

        $this->assertNull($resolver->resolveForContent(new NonTemplateDimensionContent(new Example())));
    }

    public function testResolveForContentWithNullTemplateKeyFallsBackToDefault(): void
    {
        $resolver = $this->makeResolver([RequestWorkflow::DEFAULT_NAME => []]);

        // A fresh ExampleDimensionContent implements TemplateInterface but never had a template set.
        $result = $resolver->resolveForContent(new ExampleDimensionContent(new Example()));

        $this->assertSame(RequestWorkflow::DEFAULT_NAME, $result?->name);
    }

    public function testResolveForContentWithTagPointingToRegisteredWorkflowReturnsIt(): void
    {
        $resolver = $this->makeResolver(
            ['blog' => []],
            $this->makeMetadataProviderWithTypedForm(Example::TEMPLATE_TYPE, 'blog_article', 'blog'),
        );

        $result = $resolver->resolveForContent($this->makeDimensionContent('blog_article'));

        $this->assertSame('blog', $result?->name);
    }

    public function testResolveForContentWithNoneTagReturnsNullDespiteADefaultWorkflow(): void
    {
        $resolver = $this->makeResolver(
            [RequestWorkflow::DEFAULT_NAME => []],
            $this->makeMetadataProviderWithTypedForm(Example::TEMPLATE_TYPE, 'no_review', RequestWorkflow::NONE_NAME),
        );

        $this->assertNull($resolver->resolveForContent($this->makeDimensionContent('no_review')));
    }

    public function testResolveForContentWithTagPointingToUnregisteredWorkflowThrows(): void
    {
        $resolver = $this->makeResolver(
            [],
            $this->makeMetadataProviderWithTypedForm(Example::TEMPLATE_TYPE, 'article', 'nonexistent'),
        );

        $this->expectException(UnknownRequestWorkflowException::class);
        $resolver->resolveForContent($this->makeDimensionContent('article'));
    }

    public function testResolveForContentWithNoTagFallsBackToDefault(): void
    {
        $resolver = $this->makeResolver(
            [RequestWorkflow::DEFAULT_NAME => []],
            $this->makeMetadataProviderWithTypedForm(Example::TEMPLATE_TYPE, 'simple', null),
        );

        $result = $resolver->resolveForContent($this->makeDimensionContent('simple'));

        $this->assertSame(RequestWorkflow::DEFAULT_NAME, $result?->name);
    }

    public function testResolveForContentWhenTemplateTypeHasNoMetadataFallsBackToDefault(): void
    {
        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata(Example::TEMPLATE_TYPE, 'en', [])->willThrow(new MetadataNotFoundException('form', Example::TEMPLATE_TYPE));

        $resolver = $this->makeResolver([RequestWorkflow::DEFAULT_NAME => []], $provider->reveal());

        $result = $resolver->resolveForContent($this->makeDimensionContent('some_template'));

        $this->assertSame(RequestWorkflow::DEFAULT_NAME, $result?->name);
    }

    public function testResolveForContentWhenMetadataIsNotTypedFormMetadataFallsBackToDefault(): void
    {
        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata(Example::TEMPLATE_TYPE, 'en', [])->willReturn(new FormMetadata());

        $resolver = $this->makeResolver([RequestWorkflow::DEFAULT_NAME => []], $provider->reveal());

        $result = $resolver->resolveForContent($this->makeDimensionContent('simple'));

        $this->assertSame(RequestWorkflow::DEFAULT_NAME, $result?->name);
    }

    public function testResolveForContentReturnsNullWhenDefaultWorkflowDoesNotCoverTheResource(): void
    {
        $resolver = $this->makeResolver(
            [RequestWorkflow::DEFAULT_NAME => ['resources' => ['pages', 'articles']]],
            $this->makeMetadataProviderWithTypedForm(Example::TEMPLATE_TYPE, 'simple', null),
        );

        // ExampleDimensionContent resolves to the `examples` resource key, which the workflow
        // does not list, so the implicit default must not apply.
        $this->assertNull($resolver->resolveForContent($this->makeDimensionContent('simple')));
    }

    public function testResolveForContentReturnsDefaultWhenItCoversTheResource(): void
    {
        $resolver = $this->makeResolver(
            [RequestWorkflow::DEFAULT_NAME => ['resources' => [Example::RESOURCE_KEY]]],
            $this->makeMetadataProviderWithTypedForm(Example::TEMPLATE_TYPE, 'simple', null),
        );

        $result = $resolver->resolveForContent($this->makeDimensionContent('simple'));

        $this->assertSame(RequestWorkflow::DEFAULT_NAME, $result?->name);
    }

    private function makeDimensionContent(string $templateKey): ExampleDimensionContent
    {
        $dimensionContent = new ExampleDimensionContent(new Example());
        $dimensionContent->setTemplateKey($templateKey);

        return $dimensionContent;
    }
}
