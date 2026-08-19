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

namespace Sulu\Page\Tests\Unit\Infrastructure\Symfony\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\CoreBundle\Build\FixturesBuilder;
use Sulu\Page\Infrastructure\Sulu\Build\HomepageBuilder;
use Sulu\Page\Infrastructure\Symfony\DependencyInjection\Compiler\FixturesBuilderDependencyPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FixturesBuilderDependencyPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FixturesBuilderDependencyPass());
    }

    public function testAddsHomepageAsAdditionalDependency(): void
    {
        $this->registerFixturesBuilder();
        $this->registerHomepageBuilder();

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'sulu_core.build.builder.fixtures',
            0,
            ['homepage'],
        );
    }

    public function testKeepsExistingAdditionalDependencies(): void
    {
        $this->registerFixturesBuilder(['other']);
        $this->registerHomepageBuilder();

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'sulu_core.build.builder.fixtures',
            0,
            ['other', 'homepage'],
        );
    }

    public function testDoesNotAddHomepageTwice(): void
    {
        $this->registerFixturesBuilder(['homepage']);
        $this->registerHomepageBuilder();

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'sulu_core.build.builder.fixtures',
            0,
            ['homepage'],
        );
    }

    public function testDoesNothingWithoutHomepageBuilder(): void
    {
        $this->registerFixturesBuilder();

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'sulu_core.build.builder.fixtures',
            0,
            [],
        );
    }

    public function testDoesNothingWithoutFixturesBuilder(): void
    {
        $this->registerHomepageBuilder();

        $this->compile();

        $this->assertContainerBuilderNotHasService('sulu_core.build.builder.fixtures');
    }

    /**
     * @param string[] $additionalDependencies
     */
    private function registerFixturesBuilder(array $additionalDependencies = []): void
    {
        $this->setDefinition(
            'sulu_core.build.builder.fixtures',
            new Definition(FixturesBuilder::class, [$additionalDependencies]),
        );
    }

    private function registerHomepageBuilder(): void
    {
        $this->setDefinition('sulu_page.homepage_builder', new Definition(HomepageBuilder::class));
    }
}
