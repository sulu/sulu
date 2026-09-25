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

namespace Sulu\Article\Tests\Functional\Infrastructure\Sulu\Admin;

use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleResourceViewParameterProvider;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGenerator;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\ViewUrlGenerator;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ArticleResourceViewParameterProviderTest extends SuluTestCase
{
    use CreateArticleTrait;
    use ProphecyTrait;

    private ArticleResourceViewParameterProvider $provider;

    protected function setUp(): void
    {
        /** @var GroupProviderInterface $groupProvider */
        $groupProvider = self::getContainer()->get('sulu_admin.metadata_group_provider');
        $this->provider = new ArticleResourceViewParameterProvider($this->getEntityManager(), $groupProvider);
        $this->purgeDatabase();
    }

    public function testGetResourceKey(): void
    {
        $this->assertSame(ArticleInterface::RESOURCE_KEY, ArticleResourceViewParameterProvider::getResourceKey());
    }

    public function testGetViewParametersWithGroupedTemplate(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'blog', 'title' => 'Blog Article']],
        ]);

        $this->assertSame(
            ['group' => 'blog-group'],
            $this->provider->getViewParameters(['id' => $article->getUuid(), 'locale' => 'en']),
        );
    }

    public function testGetViewParametersWithUngroupedTemplate(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'article', 'title' => 'Article']],
        ]);

        $this->assertSame(
            ['group' => GroupProviderInterface::DEFAULT_GROUP],
            $this->provider->getViewParameters(['id' => $article->getUuid(), 'locale' => 'en']),
        );
    }

    public function testGetViewParametersWithoutLocale(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'blog', 'title' => 'Blog Article']],
        ]);

        $this->assertSame(
            ['group' => 'blog-group'],
            $this->provider->getViewParameters(['id' => $article->getUuid()]),
        );
    }

    public function testGetViewParametersWithUnknownArticle(): void
    {
        $this->assertSame(
            ['group' => GroupProviderInterface::DEFAULT_GROUP],
            $this->provider->getViewParameters(['id' => '00000000-0000-0000-0000-000000000000', 'locale' => 'en']),
        );
    }

    public function testResourceViewUrlGeneratorResolvesConfiguredDetailView(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'blog', 'title' => 'Grouped Article']],
        ]);

        $router = $this->prophesize(UrlGeneratorInterface::class);
        $router->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_PATH)->willReturn('/admin/');
        /** @var ViewRegistry $viewRegistry */
        $viewRegistry = self::getContainer()->get('sulu_admin.view_registry');
        $viewUrlGenerator = new ViewUrlGenerator($router->reveal(), $viewRegistry, new RequestStack());
        /** @var array<string, array{views?: array<string, string>}> $resources */
        $resources = self::getContainer()->getParameter('sulu_admin.resources');
        $resourceViewUrlGenerator = new ResourceViewUrlGenerator(
            $viewUrlGenerator,
            $resources,
            new ServiceLocator([ArticleInterface::RESOURCE_KEY => fn () => $this->provider]),
        );

        $this->assertSame(
            '/admin/#/en/blog-group/' . $article->getUuid(),
            $resourceViewUrlGenerator->generate(ArticleInterface::RESOURCE_KEY, 'detail', ['id' => $article->getUuid(), 'locale' => 'en']),
        );
    }
}
