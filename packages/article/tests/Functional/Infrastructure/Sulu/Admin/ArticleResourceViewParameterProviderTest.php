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

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleResourceViewParameterProvider;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ArticleResourceViewParameterProviderTest extends SuluTestCase
{
    use CreateArticleTrait;

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
            $this->provider->getViewParameters('detail', ['id' => $article->getUuid(), 'locale' => 'en']),
        );
    }

    public function testGetViewParametersWithUngroupedTemplate(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'article', 'title' => 'Article']],
        ]);

        $this->assertSame(
            ['group' => GroupProviderInterface::DEFAULT_GROUP],
            $this->provider->getViewParameters('detail', ['id' => $article->getUuid(), 'locale' => 'en']),
        );
    }

    public function testGetViewParametersUsesTemplateOfGivenLocale(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'article', 'title' => 'Article']],
            'de' => ['draft' => ['template' => 'blog', 'title' => 'Blog Article']],
        ]);

        $this->assertSame(
            ['group' => GroupProviderInterface::DEFAULT_GROUP],
            $this->provider->getViewParameters('detail', ['id' => $article->getUuid(), 'locale' => 'en']),
        );
        $this->assertSame(
            ['group' => 'blog-group'],
            $this->provider->getViewParameters('detail', ['id' => $article->getUuid(), 'locale' => 'de']),
        );
    }

    public function testGetViewParametersUsesDraftTemplate(): void
    {
        $article = static::createArticle([
            'en' => [
                'live' => ['template' => 'article', 'title' => 'Article', 'url' => '/article'],
                'draft' => ['template' => 'blog', 'title' => 'Blog Article'],
            ],
        ]);

        $this->assertSame(
            ['group' => 'blog-group'],
            $this->provider->getViewParameters('detail', ['id' => $article->getUuid(), 'locale' => 'en']),
        );
    }

    public function testGetViewParametersWithoutLocaleUsesFirstLocale(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'article', 'title' => 'Article']],
            'de' => ['draft' => ['template' => 'blog', 'title' => 'Blog Article']],
        ]);

        $this->assertSame(
            ['group' => 'blog-group'],
            $this->provider->getViewParameters('detail', ['id' => $article->getUuid()]),
        );
    }

    public function testGetViewParametersWithUnknownArticle(): void
    {
        $this->assertSame(
            ['group' => GroupProviderInterface::DEFAULT_GROUP],
            $this->provider->getViewParameters('detail', ['id' => '00000000-0000-0000-0000-000000000000', 'locale' => 'en']),
        );
    }

    public function testGetViewParametersForOtherView(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'blog', 'title' => 'Blog Article']],
        ]);

        $this->assertSame([], $this->provider->getViewParameters('list', ['id' => $article->getUuid(), 'locale' => 'en']));
    }

    public function testResourceViewUrlGeneratorResolvesConfiguredDetailView(): void
    {
        $article = static::createArticle([
            'en' => ['draft' => ['template' => 'blog', 'title' => 'Grouped Article']],
        ]);

        /** @var ResourceViewUrlGeneratorInterface $resourceViewUrlGenerator */
        $resourceViewUrlGenerator = self::getContainer()->get('test.sulu_admin.resource_view_url_generator');

        $this->assertSame(
            '/admin/#/en/blog-group/' . $article->getUuid(),
            $resourceViewUrlGenerator->generate(ArticleInterface::RESOURCE_KEY, 'detail', ['id' => $article->getUuid(), 'locale' => 'en']),
        );
    }
}
