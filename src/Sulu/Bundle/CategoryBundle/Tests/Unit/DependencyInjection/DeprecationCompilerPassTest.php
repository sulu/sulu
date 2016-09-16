<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\CategoryBundle\DependencyInjection\DeprecationCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Test the deprecation transformation compiler pass.
 */
class DeprecationCompilerPassTest extends AbstractCompilerPassTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DeprecationCompilerPass());
    }

    public function testDeprecatedEntityParameters()
    {
        $this->setParameter('sulu.model.category.class', 'path-to-category-entity');
        $this->setParameter('sulu.model.keyword.class', 'path-to-keyword-entity');

        $this->compile();

        $this->assertContainerBuilderHasParameter('sulu_category.entity.category', 'path-to-category-entity');
        $this->assertContainerBuilderHasParameter('sulu_category.entity.keyword', 'path-to-keyword-entity');
    }

    public function testDeprecatedRepositoryServices()
    {
        $categoryRepositoryDefinition = new Definition();
        $this->setDefinition('sulu.repository.category', $categoryRepositoryDefinition);
        $keywordRepositoryDefinition = new Definition();
        $this->setDefinition('sulu.repository.keyword', $keywordRepositoryDefinition);

        $this->compile();

        $this->assertContainerBuilderHasAlias('sulu_category.category_repository', 'sulu.repository.category');
        $this->assertContainerBuilderHasAlias('sulu_category.keyword_repository', 'sulu.repository.keyword');
    }
}
