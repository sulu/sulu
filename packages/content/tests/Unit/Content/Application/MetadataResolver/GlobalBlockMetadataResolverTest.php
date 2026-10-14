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

namespace Sulu\Content\Tests\Unit\Content\Application\MetadataResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\MetadataResolver\GlobalBlockMetadataResolver;

#[CoversClass(GlobalBlockMetadataResolver::class)]
class GlobalBlockMetadataResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolvesTheGlobalBlockTheBlockTypeReferences(): void
    {
        $globalBlock = $this->createForm('teaser');
        $resolver = $this->createResolver(['teaser' => $globalBlock]);

        $this->assertSame($globalBlock, $resolver->resolve($this->createReference('teaser'), 'en'));
    }

    public function testReturnsTheBlockTypeItselfWhenItReferencesNoGlobalBlock(): void
    {
        $blockType = $this->createForm('text');
        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata(Argument::cetera())->shouldNotBeCalled();

        $this->assertSame($blockType, $this->createResolverForProvider($provider->reveal())->resolve($blockType, 'en'));
    }

    public function testReturnsTheBlockTypeItselfWhenTheGlobalBlockIsUnknown(): void
    {
        $reference = $this->createReference('unknown');

        $this->assertSame($reference, $this->createResolver([])->resolve($reference, 'en'));
    }

    public function testLoadsTheGlobalBlocksOncePerLocale(): void
    {
        $typed = new TypedFormMetadata();
        $typed->addForm('teaser', $this->createForm('teaser'));

        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata('block', 'en', [])->shouldBeCalledTimes(1)->willReturn($typed);
        $provider->getMetadata('block', 'de', [])->shouldBeCalledTimes(1)->willReturn($typed);

        $resolver = $this->createResolverForProvider($provider->reveal());
        $resolver->resolve($this->createReference('teaser'), 'en');
        $resolver->resolve($this->createReference('teaser'), 'en');
        $resolver->resolve($this->createReference('teaser'), 'de');
    }

    /**
     * @param array<string, FormMetadata> $globalBlocks
     */
    private function createResolver(array $globalBlocks): GlobalBlockMetadataResolver
    {
        $typed = new TypedFormMetadata();
        foreach ($globalBlocks as $name => $form) {
            $typed->addForm($name, $form);
        }

        $provider = $this->prophesize(MetadataProviderInterface::class);
        $provider->getMetadata('block', Argument::any(), [])->willReturn($typed);

        return $this->createResolverForProvider($provider->reveal());
    }

    private function createResolverForProvider(MetadataProviderInterface $provider): GlobalBlockMetadataResolver
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('form')->willReturn(true);
        $container->get('form')->willReturn($provider);

        return new GlobalBlockMetadataResolver(new MetadataProviderRegistry($container->reveal()));
    }

    private function createForm(string $key): FormMetadata
    {
        $form = new FormMetadata();
        $form->setKey($key);

        return $form;
    }

    private function createReference(string $globalBlock): FormMetadata
    {
        $type = $this->createForm($globalBlock);
        $tag = new TagMetadata();
        $tag->setName('sulu.global_block');
        $tag->setAttributes(['global_block' => $globalBlock]);
        $type->addTag($tag);

        return $type;
    }
}
